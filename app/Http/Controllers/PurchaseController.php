<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\AccountPayable;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Purchase::with(['branch', 'supplier', 'user', 'items', 'accountPayable'])->latest('receipt_date')->latest('id');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('purchase_number', 'like', $term)
                    ->orWhere('reference_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term);
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }

        // Summary metrics
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthDeliveriesQuery = Purchase::whereBetween('receipt_date', [$startOfMonth, $endOfMonth]);
        $monthDeliveriesCount = (clone $monthDeliveriesQuery)->count();
        $monthDeliveriesTotal = (float) (clone $monthDeliveriesQuery)->sum('total_amount');

        $pendingCreditDeliveries = Purchase::where('payment_status', '!=', Purchase::PAYMENT_PAID)->count();
        $pendingCreditAmount = (float) Purchase::where('payment_status', '!=', Purchase::PAYMENT_PAID)
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as balance')
            ->value('balance');

        $purchases = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.index', compact(
            'purchases',
            'suppliers',
            'monthDeliveriesCount',
            'monthDeliveriesTotal',
            'pendingCreditDeliveries',
            'pendingCreditAmount'
        ));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();
        $branch = Branch::current();
        $products = Product::where('is_active', true)
            ->select(['id', 'name', 'sku', 'barcode', 'unit', 'cost_price', 'selling_price', 'current_stock'])
            ->withBranchStock($branch)
            ->orderBy('name')
            ->get();

        return view('purchases.create', compact('suppliers', 'products', 'branch'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'receipt_date' => ['required', 'date'],
            'payment_terms' => ['required', 'in:credit,paid'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'downpayment' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'record_as_expense' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.update_cost_price' => ['nullable', 'boolean'],
        ]);

        // Deliveries are received into the branch the user is currently working in.
        $branchId = Branch::current()?->id;

        $purchase = DB::transaction(function () use ($request, $validated, $branchId) {
            $supplierName = $validated['supplier_name'] ?? null;
            if (empty($supplierName) && ! empty($validated['supplier_id'])) {
                $supplierName = Supplier::find($validated['supplier_id'])?->name;
            }
            if (empty($supplierName)) {
                $supplierName = 'General Supplier';
            }

            // Calculate total
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += (int) $item['quantity'] * (float) $item['unit_cost'];
            }

            $isCredit = $validated['payment_terms'] === 'credit';
            $downpayment = max(0, (float) ($validated['downpayment'] ?? 0));
            if ($isCredit) {
                $amountPaid = min($totalAmount, $downpayment);
                $paymentStatus = $amountPaid >= $totalAmount
                    ? Purchase::PAYMENT_PAID
                    : ($amountPaid > 0 ? Purchase::PAYMENT_PARTIAL : Purchase::PAYMENT_UNPAID);
                $paymentMethod = $amountPaid > 0 ? ($validated['payment_method'] ?? 'cash') : 'credit';
            } else {
                $amountPaid = $totalAmount;
                $paymentStatus = Purchase::PAYMENT_PAID;
                $paymentMethod = $validated['payment_method'] ?: 'cash';
            }

            $dueDate = $isCredit
                ? ($validated['due_date'] ?? now()->addDays(30)->toDateString())
                : null;

            $purchase = Purchase::create([
                'purchase_number' => Purchase::generatePurchaseNumber(),
                'branch_id' => $branchId,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'supplier_name' => $supplierName,
                'reference_number' => $validated['reference_number'] ?? null,
                'receipt_date' => $validated['receipt_date'],
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid,
                'due_date' => $dueDate,
                'status' => Purchase::STATUS_RECEIVED,
                'notes' => $validated['notes'] ?? null,
                'user_id' => $request->user()->id,
            ]);

            // Save items & increment stock
            foreach ($validated['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $qty = (int) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $subtotal = $qty * $unitCost;
                $updateCost = ! empty($itemData['update_cost_price']);

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'update_cost_price' => $updateCost,
                ]);

                // Record stock movement (Purchase)
                $drNote = $purchase->reference_number
                    ? "Delivery DR #{$purchase->reference_number} from {$supplierName}"
                    : "Stock Delivery {$purchase->purchase_number} from {$supplierName}";

                $product->recordMovement(
                    StockMovement::TYPE_PURCHASE,
                    $qty,
                    $drNote,
                    Purchase::class,
                    $purchase->id,
                    $branchId
                );

                if ($updateCost && $unitCost > 0) {
                    $product->cost_price = $unitCost;
                    $product->save();
                }
            }

            // Create Accounts Payable bill if on credit with remaining balance
            $balance = max(0, $totalAmount - $amountPaid);
            if ($isCredit && $balance > 0) {
                $billNumber = $purchase->reference_number ?: $purchase->purchase_number;

                $payable = AccountPayable::create([
                    'supplier_id' => $purchase->supplier_id,
                    'vendor_name' => $supplierName,
                    'bill_number' => $billNumber,
                    'bill_date' => $purchase->receipt_date,
                    'due_date' => $dueDate,
                    'amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'status' => $amountPaid > 0 ? AccountPayable::STATUS_PARTIALLY_PAID : AccountPayable::STATUS_UNPAID,
                    'payment_method' => $amountPaid > 0 ? $paymentMethod : null,
                    'notes' => "Auto-created from Delivery Receipt {$purchase->purchase_number}" . ($purchase->reference_number ? " (DR: {$purchase->reference_number})" : ''),
                    'user_id' => $request->user()->id,
                ]);

                $purchase->update(['account_payable_id' => $payable->id]);
            }

            // Record as expense if paid immediately and requested
            if (! $isCredit && $request->boolean('record_as_expense') && $amountPaid > 0) {
                $byCheck = str_contains(strtolower((string) $paymentMethod), 'check');

                Expense::create([
                    'voucher_type' => $byCheck ? Expense::TYPE_CHECK : Expense::TYPE_PETTY_CASH,
                    'check_date' => $byCheck ? $purchase->receipt_date : null,
                    'payee' => $supplierName,
                    'expense_date' => $purchase->receipt_date,
                    'category' => 'Supplies & Parts',
                    'description' => "Stock delivery payment for {$purchase->purchase_number}" . ($purchase->reference_number ? " (DR: {$purchase->reference_number})" : '') . " - {$supplierName}",
                    'amount' => $amountPaid,
                    'user_id' => $request->user()->id,
                ]);
            }

            return $purchase;
        });

        return redirect()->route('purchases.show', $purchase)->with('success', "Stock delivery {$purchase->purchase_number} recorded successfully! Inventory has been updated.");
    }

    public function show(Purchase $purchase): View
    {
        $purchase->load(['branch', 'supplier', 'user', 'items.product', 'accountPayable']);

        return view('purchases.show', compact('purchase'));
    }

    public function printBarcodes(Purchase $purchase): RedirectResponse
    {
        $productIds = $purchase->items->pluck('product_id')->unique()->implode(',');

        return redirect()->route('products.barcodes.print', ['ids' => $productIds]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = Purchase::with(['branch', 'supplier', 'user', 'items'])
            ->latest('receipt_date')
            ->get()
            ->map(fn (Purchase $p) => [
                $p->purchase_number,
                $p->reference_number ?? 'N/A',
                $p->receipt_date->format('Y-m-d'),
                $p->supplier_name,
                $p->branch?->name ?? 'N/A',
                $p->items->count(),
                $p->total_quantity,
                (float) $p->total_amount,
                (float) $p->amount_paid,
                (float) $p->balance,
                ucfirst($p->payment_status),
                $p->due_date?->format('Y-m-d') ?? 'N/A',
                $p->user?->name ?? 'System',
                $p->notes ?? '',
            ])->toArray();

        $headings = [
            'Receipt #',
            'Supplier DR / Ref #',
            'Delivery Date',
            'Supplier / Vendor',
            'Received At (Branch)',
            'Line Items',
            'Total Units',
            'Total Amount',
            'Amount Paid',
            'Balance Due',
            'Payment Status',
            'Due Date',
            'Received By',
            'Notes',
        ];

        return Excel::download(new ReportExport($rows, $headings, 'Stock Deliveries'), 'stock-deliveries.xlsx');
    }
}
