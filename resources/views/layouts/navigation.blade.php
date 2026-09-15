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
    class="bg-gray-900 text-white w-64 flex-shrink-0 flex flex-col fixed inset-y-0 left-0 z-50 transform transition-transform lg:translate-x-0 lg:static"
    :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="px-5 py-5 border-b border-gray-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <span class="text-orange-500 text-xl">&#9881;</span>
            <span class="font-semibold leading-tight">Iligan Hydraulics<br><span class="text-xs text-gray-400 font-normal">&amp; Auto Parts POS</span></span>
        </a>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="{{ $navLink('dashboard', 'Dashboard', '')[0] }}">
            <span>&#128202;</span> Dashboard
        </a>
        <a href="{{ route('pos.index') }}" class="{{ $navLink('pos.index', 'POS', '', 'pos.*')[0] }}">
            <span>&#128179;</span> Point of Sale
        </a>
        <a href="{{ route('sales.index') }}" class="{{ $navLink('sales.index', 'Sales', '', 'sales.*')[0] }}">
            <span>&#129534;</span> Sales History
        </a>

        <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Inventory</p>
        <a href="{{ route('products.index') }}" class="{{ $navLink('products.index', 'Products', '', 'products.*')[0] }}">
            <span>&#128230;</span> Products
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
        <a href="{{ route('customers.index') }}" class="{{ $navLink('customers.index', 'Customers', '', 'customers.*')[0] }}">
            <span>&#128101;</span> Customers
        </a>
        <a href="{{ route('imports.index') }}" class="{{ $navLink('imports.index', 'Import', '', 'imports.*')[0] }}">
            <span>&#128228;</span> Import Products
        </a>

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

        @if (auth()->user()?->isManager())
            <p class="px-4 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase">Administration</p>
            <a href="{{ route('settings.edit') }}" class="{{ $navLink('settings.edit', 'Settings', '', 'settings.edit')[0] }}">
                <span>&#9881;&#65039;</span> Settings
            </a>
            <a href="{{ route('backup.index') }}" class="{{ $navLink('backup.index', 'Backup', '', 'backup.*')[0] }}">
                <span>&#128190;</span> Backup &amp; Restore
            </a>
            @if (auth()->user()?->isAdmin())
                <a href="{{ route('users.index') }}" class="{{ $navLink('users.index', 'Users', '', 'users.*')[0] }}">
                    <span>&#128100;</span> Users
                </a>
            @endif
        @endif
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
