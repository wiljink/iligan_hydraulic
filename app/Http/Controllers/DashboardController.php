<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $todaySales = Sale::whereDate('created_at', $today)->where('status', 'completed');
        $todayTotal = (clone $todaySales)->sum('total');
        $todayCount = (clone $todaySales)->count();

        $monthTotal = Sale::where('status', 'completed')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'reorder_level')
            ->where('is_active', true)
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        $lowStockCount = Product::whereColumn('current_stock', '<=', 'reorder_level')
            ->where('is_active', true)
            ->count();

        $topProducts = SaleItem::select('product_name')
            ->selectRaw('SUM(quantity) as total_qty')
            ->selectRaw('SUM(subtotal) as total_sales')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed')->whereBetween('created_at', [now()->subDays(30), now()]))
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $recentSales = Sale::with('user')->latest()->limit(8)->get();

        $totalProducts = Product::where('is_active', true)->count();
        $inventoryValue = Product::sum(DB::raw('current_stock * cost_price'));

        return view('dashboard', compact(
            'todayTotal', 'todayCount', 'monthTotal', 'lowStockProducts', 'lowStockCount',
            'topProducts', 'recentSales', 'totalProducts', 'inventoryValue'
        ));
    }
}
