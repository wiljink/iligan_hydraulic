@php
    $navLink = function (string $route, string $label, string $icon, $routePatterns = null) {
        $active = request()->routeIs($routePatterns ?? $route.'*');
        $classes = $active
            ? 'flex items-center gap-3 px-4 py-2.5 rounded-lg bg-orange-500 text-white text-sm font-medium'
            : 'flex items-center gap-3 px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white text-sm font-medium';
        return [$classes, $active];
    };
@endphp

<!-- Mobile overlay -->
<div x-show="mobileNavOpen" x-cloak class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="mobileNavOpen = false"></div>

<aside
    class="bg-gray-900 text-white w-64 flex-shrink-0 flex flex-col fixed inset-y-0 left-0 z-50 transform transition-transform lg:translate-x-0 lg:static print:hidden"
    :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="px-5 py-5 border-b border-gray-800">
        <a href="{{ route(auth()->user()->homeRoute()) }}" class="flex items-center gap-2">
            <span class="text-orange-500 text-xl">&#9881;</span>
            <span class="font-semibold leading-tight">Osmena Hydraulic<br><span class="text-xs text-gray-400 font-normal">Point of Sale</span></span>
        </a>
    </div>

    {{-- Active branch: stock, sales and deliveries apply to this branch --}}
    @php($currentBranch = \App\Models\Branch::current())
    <div class="px-5 py-3 border-b border-gray-800">
        <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1">Branch</p>
        @if (auth()->user()->canSwitchBranch())
            <form method="POST" action="{{ route('branch.switch') }}">
                @csrf
                <select name="branch_id" onchange="this.form.submit()" class="w-full rounded-md bg-gray-800 border-gray-700 text-white text-sm py-1.5">
                    @foreach (\App\Models\Branch::active()->orderBy('name')->get() as $b)
                        <option value="{{ $b->id }}" @selected($currentBranch?->id === $b->id)>{{ $b->label }}</option>
                    @endforeach
                </select>
            </form>
        @else
            <p class="text-sm font-medium text-orange-400">{{ $currentBranch?->label }}</p>
        @endif
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {{-- Menu items are filtered by role permissions (config/permissions.php). Admins see everything. --}}
        @can('dashboard')
            <a href="{{ route('dashboard') }}" class="{{ $navLink('dashboard', 'Dashboard', '')[0] }}">
                <span>&#128202;</span> Dashboard
            </a>
        @endcan
        @can('pos')
            <a href="{{ route('pos.index') }}" class="{{ $navLink('pos.index', 'POS', '', 'pos.*')[0] }}">
                <span>&#128179;</span> Point of Sale
            </a>
        @endcan
        @can('sales')
            <a href="{{ route('sales.index') }}" class="{{ $navLink('sales.index', 'Sales', '', 'sales.*')[0] }}">
                <span>&#129534;</span> Sales History
            </a>
        @endcan
        @can('customers')
            <a href="{{ route('customers.index') }}" class="{{ $navLink('customers.index', 'Customers', '', 'customers.*')[0] }}">
                <span>&#128101;</span> Customers
            </a>
        @endcan

        @can('inventory')
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Inventory</p>
            <a href="{{ route('products.index') }}" class="{{ $navLink('products.index', 'Products', '', 'products.index')[0] }}">
                <span>&#128230;</span> Products
            </a>
            <a href="{{ route('purchases.index') }}" class="{{ $navLink('purchases.index', 'Stock In & Deliveries', '', 'purchases.*')[0] }}">
                <span>🚚</span> Stock In / Deliveries
            </a>
            <a href="{{ route('products.barcodes.print') }}" class="{{ $navLink('products.barcodes.print', 'Print Barcodes', '', 'products.barcodes*')[0] }}">
                <span>🖨️</span> Print Barcodes
            </a>
            <a href="{{ route('categories.index') }}" class="{{ $navLink('categories.index', 'Categories', '', 'categories.*')[0] }}">
                <span>&#127991;</span> Categories
            </a>
            <a href="{{ route('brands.index') }}" class="{{ $navLink('brands.index', 'Brands', '', 'brands.*')[0] }}">
                <span>&#127959;</span> Brands
            </a>
            <a href="{{ route('suppliers.index') }}" class="{{ $navLink('suppliers.index', 'Suppliers', '', 'suppliers.*')[0] }}">
                <span>&#128666;</span> Suppliers
            </a>
            @can('transfers')
                <a href="{{ route('transfers.index') }}" class="{{ $navLink('transfers.index', 'Transfers', '', 'transfers.*')[0] }}">
                    <span>🔁</span> Stock Transfers
                </a>
            @endcan
            <a href="{{ route('imports.index') }}" class="{{ $navLink('imports.index', 'Import', '', 'imports.*')[0] }}">
                <span>&#128228;</span> Import Products
            </a>
        @endcan

        @can('reports')
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Reports</p>
            <a href="{{ route('reports.sales') }}" class="{{ $navLink('reports.sales', 'Sales Report', '', 'reports.sales*')[0] }}">
                <span>&#128200;</span> Sales Report
            </a>
            <a href="{{ route('reports.inventory') }}" class="{{ $navLink('reports.inventory', 'Inventory Report', '', 'reports.inventory*')[0] }}">
                <span>&#128203;</span> Inventory Report
            </a>
            <a href="{{ route('reports.products') }}" class="{{ $navLink('reports.products', 'Product Report', '', 'reports.products*')[0] }}">
                <span>&#128717;</span> Product Report
            </a>
        @endcan

        @can('accounting')
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Accounting</p>
            <a href="{{ route('reports.profit-loss') }}" class="{{ $navLink('reports.profit-loss', 'Profit & Loss', '', 'reports.profit-loss*')[0] }}">
                <span>&#128176;</span> Profit &amp; Loss
            </a>
            <a href="{{ route('expenses.index') }}" class="{{ $navLink('expenses.index', 'Expenses', '', 'expenses.*')[0] }}">
                <span>&#129534;</span> Expenses
            </a>
            <a href="{{ route('reports.receivables') }}" class="{{ $navLink('reports.receivables', 'Accounts Receivable', '', 'reports.receivables*')[0] }}">
                <span>&#128220;</span> Accounts Receivable
            </a>
            <a href="{{ route('payables.index') }}" class="{{ $navLink('payables.index', 'Accounts Payable', '', 'payables*')[0] }}">
                <span>&#128179;</span> Accounts Payable
            </a>
        @endcan

        @can('hr')
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">HR &amp; Payroll</p>
            <a href="{{ route('hr.employees.index') }}" class="{{ $navLink('hr.employees.index', 'Employees', '', 'hr.employees.*')[0] }}">
                <span>&#129489;</span> Employees
            </a>
            <a href="{{ route('hr.attendance.index') }}" class="{{ $navLink('hr.attendance.index', 'Attendance', '', 'hr.attendance.*')[0] }}">
                <span>&#128336;</span> Attendance
            </a>
            <a href="{{ route('hr.cash-advances.index') }}" class="{{ $navLink('hr.cash-advances.index', 'Cash Advances', '', 'hr.cash-advances.*')[0] }}">
                <span>&#128181;</span> Cash Advances
            </a>
            <a href="{{ route('hr.payroll.index') }}" class="{{ $navLink('hr.payroll.index', 'Payroll', '', 'hr.payroll.*')[0] }}">
                <span>&#128200;</span> Payroll
            </a>
        @endcan

        @canany(['settings', 'backup', 'branches', 'users'])
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Administration</p>
            @can('settings')
                <a href="{{ route('settings.edit') }}" class="{{ $navLink('settings.edit', 'Settings', '', 'settings.edit')[0] }}">
                    <span>&#9881;&#65039;</span> Settings
                </a>
            @endcan
            @can('backup')
                <a href="{{ route('backup.index') }}" class="{{ $navLink('backup.index', 'Backup', '', 'backup.*')[0] }}">
                    <span>&#128190;</span> Backup &amp; Restore
                </a>
            @endcan
            @can('branches')
                <a href="{{ route('branches.index') }}" class="{{ $navLink('branches.index', 'Branches', '', 'branches.*')[0] }}">
                    <span>🏬</span> Branches &amp; Warehouses
                </a>
            @endcan
            @can('users')
                <a href="{{ route('users.index') }}" class="{{ $navLink('users.index', 'Users', '', 'users.*')[0] }}">
                    <span>&#128100;</span> Users
                </a>
            @endcan
        @endcanany
    </nav>

    <div class="border-t border-gray-800 p-4">
        <div class="flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-gray-400 capitalize">{{ auth()->user()->role }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="text-gray-400 hover:text-white text-xs">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-white text-xs">Logout</button>
                </form>
            </div>
        </div>
    </div>
</aside>
