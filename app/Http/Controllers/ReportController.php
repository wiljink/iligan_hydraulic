<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                $s->customer?->tin_number ?? '',
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
        $movements = StockMovement::with(['branch', 'product', 'user'])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->get('branch_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->get('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->get('type')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $products = Product::orderBy('name')->get(['id', 'name']);

        $branches = Branch::orderBy('name')->get();

        return view('reports.inventory', compact('movements', 'products', 'branches'));
    }

    public function inventoryExport(): BinaryFileResponse
    {
        $rows = StockMovement::with(['branch', 'product', 'user'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (StockMovement $m) => [
                $m->created_at->format('Y-m-d H:i'),
                $m->branch?->name,
                $m->product?->name,
                $m->product?->sku,
                $m->type,
                $m->quantity,
                $m->balance_after,
                $m->user?->name,
                $m->note,
            ])->toArray();

        $headings = ['Date', 'Branch', 'Product', 'SKU', 'Type', 'Qty Change', 'Branch Balance After', 'User', 'Note'];

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

    public function profitLoss(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $figures = $this->profitLossFigures($from, $to);

        $daily = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as date, COALESCE(SUM(subtotal - discount),0) as net_sales, COALESCE(SUM(total),0) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $expensesByCategory = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('category, COALESCE(SUM(amount),0) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('reports.profit-loss', array_merge($figures, [
            'from' => $from,
            'to' => $to,
            'daily' => $daily,
            'expensesByCategory' => $expensesByCategory,
        ]));
    }

    public function profitLossExport(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->dateRange($request);
        $f = $this->profitLossFigures($from, $to);

        $rows = [
            ['Gross Sales', $f['grossSales']],
            ['Discounts', -$f['discounts']],
            ['Net Sales', $f['netSales']],
            ['Cost of Goods Sold', -$f['cogs']],
            ['Gross Profit', $f['grossProfit']],
            ['Operating Expenses', -$f['expenses']],
            ['Net Profit', $f['netProfit']],
            ['', ''],
            ['Sales Tax Collected (liability, not profit)', $f['tax']],
        ];

        $headings = ['Line Item', 'Amount'];

        return Excel::download(
            new ReportExport($rows, $headings, 'Profit & Loss'),
            'profit-loss-'.$from->toDateString().'-to-'.$to->toDateString().'.xlsx'
        );
    }

    private function profitLossFigures(Carbon $from, Carbon $to): array
    {
        $sales = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(subtotal),0) as gross_sales, COALESCE(SUM(discount),0) as discounts, COALESCE(SUM(tax),0) as tax')
            ->first();

        $cogs = (float) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(sale_items.cost_price * sale_items.quantity),0) as cogs')
            ->value('cogs');

        $expenses = (float) Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

        $grossSales = (float) $sales->gross_sales;
        $discounts = (float) $sales->discounts;
        $netSales = $grossSales - $discounts;
        $grossProfit = $netSales - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'grossSales' => $grossSales,
            'discounts' => $discounts,
            'netSales' => $netSales,
            'cogs' => $cogs,
            'grossProfit' => $grossProfit,
            'expenses' => $expenses,
            'netProfit' => $netProfit,
            'tax' => (float) $sales->tax,
        ];
    }

    public function receivables(Request $request): View
    {
        $query = Sale::with(['customer', 'user'])
            ->where('status', 'completed')
            ->where('payment_method', 'credit')
            ->whereRaw(Sale::BALANCE_SQL.' > 0')
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->get('customer_id')));

        $totalOutstanding = (clone $query)->get()->sum(fn (Sale $s) => $s->balance());

        $sales = $query->orderBy('created_at')->paginate(25)->withQueryString();

        $customers = \App\Models\Customer::orderBy('name')->get(['id', 'name']);

        return view('reports.receivables', compact('sales', 'totalOutstanding', 'customers'));
    }

    public function receivablesExport(): BinaryFileResponse
    {
        $rows = Sale::with(['customer', 'user'])
            ->where('status', 'completed')
            ->where('payment_method', 'credit')
            ->whereRaw(Sale::BALANCE_SQL.' > 0')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Sale $s) => [
                $s->invoice_number,
                $s->created_at->format('Y-m-d'),
                $s->customer?->name ?? 'Walk-in',
                $s->customer?->tin_number ?? '',
                (float) $s->total,
                (float) $s->amount_paid,
                (float) $s->balance(),
                (int) $s->created_at->diffInDays(),
            ])->toArray();

        $headings = ['Invoice #', 'Date', 'Customer', 'TIN', 'Total', 'Paid', 'Balance Due', 'Days Outstanding'];

        return Excel::download(new ReportExport($rows, $headings, 'Accounts Receivable'), 'accounts-receivable.xlsx');
    }

    private function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->get('from'))->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->get('to'))->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}
