<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RegisterReportController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\StockExpiryReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SalesSummaryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

 

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS and Sales
    Route::get('/pos', [POSController::class, 'index'])->name('pos.redirect');
    Route::resource('pos', POSController::class)->only(['create','store']);
    Route::get('/pos/new', [POSController::class, 'newIndex'])->name('pos.new');
    Route::get('/pos/register/details', [POSController::class, 'registerDetails'])->name('pos.register.details');
    
    // Register management (database-driven)
    Route::get('/pos/register/open', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/pos/register/open', [RegisterController::class, 'store'])->name('register.store')->middleware(['log.activity']);
    Route::get('/pos/register/close', [RegisterController::class, 'confirm'])->name('register.confirm');
    Route::patch('/pos/register/{register}', [RegisterController::class, 'update'])->name('register.update')->middleware(['log.activity']);
    
    // Cart and finalize
    Route::post('pos/cart/add', [POSController::class, 'addToCart'])->name('pos.cart.add');
    Route::post('pos/cart/remove', [POSController::class, 'removeFromCart'])->name('pos.cart.remove');
    Route::post('pos/cart/update', [POSController::class, 'updateCart'])->name('pos.cart.update');
    Route::post('pos/cart/clear', [POSController::class, 'clearCart'])->name('pos.cart.clear');
    Route::get('pos/cart/get', [POSController::class, 'getCart'])->name('pos.cart.get');
    Route::post('pos/finalize', [POSController::class, 'finalizeSale'])->name('pos.finalize')->middleware(['log.activity']);
    Route::get('pos/recent/sales', [POSController::class, 'getRecentSales'])->name('pos.recent.sales.data');
    Route::get('pos/recent', [POSController::class, 'recentSales'])->name('pos.recent.sales');
    Route::get('pos/sale/{sale}/edit-data', [POSController::class, 'getSaleEditData'])->name('pos.sale.edit-data');
    Route::post('pos/sale/edit', [POSController::class, 'editSale'])->name('pos.edit.sale');
    Route::get('pos/refresh/products', [POSController::class, 'refreshProducts'])->name('pos.refresh.products');
    Route::resource('sales', SaleController::class)->only(['index','create','store','show','edit','update','destroy']);
    
    // Sales Summary
    Route::get('/sales/summary', [SalesSummaryController::class, 'index'])->name('sales.summary');
    Route::post('/sales/summary/payment-methods', [SalesSummaryController::class, 'getPaymentMethodSummary'])->name('sales.summary.payment-methods');
    Route::post('/sales/summary/validate', [SalesSummaryController::class, 'validatePaymentData'])->name('sales.summary.validate');
    Route::get('/sales/summary/test-payments', [SalesSummaryController::class, 'getAllPaymentsTest'])->name('sales.summary.test-payments');

    // Product purchase routes
    Route::get('/products/purchase', [ProductController::class, 'purchaseCreate'])->name('products.purchase.create');
    Route::post('/products/purchase', [ProductController::class, 'purchaseStore'])->name('products.purchase.store');

    // Resource routes for POS domain
    Route::resource('units', UnitController::class)->middleware(['log.activity']);
    Route::resource('categories', CategoryController::class)->middleware(['log.activity']);
    Route::resource('brands', BrandController::class)->middleware(['log.activity']);
    Route::get('/products/{product}/stock-info', [ProductController::class, 'getStockInfo'])->name('products.stock-info')->middleware('auth');
    Route::resource('products', ProductController::class)->middleware(['log.activity']);
    Route::resource('purchases', PurchaseController::class)->middleware(['log.activity']);

    // Reports
    Route::get('/reports/profit-loss', [ProfitLossController::class, 'index'])->name('reports.profit-loss');
    Route::get('/reports/register-report', [RegisterReportController::class, 'index'])->name('reports.register-report');
    Route::get('/reports/register-report/export', [RegisterReportController::class, 'exportCsv'])->name('reports.register-report.export');
    Route::get('/reports/stock-expiry', [StockExpiryReportController::class, 'index'])->name('reports.stock-expiry');
    Route::get('/reports/stock-expiry/export/csv', [StockExpiryReportController::class, 'exportCsv'])->name('reports.stock-expiry.export.csv');
    Route::get('/reports/low-stock', [StockReportController::class, 'lowStock'])->name('reports.low-stock');
    
    // Stock Report
    Route::get('/stock-report', [StockReportController::class, 'index'])->name('stock.report');
    Route::get('/stock-report/export/csv', [StockReportController::class, 'exportCsv'])->name('stock.report.export.csv');
    Route::get('/stock-report/export/excel', [StockReportController::class, 'exportExcel'])->name('stock.report.export.excel');
    Route::get('/stock-report/export/pdf', [StockReportController::class, 'exportPdf'])->name('stock.report.export.pdf');
    Route::get('/stock-history/{productId}', [StockReportController::class, 'productStockHistory'])->name('stock.history.product');
    
    // Stock Adjustments
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store')->middleware(['log.activity']);
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    
    // Contacts
    Route::post('/contacts/store-supplier', [ContactController::class, 'storeSupplier'])->name('contacts.store.supplier');

    // User Management
    Route::prefix('admin')->name('admin.')->group(function () {
        // Users
        Route::get('/users', [UserManagementController::class, 'users'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'storeUser'])->name('users.store')->middleware(['log.activity']);
        Route::get('/users/{user}/edit', [UserManagementController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update')->middleware(['log.activity']);
        Route::delete('/users/{user}', [UserManagementController::class, 'deleteUser'])->name('users.delete')->middleware(['log.activity']);
        
        // Roles
        Route::get('/roles', [UserManagementController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [UserManagementController::class, 'storeRole'])->name('roles.store');
        Route::get('/roles/{role}/edit', [UserManagementController::class, 'editRole'])->name('roles.edit');
        Route::put('/roles/{role}', [UserManagementController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [UserManagementController::class, 'deleteRole'])->name('roles.delete');
    });

    // Activity Log
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('/activity-log/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-log.show');
    Route::delete('/activity-log/{activityLog}', [ActivityLogController::class, 'destroy'])->name('activity-log.destroy');
    Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
    Route::post('/activity-log/clear-old', [ActivityLogController::class, 'clearOldLogs'])->name('activity-log.clear-old');

});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
