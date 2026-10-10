<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Roles without dashboard access (e.g. cashiers) are sent to their own home page.
        if (! $request->user()->can('dashboard')) {
            return redirect()->route($request->user()->homeRoute());
        }

        $days = (int) $request->get('days', 14);
        if (! in_array($days, [7, 14, 30], true)) {
            $days = 14;
        }

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        // Today KPIs
        $todaySales = Sale::whereDate('created_at', $today)->where('status', 'completed');
        $todayTotal = (float) (clone $todaySales)->sum('total');
        $todayCount = (clone $todaySales)->count();
        $todayAvgTicket = $todayCount > 0 ? round($todayTotal / $todayCount, 2) : 0;

        // Yesterday comparison
        $yesterdaySales = Sale::whereDate('created_at', $yesterday)->where('status', 'completed');
        $yesterdayTotal = (float) (clone $yesterdaySales)->sum('total');

        // This Month KPIs
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $monthSalesQuery = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
        $monthTotal = (float) (clone $monthSalesQuery)->sum('total');
        $monthCount = (clone $monthSalesQuery)->count();
        $daysPassedInMonth = max(1, now()->day);
        $monthDailyAvg = round($monthTotal / $daysPassedInMonth, 2);

        // Month Gross Profit (from completed sale items)
        $monthCost = (float) SaleItem::whereHas('sale', function ($q) use ($startOfMonth, $endOfMonth) {
            $q->where('status', 'completed')->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
        })->sum(DB::raw('cost_price * quantity'));

        $monthProfit = max(0, $monthTotal - $monthCost);
        $monthProfitMargin = $monthTotal > 0 ? round(($monthProfit / $monthTotal) * 100, 1) : 0;

        // Inventory health
        $totalProducts = Product::where('is_active', true)->count();
        $inventoryCostValue = (float) Product::where('is_active', true)->sum(DB::raw('current_stock * cost_price'));
        $inventoryRetailValue = (float) Product::where('is_active', true)->sum(DB::raw('current_stock * selling_price'));

        $outOfStockCount = Product::where('is_active', true)->where('current_stock', '<=', 0)->count();
        $lowStockCount = Product::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->where('current_stock', '>', 0)
            ->count();

        $lowStockProducts = Product::with(['category', 'brand'])
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->where('is_active', true)
            ->orderBy('current_stock')
            ->limit(6)
            ->get();

        // Customer Receivables (Total pending credit balance)
        $totalReceivables = (float) Sale::where('status', 'completed')
            ->where('payment_method', 'credit')
            ->selectRaw('COALESCE(SUM('.Sale::BALANCE_SQL.'), 0) as balance')
            ->value('balance');

        // Accounts Payable (Pending bills to pay)
        $totalPayables = (float) AccountPayable::pending()
            ->selectRaw('COALESCE(SUM(amount - amount_paid), 0) as balance')
            ->value('balance');

        $overduePayablesCount = AccountPayable::overdue()->count();
        $overduePayablesAmount = (float) AccountPayable::overdue()
            ->selectRaw('COALESCE(SUM(amount - amount_paid), 0) as balance')
            ->value('balance');

        $pendingPayables = AccountPayable::pending()
            ->with('supplier')
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        // Trend Chart: Last N days (7, 14, or 30 days)
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $dailySalesRaw = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as sale_date, SUM(total) as revenue')
            ->groupBy('sale_date')
            ->pluck('revenue', 'sale_date');

        $dailyProfitRaw = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')->whereBetween('created_at', [$startDate, $endDate]);
        })
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('DATE(sales.created_at) as sale_date, SUM(sale_items.subtotal - (sale_items.cost_price * sale_items.quantity)) as profit')
            ->groupBy('sale_date')
            ->pluck('profit', 'sale_date');

        $trendLabels = [];
        $trendRevenue = [];
        $trendProfit = [];

        $period = CarbonPeriod::create($startDate, now());
        foreach ($period as $date) {
            $dateKey = $date->toDateString();
            $trendLabels[] = $date->format('M j');
            $trendRevenue[] = round((float) ($dailySalesRaw[$dateKey] ?? 0), 2);
            $trendProfit[] = round((float) ($dailyProfitRaw[$dateKey] ?? 0), 2);
        }

        // Payment Methods Breakdown (Last 30 Days)
        $paymentMethodsData = Sale::where('status', 'completed')
            ->whereBetween('created_at', [now()->subDays(30), now()])
            ->select('payment_method', DB::raw('SUM(total) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $paymentLabels = [];
        $paymentTotals = [];
        $paymentColors = [
            'cash' => '#10b981',    // Emerald
            'gcash' => '#3b82f6',   // Blue
            'bank' => '#8b5cf6',    // Purple
            'credit' => '#f59e0b',  // Amber
            'other' => '#6b7280',   // Gray
        ];
        $paymentChartColors = [];

        foreach ($paymentMethodsData as $pm) {
            $key = strtolower($pm->payment_method ?: 'cash');
            $label = match ($key) {
                'cash' => 'Cash',
                'gcash' => 'GCash',
                'bank' => 'Bank Transfer',
                'credit' => 'Charge / Credit',
                default => ucfirst($key),
            };
            $paymentLabels[] = $label;
            $paymentTotals[] = round((float) $pm->total_amount, 2);
            $paymentChartColors[] = $paymentColors[$key] ?? '#6b7280';
        }

        // Top Revenue Categories (Last 30 Days)
        $topCategoriesData = SaleItem::whereHas('sale', function ($q) {
            $q->where('status', 'completed')->whereBetween('created_at', [now()->subDays(30), now()]);
        })
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(sale_items.subtotal) as total_sales'))
            ->groupBy('categories.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $categoryLabels = $topCategoriesData->pluck('category_name')->toArray();
        $categoryTotals = $topCategoriesData->map(fn ($c) => round((float) $c->total_sales, 2))->toArray();

        // Top Moving Products (Last 30 Days)
        $topProducts = SaleItem::select('product_id', 'product_name')
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed')->whereBetween('created_at', [now()->subDays(30), now()]))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $maxQty = $topProducts->max('total_qty') ?: 1;

        // Recent Sales
        $recentSales = Sale::with(['user', 'customer'])->latest()->limit(6)->get();

        return view('dashboard', compact(
            'days',
            'todayTotal',
            'todayCount',
            'todayAvgTicket',
            'yesterdayTotal',
            'monthTotal',
            'monthCount',
            'monthDailyAvg',
            'monthProfit',
            'monthProfitMargin',
            'totalProducts',
            'inventoryCostValue',
            'inventoryRetailValue',
            'outOfStockCount',
            'lowStockCount',
            'lowStockProducts',
            'totalReceivables',
            'totalPayables',
            'overduePayablesCount',
            'overduePayablesAmount',
            'pendingPayables',
            'trendLabels',
            'trendRevenue',
            'trendProfit',
            'paymentLabels',
            'paymentTotals',
            'paymentChartColors',
            'categoryLabels',
            'categoryTotals',
            'topProducts',
            'maxQty',
            'recentSales'
        ));
    }
}
