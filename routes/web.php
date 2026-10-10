<?php

use App\Http\Controllers\AccountPayableController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IclockController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ZKTeco ADMS push endpoint. No login: devices are accepted by registered serial number.
Route::prefix('iclock')->middleware('throttle:120,1')->group(function () {
    Route::get('/cdata', [IclockController::class, 'handshake']);
    Route::post('/cdata', [IclockController::class, 'receive']);
    Route::get('/getrequest', [IclockController::class, 'poll']);
    Route::post('/devicecmd', [IclockController::class, 'poll']);
});

Route::get('/', function () {
    return auth()->check() ? redirect()->route(auth()->user()->homeRoute()) : redirect()->route('login');
});

// Each module is gated by a permission from config/permissions.php (admins can access everything).
Route::middleware('auth')->group(function () {
    // Redirects roles without dashboard access to their home page
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Active branch picker (admins and users not assigned to a branch)
    Route::post('/branch/switch', [BranchController::class, 'switch'])->name('branch.switch');

    // Point of Sale
    Route::middleware('can:pos')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('/pos/lookup', [PosController::class, 'lookup'])->name('pos.lookup');
        Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    });

    // Sales history
    Route::middleware('can:sales')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/sales/{sale}/record-payment', [SaleController::class, 'recordPayment'])->name('sales.record-payment');
        Route::get('/payments/{payment}', [SaleController::class, 'showPayment'])->name('payments.show');
        Route::get('/credit-memos/{creditMemo}', [SaleController::class, 'showCreditMemo'])->name('credit-memos.show');
    });
    Route::middleware('can:sales.return')->group(function () {
        Route::get('/sales/{sale}/return', [SaleController::class, 'createReturn'])->name('sales.return.create');
        Route::post('/sales/{sale}/return', [SaleController::class, 'storeReturn'])->name('sales.return.store');
    });
    Route::post('/sales/{sale}/void', [SaleController::class, 'void'])->middleware('can:sales.void')->name('sales.void');

    // Inventory
    Route::middleware('can:inventory')->group(function () {
        Route::get('/products/barcodes/print', [ProductController::class, 'printBarcodes'])->name('products.barcodes.print');
        Route::get('/products/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generate-barcode');
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::get('/products/{product}/barcode', [ProductController::class, 'barcode'])->name('products.barcode');
        Route::get('/products/{product}/barcode/print', [ProductController::class, 'printBarcode'])->name('products.barcode.print');

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('brands', BrandController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

        // Stock In / Deliveries (Purchases)
        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/export', [PurchaseController::class, 'export'])->name('purchases.export');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('/purchases/{purchase}/barcodes', [PurchaseController::class, 'printBarcodes'])->name('purchases.barcodes');

        // Import
        Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('/imports/template', [ImportController::class, 'template'])->name('imports.template');
        Route::post('/imports/products', [ImportController::class, 'import'])->name('imports.products');
    });

    // Warehousing: stock transfers between branches
    Route::resource('transfers', StockTransferController::class)->only(['index', 'create', 'store', 'show'])
        ->middleware('can:transfers');

    Route::resource('customers', CustomerController::class)->only(['index', 'show', 'store', 'update', 'destroy'])
        ->middleware('can:customers');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::middleware('can:reports')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/sales/export', [ReportController::class, 'salesExport'])->name('sales.export');
            Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
            Route::get('/inventory/export', [ReportController::class, 'inventoryExport'])->name('inventory.export');
            Route::get('/products', [ReportController::class, 'products'])->name('products');
            Route::get('/products/export', [ReportController::class, 'productsExport'])->name('products.export');
        });

        Route::middleware('can:accounting')->group(function () {
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/profit-loss/export', [ReportController::class, 'profitLossExport'])->name('profit-loss.export');
            Route::get('/receivables', [ReportController::class, 'receivables'])->name('receivables');
            Route::get('/receivables/export', [ReportController::class, 'receivablesExport'])->name('receivables.export');
            Route::get('/payables', [AccountPayableController::class, 'index'])->name('payables');
            Route::get('/payables/export', [AccountPayableController::class, 'export'])->name('payables.export');
        });
    });

    // Accounting: Accounts Payable & Bills, Expenses
    Route::middleware('can:accounting')->group(function () {
        Route::get('/payables', [AccountPayableController::class, 'index'])->name('payables.index');
        Route::post('/payables', [AccountPayableController::class, 'store'])->name('payables.store');
        Route::post('/payables/{accountPayable}/record-payment', [AccountPayableController::class, 'recordPayment'])->name('payables.record-payment');
        Route::delete('/payables/{accountPayable}', [AccountPayableController::class, 'destroy'])->name('payables.destroy');
        Route::get('/payables/export', [AccountPayableController::class, 'export'])->name('payables.export');

        Route::resource('expenses', ExpenseController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    });

    // HR: employees, biometric attendance, cash advances, payroll
    Route::middleware('can:hr')->prefix('hr')->name('hr.')->group(function () {
        Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/import', [AttendanceController::class, 'import'])->name('attendance.import');
        Route::get('/attendance/employees/{employee}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/attendance/employees/{employee}/punches', [AttendanceController::class, 'storePunch'])->name('attendance.punches.store');
        Route::delete('/attendance/punches/{log}', [AttendanceController::class, 'destroyPunch'])->name('attendance.punches.destroy');
        Route::post('/attendance/devices', [AttendanceController::class, 'storeDevice'])->name('attendance.devices.store');
        Route::delete('/attendance/devices/{device}', [AttendanceController::class, 'destroyDevice'])->name('attendance.devices.destroy');

        Route::resource('cash-advances', CashAdvanceController::class)->only(['index', 'store', 'destroy']);

        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('/payroll/{payroll}/recompute', [PayrollController::class, 'recompute'])->name('payroll.recompute');
        Route::post('/payroll/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::delete('/payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::get('/payroll/{payroll}/payslips', [PayrollController::class, 'payslips'])->name('payroll.payslips');
        Route::put('/payroll-items/{item}', [PayrollController::class, 'adjust'])->name('payroll.items.adjust');
    });

    // Administration
    Route::middleware('can:settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::middleware('can:backup')->group(function () {
        Route::get('/settings/backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('/settings/backup/run', [BackupController::class, 'run'])->name('backup.run');
        Route::get('/settings/backup/{filename}/download', [BackupController::class, 'download'])->name('backup.download');
        Route::delete('/settings/backup/{filename}', [BackupController::class, 'destroy'])->name('backup.destroy');
        Route::post('/settings/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    });

    Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy'])
        ->middleware('can:branches');

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy'])
        ->middleware('can:users');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
