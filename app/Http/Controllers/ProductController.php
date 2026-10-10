<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\BarcodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $branch = Branch::current();

        $products = Product::with(['category', 'brand', 'supplier'])
            ->withBranchStock($branch)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->get('q').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term)
                        ->orWhere('barcode', 'like', $term)
                        ->orWhere('part_number', 'like', $term)
                        ->orWhere('oem_number', 'like', $term);
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->get('category_id')))
            ->when($request->get('stock') === 'low', fn ($q) => $q->whereColumn('current_stock', '<=', 'reorder_level'))
            ->when($request->get('stock') === 'out', fn ($q) => $q->where('current_stock', '<=', 0))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories', 'branch'));
    }

    public function create(): View
    {
        return view('products.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $product = Product::create($data);

        $opening = (int) $request->input('opening_stock', 0);
        if ($opening > 0) {
            $product->recordMovement(StockMovement::TYPE_OPENING, $opening, 'Opening stock');
        }

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load('branchStocks.branch');

        return view('products.edit', array_merge([
            'product' => $product,
            'branch' => Branch::current(),
        ], $this->formData()));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->saleItems()->exists()) {
            return back()->with('error', 'Cannot delete a product that has sales history. Mark it inactive instead.');
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:purchase,adjustment,wastage,return'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $quantity = (int) $data['quantity'];
        if (in_array($data['type'], ['wastage']) && $quantity > 0) {
            $quantity = -$quantity;
        }

        $branch = Branch::current();
        if ($product->stockAt($branch) + $quantity < 0) {
            return back()->with('error', 'Adjustment would result in negative stock at '.$branch?->name.'.');
        }

        $product->recordMovement($data['type'], $quantity, $data['note'] ?? null);

        return back()->with('success', 'Stock updated.');
    }

    public function barcode(Product $product, ?Request $request = null): Response
    {
        $request = $request ?? request();
        $code = $product->barcode ?: $product->sku;
        $scale = max(1, min(5, (int) $request->input('scale', $request->input('w', 2))));
        $height = max(20, min(200, (int) $request->input('height', $request->input('h', 60))));
        $format = strtolower((string) $request->input('format', 'png'));

        if ($format === 'svg') {
            $svg = BarcodeService::generateSvg($code, $scale, $height);

            return response($svg, 200)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'public, max-age=86400');
        }

        $png = BarcodeService::generatePng($code, $scale, $height);

        $response = response($png, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');

        if ($request->boolean('download')) {
            $filename = ($product->barcode ?: $product->sku).'.png';
            $response->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
        }

        return $response;
    }

    public function printBarcode(Product $product): View
    {
        return view('products.barcode-print', compact('product'));
    }

    public function printBarcodes(Request $request): View
    {
        $categories = Category::orderBy('name')->get();
        $selectedIds = collect(explode(',', (string) $request->get('ids', '')))
            ->filter()
            ->map(fn ($id) => (int) trim($id))
            ->values();

        $query = Product::with(['category', 'brand'])
            ->where('is_active', true);

        if ($selectedIds->isNotEmpty()) {
            $query->whereIn('id', $selectedIds);
        } elseif ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->get('q').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('barcode', 'like', $term)
                    ->orWhere('part_number', 'like', $term);
            });
        }

        $products = $query->orderBy('name')->get();

        return view('products.barcodes-bulk-print', compact('products', 'categories', 'selectedIds'));
    }

    public function generateBarcode(): JsonResponse
    {
        return response()->json([
            'barcode' => BarcodeService::generateUniqueEan13(),
        ]);
    }

    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'units' => config('pos.units'),
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $productId = $product?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,'.$productId],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode,'.$productId],
            'part_number' => ['nullable', 'string', 'max:100'],
            'oem_number' => ['nullable', 'string', 'max:5000'],
            'fitment' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'unit' => ['required', 'string', 'max:20'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
