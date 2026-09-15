<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Point of Sale
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/lookup', [PosController::class, 'lookup'])->name('pos.lookup');
    Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

    // Sales history
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->name('sales.void');

    // Inventory
    Route::resource('products', ProductController::class)->except(['show']);
    Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
    Route::get('/products/{product}/barcode', [ProductController::class, 'barcode'])->name('products.barcode');

    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/export', [ReportController::class, 'salesExport'])->name('sales.export');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/inventory/export', [ReportController::class, 'inventoryExport'])->name('inventory.export');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/products/export', [ReportController::class, 'productsExport'])->name('products.export');
    });

    // Import
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('/imports/template', [ImportController::class, 'template'])->name('imports.template');
    Route::post('/imports/products', [ImportController::class, 'import'])->name('imports.products');

    // Settings, users, backups - manager/admin only
    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/settings/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/settings/backup/run', [BackupController::class, 'run'])->name('backup.run');
        Route::get('/settings/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/settings/backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
        Route::post('/settings/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
