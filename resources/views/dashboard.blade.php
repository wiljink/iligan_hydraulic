<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Dashboard &amp; Analytics</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ now()->format('l, F j, Y') }} &bull; Real-time POS performance, revenue trends &amp; inventory monitoring
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Time Range Filter -->
                <div class="bg-gray-100 p-0.5 rounded-lg flex items-center text-xs font-semibold">
                    <a
                        href="{{ route('dashboard', ['days' => 7]) }}"
                        class="px-3 py-1.5 rounded-md transition {{ $days === 7 ? 'bg-white text-orange-600 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        7 Days
                    </a>
                    <a
                        href="{{ route('dashboard', ['days' => 14]) }}"
                        class="px-3 py-1.5 rounded-md transition {{ $days === 14 ? 'bg-white text-orange-600 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        14 Days
                    </a>
                    <a
                        href="{{ route('dashboard', ['days' => 30]) }}"
                        class="px-3 py-1.5 rounded-md transition {{ $days === 30 ? 'bg-white text-orange-600 shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        30 Days
                    </a>
                </div>

                <!-- Quick Action Shortcuts -->
                <a
                    href="{{ route('pos.index') }}"
                    class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>⚡</span>
                    <span>New Sale (POS)</span>
                </a>
                <a
                    href="{{ route('payables.index') }}"
                    class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <span>🧾</span>
                    <span>Payables</span>
                    @if ($overduePayablesCount > 0)
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    @endif
                </a>
                <a
                    href="{{ route('purchases.create') }}"
                    class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                >
                    <span>🚚</span>
                    <span>Stock In</span>
                </a>
                <a
                    href="{{ route('products.create') }}"
                    class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-xs font-semibold shadow-xs transition"
                >
                    + Add Product
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-6">
        <!-- Today's Sales -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-orange-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Today's Sales</span>
                <span class="w-8 h-8 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-sm font-bold">💳</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($todayTotal) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>{{ $todayCount }} transaction(s)</span>
                    <span class="font-medium text-gray-600">Avg: {{ money($todayAvgTicket) }}</span>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-gray-400">
                <span>Yesterday: {{ money($yesterdayTotal) }}</span>
            </div>
        </div>

        <!-- This Month & Profit -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-emerald-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">This Month Sales</span>
                <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">📈</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($monthTotal) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100">
                    <span class="text-emerald-700 font-semibold">Est. Profit: {{ money($monthProfit) }}</span>
                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 font-bold">{{ $monthProfitMargin }}% margin</span>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-gray-400">
                <span>Daily Run Rate: {{ money($monthDailyAvg) }} / day</span>
            </div>
        </div>

        <!-- Customer Receivables (A/R) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 border-indigo-500 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Receivables (A/R)</span>
                <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">💰</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($totalReceivables) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>Customer Credits</span>
                    <a href="{{ route('reports.receivables') }}" class="text-indigo-600 hover:underline font-semibold text-[11px]">Manage &rarr;</a>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-gray-400 flex items-center justify-between">
                <span>Uncollected sales balance</span>
            </div>
        </div>

        <!-- Accounts Payable (A/P) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 {{ $overduePayablesCount > 0 ? 'border-red-500' : ($totalPayables > 0 ? 'border-amber-500' : 'border-emerald-500') }} flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Accounts Payable (A/P)</span>
                <span class="w-8 h-8 rounded-full {{ $overduePayablesCount > 0 ? 'bg-red-50 text-red-600' : ($totalPayables > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600') }} flex items-center justify-center text-sm font-bold">🧾</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($totalPayables) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100">
                    @if ($overduePayablesCount > 0)
                        <span class="text-red-600 font-bold flex items-center gap-1">
                            <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                            {{ $overduePayablesCount }} Overdue
                        </span>
                    @else
                        <span class="text-emerald-600 font-semibold">0 Overdue</span>
                    @endif
                    <a href="{{ route('payables.index') }}" class="text-orange-600 hover:underline font-semibold text-[11px]">Payables &rarr;</a>
                </div>
            </div>
            <div class="mt-2 text-[11px] text-gray-400 flex items-center justify-between">
                <span>Overdue:</span>
                <span class="font-bold {{ $overduePayablesCount > 0 ? 'text-red-600' : 'text-gray-700' }}">{{ money($overduePayablesAmount) }}</span>
            </div>
        </div>

        <!-- Inventory Valuation & Stock Alerts -->
        <div class="bg-white rounded-xl shadow-xs p-5 border-l-4 {{ ($lowStockCount + $outOfStockCount) > 0 ? 'border-blue-500' : 'border-gray-300' }} flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Inventory Value</span>
                <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">📦</span>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 tracking-tight">{{ money($inventoryCostValue) }}</p>
                <div class="flex items-center justify-between text-xs mt-2 pt-2 border-t border-gray-100 text-gray-500">
                    <span>{{ number_format($totalProducts) }} Products</span>
                    <a href="{{ route('products.index') }}" class="text-blue-600 hover:underline font-semibold text-[11px]">Catalog &rarr;</a>
                </div>
            </div>
            <div class="mt-2 text-[11px] flex items-center justify-between">
                <span class="text-gray-400">Retail: {{ money($inventoryRetailValue) }}</span>
                @if (($lowStockCount + $outOfStockCount) > 0)
                    <span class="font-bold text-amber-600">{{ $lowStockCount + $outOfStockCount }} low stock</span>
                @else
                    <span class="text-emerald-600 font-semibold">Healthy</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Analytics & Graphs Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Main Line/Area Chart: Sales & Profit Trend -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-xs p-5 border border-gray-100">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Revenue &amp; Gross Profit Trend</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Daily sales performance and estimated profits over the last {{ $days }} days
                    </p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-orange-600">
                        <span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span>
                        Revenue
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-600">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                        Gross Profit
                    </span>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full" style="height: 310px;">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Doughnut Chart: Payment Methods Breakdown -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100 flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-900">Payment Breakdown</h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    Share of payment channels (Last 30 Days)
                </p>
            </div>

            <div class="relative my-4 flex items-center justify-center" style="height: 200px;">
                @if (count($paymentTotals) > 0)
                    <canvas id="paymentMethodChart"></canvas>
                @else
                    <div class="text-center text-gray-400 py-8">
                        <span class="text-3xl block mb-2">💳</span>
                        <p class="text-xs font-medium text-gray-500">No payment data yet</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Complete transactions at POS to view payment distribution.</p>
                    </div>
                @endif
            </div>

            <!-- Payment Summary List -->
            @if (count($paymentTotals) > 0)
                <div class="space-y-1.5 pt-3 border-t border-gray-100 text-xs">
                    @php $sumPayments = array_sum($paymentTotals) ?: 1; @endphp
                    @foreach ($paymentLabels as $idx => $label)
                        @php
                            $val = $paymentTotals[$idx] ?? 0;
                            $pct = round(($val / $sumPayments) * 100, 1);
                            $color = $paymentChartColors[$idx] ?? '#6b7280';
                        @endphp
                        <div class="flex items-center justify-between text-gray-600">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background-color: {{ $color }};"></span>
                                <span class="font-medium text-gray-800">{{ $label }}</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900">{{ money($val) }}</span>
                                <span class="text-[11px] text-gray-400 w-10 text-right">({{ $pct }}%)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Secondary Row: Category Sales + Top Movers + Low Stock -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Top Revenue Categories (Horizontal Bar Chart) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Top Categories</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Revenue leaders by product line (Last 30 Days)</p>
                </div>
                <a href="{{ route('categories.index') }}" class="text-xs text-orange-600 hover:underline font-semibold">View All</a>
            </div>

            <div class="relative w-full" style="height: 250px;">
                @if (count($categoryTotals) > 0)
                    <canvas id="categorySalesChart"></canvas>
                @else
                    <div class="h-full flex flex-col items-center justify-center text-center text-gray-400 py-6">
                        <span class="text-3xl block mb-2">🏷️</span>
                        <p class="text-xs font-medium text-gray-500">No category sales yet</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Sales grouped by product categories will appear here.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Top Movers (Fastest Selling Products) -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Top Moving Items</h3>
                    <p class="text-xs text-gray-500 mt-0.5">By quantity sold (Last 30 Days)</p>
                </div>
                <a href="{{ route('reports.products') }}" class="text-xs text-orange-600 hover:underline font-semibold">Full Report</a>
            </div>

            <div class="space-y-3.5">
                @forelse ($topProducts as $idx => $row)
                    @php $pct = min(100, round(($row->total_qty / $maxQty) * 100)); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-gray-900 truncate max-w-[180px] flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full bg-orange-100 text-orange-800 text-[10px] flex items-center justify-center font-bold">{{ $idx + 1 }}</span>
                                <span class="truncate">{{ $row->product_name }}</span>
                            </span>
                            <div class="text-right">
                                <span class="font-bold text-gray-900">{{ (int) $row->total_qty }} pcs</span>
                                <span class="text-gray-400 text-[11px] block">{{ money($row->total_sales) }}</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-orange-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-8">
                        <span class="text-3xl block mb-2">📦</span>
                        <p class="text-xs font-medium text-gray-500">No sales transactions yet</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Top performing products will rank here.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Critical Low Stock Alerts -->
        <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Reorder Priority</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Items currently at or below reorder level</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('purchases.create') }}" class="text-xs text-orange-600 hover:underline font-bold">+ Receive Stock</a>
                    <span class="text-gray-300">&bull;</span>
                    <a href="{{ route('products.index', ['stock' => 'low']) }}" class="text-xs text-gray-500 hover:underline font-semibold">Manage Stock</a>
                </div>
            </div>

            <ul class="space-y-2.5">
                @forelse ($lowStockProducts as $prod)
                    <li class="flex items-center justify-between text-xs border-b border-gray-100 pb-2 last:border-0">
                        <div class="truncate max-w-[160px]">
                            <a href="{{ route('products.edit', $prod) }}" class="font-bold text-gray-900 hover:text-orange-600 truncate block">
                                {{ $prod->name }}
                            </a>
                            <span class="text-[10px] text-gray-400 font-mono">{{ $prod->sku }}</span>
                        </div>
                        <div class="text-right flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold {{ $prod->current_stock <= 0 ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $prod->current_stock }} / {{ $prod->reorder_level }} {{ $prod->unit }}
                            </span>
                            <a
                                href="{{ route('products.edit', $prod) }}"
                                class="text-gray-400 hover:text-orange-600 text-xs"
                                title="Edit product / adjust stock"
                            >
                                ✏️
                            </a>
                        </div>
                    </li>
                @empty
                    <li class="text-center text-gray-400 py-8">
                        <span class="text-3xl block mb-2">✅</span>
                        <p class="text-xs font-medium text-gray-600">All inventory levels healthy</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">No products require immediate restocking.</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- Accounts Needed to be Paid (Actionable Payables Widget) -->
    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-gray-900">Accounts Needed to be Paid</h3>
                    @if ($overduePayablesCount > 0)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800">
                            {{ $overduePayablesCount }} Overdue ({{ money($overduePayablesAmount) }})
                        </span>
                    @elseif ($totalPayables > 0)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                            {{ money($totalPayables) }} Pending
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                    Supplier invoices and vendor accounts requiring payment settlement
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('payables.index') }}"
                    class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-md text-xs font-semibold shadow-xs flex items-center gap-1.5 transition"
                >
                    <span>🧾</span>
                    <span>Manage Payables</span>
                </a>
            </div>
        </div>

        @if ($pendingPayables->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-600 border-b">
                        <tr>
                            <th class="py-2.5 px-3">Bill #</th>
                            <th class="py-2.5 px-3">Vendor / Supplier</th>
                            <th class="py-2.5 px-3">Bill Date</th>
                            <th class="py-2.5 px-3">Due Date &amp; Urgency</th>
                            <th class="py-2.5 px-3 text-right">Total Amount</th>
                            <th class="py-2.5 px-3 text-right">Paid So Far</th>
                            <th class="py-2.5 px-3 text-right">Balance Due</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pendingPayables as $payable)
                            @php
                                $isOverdue = $payable->is_overdue;
                                $daysOverdue = $payable->days_overdue;
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition {{ $isOverdue ? 'bg-red-50/30' : '' }}">
                                <td class="py-2.5 px-3 font-mono font-bold text-gray-900">
                                    <a href="{{ route('payables.index', ['search' => $payable->bill_number]) }}" class="text-orange-600 hover:underline">
                                        {{ $payable->bill_number }}
                                    </a>
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900">{{ $payable->supplier?->name ?? $payable->vendor_name }}</div>
                                    @if ($payable->supplier && $payable->vendor_name && $payable->vendor_name !== $payable->supplier->name)
                                        <span class="text-[11px] text-gray-400">Attn: {{ $payable->vendor_name }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-gray-500">
                                    {{ $payable->bill_date ? $payable->bill_date->format('M j, Y') : '—' }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @if ($payable->due_date)
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-medium text-gray-700">{{ $payable->due_date->format('M j, Y') }}</span>
                                            @if ($isOverdue)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">
                                                    {{ $daysOverdue }}d Overdue
                                                </span>
                                            @elseif ($payable->due_date->isToday())
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    Due Today
                                                </span>
                                            @elseif ($payable->due_date->diffInDays(now()) <= 7)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                    In {{ (int) now()->diffInDays($payable->due_date) }}d
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">No due date</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-medium text-gray-700">
                                    {{ money($payable->amount) }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-gray-500">
                                    {{ money($payable->amount_paid) }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black {{ $isOverdue ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ money($payable->balance) }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <a
                                        href="{{ route('payables.index', ['search' => $payable->bill_number]) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded text-[11px] font-bold transition"
                                    >
                                        <span>Pay Bill</span>
                                        <span>&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-gray-400">
                <span class="text-3xl block mb-2">🎉</span>
                <p class="text-xs font-semibold text-gray-700">No pending accounts payable!</p>
                <p class="text-[11px] text-gray-400 mt-0.5">All supplier bills have been fully paid. There are no accounts currently needing payment.</p>
            </div>
        @endif
    </div>

    <!-- Recent Sales Table Activity Feed -->
    <div class="bg-white rounded-xl shadow-xs p-5 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-gray-900">Recent Transactions</h3>
                <p class="text-xs text-gray-500 mt-0.5">Latest sales processed across cashiers</p>
            </div>
            <a href="{{ route('sales.index') }}" class="text-xs text-orange-600 hover:underline font-semibold">
                View All Sales &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-600 border-b">
                    <tr>
                        <th class="py-2.5 px-3">Invoice #</th>
                        <th class="py-2.5 px-3">Date / Time</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Cashier</th>
                        <th class="py-2.5 px-3">Payment</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentSales as $sale)
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="py-2.5 px-3 font-mono font-bold">
                                <a href="{{ route('sales.show', $sale) }}" class="text-orange-600 hover:underline">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="py-2.5 px-3 text-gray-500">
                                {{ $sale->created_at->format('M j, Y • g:ia') }}
                            </td>
                            <td class="py-2.5 px-3 font-medium text-gray-800">
                                {{ $sale->customer?->name ?? 'Walk-in Customer' }}
                            </td>
                            <td class="py-2.5 px-3 text-gray-600">
                                {{ $sale->user?->name ?? 'System' }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    {{ $sale->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $sale->payment_method === 'gcash' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $sale->payment_method === 'bank' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $sale->payment_method === 'credit' ? 'bg-amber-100 text-amber-800' : '' }}
                                ">
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    {{ $sale->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $sale->status === 'voided' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ $sale->status }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-black text-gray-900">
                                {{ money($sale->total) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">
                                No sales transactions recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart.js Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.Chart === 'undefined') {
                console.warn('Chart.js is not loaded.');
                return;
            }

            // 1. Sales & Gross Profit Trend Chart
            const salesCanvas = document.getElementById('salesTrendChart');
            if (salesCanvas) {
                const trendCtx = salesCanvas.getContext('2d');
                new window.Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: @json($trendLabels),
                        datasets: [
                            {
                                label: 'Revenue',
                                data: @json($trendRevenue),
                                borderColor: '#ea580c',
                                backgroundColor: 'rgba(234, 88, 12, 0.08)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#ea580c',
                            },
                            {
                                label: 'Gross Profit',
                                data: @json($trendProfit),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                fill: true,
                                tension: 0.35,
                                borderWidth: 2.5,
                                pointRadius: 3,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#10b981',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        if (context.parsed.y !== null) {
                                            label += '₱ ' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: { size: 11 }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 11 },
                                    callback: function(value) {
                                        return '₱ ' + (value >= 1000 ? (value / 1000).toFixed(0) + 'k' : value);
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // 2. Payment Method Doughnut Chart
            const paymentCanvas = document.getElementById('paymentMethodChart');
            if (paymentCanvas && @json(count($paymentTotals)) > 0) {
                const paymentCtx = paymentCanvas.getContext('2d');
                new window.Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($paymentLabels),
                        datasets: [{
                            data: @json($paymentTotals),
                            backgroundColor: @json($paymentChartColors),
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) label += ': ';
                                        let val = context.parsed;
                                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        let pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                        label += '₱ ' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (' + pct + '%)';
                                        return label;
                                    }
                                }
                            }
                        },
                        cutout: '68%'
                    }
                });
            }

            // 3. Top Revenue Categories Horizontal Bar Chart
            const categoryCanvas = document.getElementById('categorySalesChart');
            if (categoryCanvas && @json(count($categoryTotals)) > 0) {
                const catCtx = categoryCanvas.getContext('2d');
                new window.Chart(catCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($categoryLabels),
                        datasets: [{
                            label: 'Revenue',
                            data: @json($categoryTotals),
                            backgroundColor: 'rgba(234, 88, 12, 0.85)',
                            hoverBackgroundColor: '#ea580c',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: ₱ ' + context.parsed.x.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 10 },
                                    callback: function(value) {
                                        return '₱ ' + (value >= 1000 ? (value / 1000).toFixed(0) + 'k' : value);
                                    }
                                }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 11, weight: 600 } }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>
