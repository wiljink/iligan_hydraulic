<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $sales = Sale::with(['customer', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $summary = Sale::whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total),0) as total, COALESCE(SUM(tax),0) as tax, COALESCE(SUM(discount),0) as discount')
            ->first();

        return view('reports.sales', compact('sales', 'summary', 'from', 'to'));
    }

    public function salesExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);

        $rows = Sale::with(['customer', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get()
            ->map(fn (Sale $s) => [
                $s->invoice_number,
                $s->created_at->format('Y-m-d H:i'),
                $s->customer?->name ?? 'Walk-in',
                $s->user?->name,
                (float) $s->subtotal,
                (float) $s->discount,
                (float) $s->tax,
                (float) $s->total,
                $s->payment_method,
                $s->status,
            ])->toArray();

        $headings = ['Invoice #', 'Date', 'Customer', 'Cashier', 'Subtotal', 'Discount', 'Tax', 'Total', 'Payment', 'Status'];

        return Excel::download(new ReportExport($rows, $headings, 'Sales Report'), 'sales-report.xlsx');
    }

    public function inventory(Request $request): View
    {
        $movements = StockMovement::with(['product', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->get('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->get('type')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('reports.inventory', compact('movements', 'products'));
    }

    public function inventoryExport(): BinaryFileResponse
    {
        $rows = StockMovement::with(['product', 'user'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (StockMovement $m) => [
                $m->created_at->format('Y-m-d H:i'),
                $m->product?->name,
                $m->product?->sku,
                $m->type,
                $m->quantity,
                $m->balance_after,
                $m->user?->name,
                $m->note,
            ])->toArray();

        $headings = ['Date', 'Product', 'SKU', 'Type', 'Qty Change', 'Balance After', 'User', 'Note'];

        return Excel::download(new ReportExport($rows, $headings, 'Inventory Movements'), 'inventory-report.xlsx');
    }

    public function products(Request $request): View
    {
        $products = Product::with(['category', 'brand', 'supplier'])
            ->when($request->get('stock') === 'low', fn ($q) => $q->whereColumn('current_stock', '<=', 'reorder_level'))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('reports.products', compact('products'));
    }

    public function productsExport(): BinaryFileResponse
    {
        $rows = Product::with(['category', 'brand', 'supplier'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                $p->sku,
                $p->name,
                $p->part_number,
                $p->category?->name,
                $p->brand?->name,
                $p->current_stock,
                $p->reorder_level,
                (float) $p->cost_price,
                (float) $p->selling_price,
                (float) ($p->current_stock * $p->cost_price),
            ])->toArray();

        $headings = ['SKU', 'Name', 'Part #', 'Category', 'Brand', 'Stock', 'Reorder Level', 'Cost', 'Price', 'Stock Value'];

        return Excel::download(new ReportExport($rows, $headings, 'Product Inventory'), 'products-report.xlsx');
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? \Illuminate\Support\Carbon::parse($request->get('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? \Illuminate\Support\Carbon::parse($request->get('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
