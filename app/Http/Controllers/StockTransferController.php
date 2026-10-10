<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function index(Request $request): View
    {
        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'user'])
            ->withSum('items as total_quantity', 'quantity')
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where(fn ($q) => $q->where('from_branch_id', $request->branch_id)->orWhere('to_branch_id', $request->branch_id));
            })
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $branches = Branch::orderBy('name')->get();

        return view('transfers.index', compact('transfers', 'branches'));
    }

    public function create(): View
    {
        $branches = Branch::active()->orderBy('name')->get();
        $fromBranch = Branch::current();

        // Stock on hand per branch, so the form can show what is available at the source.
        $products = Product::where('is_active', true)
            ->with('branchStocks:product_id,branch_id,quantity')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit' => $p->unit,
                'stock' => $p->branchStocks->pluck('quantity', 'branch_id'),
            ]);

        return view('transfers.create', compact('branches', 'fromBranch', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'to_branch_id' => ['required', 'different:from_branch_id', Rule::exists('branches', 'id')->where('is_active', true)],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'to_branch_id.different' => 'The destination must be a different branch.',
            'items.*.product_id.distinct' => 'Each product can only be listed once.',
        ]);

        $transfer = DB::transaction(function () use ($data, $request) {
            $from = Branch::findOrFail($data['from_branch_id']);
            $to = Branch::findOrFail($data['to_branch_id']);

            $transfer = StockTransfer::create([
                'transfer_number' => StockTransfer::generateTransferNumber(),
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            foreach ($data['items'] as $index => $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();
                $qty = (int) $item['quantity'];
                $available = $product->stockAt($from);

                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Insufficient stock for {$product->name} at {$from->name}. Available: {$available}.",
                    ]);
                }

                $transfer->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $qty,
                ]);

                $product->recordMovement(StockMovement::TYPE_TRANSFER_OUT, -$qty, "Transfer {$transfer->transfer_number} to {$to->name}", StockTransfer::class, $transfer->id, $from->id);
                $product->recordMovement(StockMovement::TYPE_TRANSFER_IN, $qty, "Transfer {$transfer->transfer_number} from {$from->name}", StockTransfer::class, $transfer->id, $to->id);
            }

            return $transfer;
        });

        return redirect()->route('transfers.show', $transfer)->with('success', "Stock transfer {$transfer->transfer_number} completed.");
    }

    public function show(StockTransfer $transfer): View
    {
        $transfer->load(['fromBranch', 'toBranch', 'user', 'items']);

        return view('transfers.show', compact('transfer'));
    }
}
