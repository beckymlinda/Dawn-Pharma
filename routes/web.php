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
use App\Http\Controllers\ProductReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\IntegrityController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PlatformAuthController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ShopEntryController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SalesSummaryController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fresh CSRF token for long-open pages (used by public/js/session-guard.js to prevent 419 errors).
// Also keeps the session alive. Works logged in or out, so the login page never goes stale.
Route::get('/session/token', function () {
    return response()->json(['token' => csrf_token(), 'authenticated' => auth()->check()])
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
})->name('session.token');



// Server setup for hosting without a terminal (cPanel): token in .env, see SetupController.
// There is deliberately no route that wipes or re-seeds the database.
Route::get('/setup', [SetupController::class, 'show']);
Route::post('/setup/admin', [SetupController::class, 'createAdmin']);
Route::post('/setup/{action}', [SetupController::class, 'run'])->whereIn('action', ['migrate', 'clear-cache', 'storage-link']);

// Admin portal for the software owner (/platform)
Route::get('/platform/login', [PlatformAuthController::class, 'show'])->name('platform.login');
Route::post('/platform/login', [PlatformAuthController::class, 'login']);
Route::middleware(['auth', 'platform'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [PlatformController::class, 'index'])->name('index');
    Route::get('/tenants', [PlatformController::class, 'tenants'])->name('tenants.index');
    Route::get('/income', [\App\Http\Controllers\PlatformIncomeController::class, 'index'])->name('income');
    Route::get('/tenants/create', [PlatformController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [PlatformController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [PlatformController::class, 'show'])->name('tenants.show');
    Route::put('/tenants/{tenant}', [PlatformController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/extend', [PlatformController::class, 'extend'])->name('tenants.extend');
    Route::post('/tenants/{tenant}/suspend', [PlatformController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/reactivate', [PlatformController::class, 'reactivate'])->name('tenants.reactivate');
    Route::put('/tenants/{tenant}/users/{userId}/password', [PlatformController::class, 'updatePassword'])->whereNumber('userId')->name('tenants.password');
    Route::post('/tenants/{tenant}/impersonate', [ImpersonationController::class, 'start'])->name('tenants.impersonate');

    // Proofs of payment uploaded by shops
    Route::get('/payments', [\App\Http\Controllers\PaymentReviewController::class, 'index'])->name('payments.index');
    Route::post('/payments/{submission}/approve', [\App\Http\Controllers\PaymentReviewController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{submission}/reject', [\App\Http\Controllers\PaymentReviewController::class, 'reject'])->name('payments.reject');
    Route::get('/payments/{submission}/proof', [\App\Http\Controllers\PaymentReviewController::class, 'proof'])->name('payments.proof');
    Route::post('/payments/{submission}/resend-email', [\App\Http\Controllers\PaymentReviewController::class, 'resendEmail'])->name('payments.resend');
});

// Registers a resource controller with the matching view/add/edit/delete permissions
// (route names and URLs are the same as a plain Route::resource()).
$permittedResource = function (string $name, string $controller, array $permissions, array $middleware = []): void
{
    [$view, $add, $edit, $delete] = $permissions;
    // create is registered before show, otherwise "/{name}/create" is read as show with id "create"
    Route::resource($name, $controller)->only(['create', 'store'])->middleware(array_merge(["permission:$add"], $middleware));
    Route::resource($name, $controller)->only(['index', 'show'])->middleware(array_merge(["permission:$view"], $middleware));
    Route::resource($name, $controller)->only(['edit', 'update'])->middleware(array_merge(["permission:$edit"], $middleware));
    Route::resource($name, $controller)->only(['destroy'])->middleware(array_merge(["permission:$delete"], $middleware));
};

Route::middleware(['auth'])->group(function () use ($permittedResource) {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Keepalive route to maintain Laravel session without using dashboard URL
    Route::get('/keepalive', function () {
        return response()->json(['status' => 'alive']);
    })->name('keepalive');

    // POS screen, cart, registers (anyone allowed to sell)
    Route::middleware('permission:add_pos_sell')->group(function () {
        Route::get('/pos', [POSController::class, 'index'])->name('pos.redirect');
        Route::resource('pos', POSController::class)->only(['create','store']);
        Route::get('/pos/new', [POSController::class, 'newIndex'])->name('pos.new');
        Route::get('/pos/register/details', [POSController::class, 'registerDetails'])->name('pos.register.details');
        Route::get('/pos/register/{id}/details', [POSController::class, 'showRegister'])->name('pos.register.show');

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
        Route::post('pos/integrity/void', [IntegrityController::class, 'reportVoid'])->name('pos.integrity.void');
        Route::post('pos/finalize', [POSController::class, 'finalizeSale'])->name('pos.finalize')->middleware(['log.activity']);
        Route::get('pos/recent/sales', [POSController::class, 'getRecentSales'])->name('pos.recent.sales.data');
        Route::get('pos/recent', [POSController::class, 'recentSales'])->name('pos.recent.sales');
        Route::get('pos/refresh/products', [POSController::class, 'refreshProducts'])->name('pos.refresh.products');
    });
    Route::get('pos/sale/{sale}/edit-data', [POSController::class, 'getSaleEditData'])->name('pos.sale.edit-data')->middleware('permission:edit_pos_sell');
    Route::post('pos/sale/edit', [POSController::class, 'editSale'])->name('pos.edit.sale')->middleware('permission:edit_pos_sell,add_pos_sell');

    // Sales (SaleController limits cashiers to their own sales)
    Route::middleware('permission:view_pos_sell')->group(function () {
        Route::get('/sales/summary', [SalesSummaryController::class, 'index'])->name('sales.summary');
        Route::post('/sales/summary/payment-methods', [SalesSummaryController::class, 'getPaymentMethodSummary'])->name('sales.summary.payment-methods');
        Route::post('/sales/summary/validate', [SalesSummaryController::class, 'validatePaymentData'])->name('sales.summary.validate');
        Route::get('/sales/summary/test-payments', [SalesSummaryController::class, 'getAllPaymentsTest'])->name('sales.summary.test-payments');
    });
    Route::resource('sales', SaleController::class)->only(['create','store'])->middleware('permission:add_pos_sell');
    Route::resource('sales', SaleController::class)->only(['index','show'])->middleware('permission:view_pos_sell,add_pos_sell');
    Route::resource('sales', SaleController::class)->only(['edit'])->middleware('permission:edit_pos_sell');
    // Update and delete check their own permission, so a refused attempt is logged in Loss Prevention
    Route::resource('sales', SaleController::class)->only(['update'])->middleware('permission:edit_pos_sell,view_pos_sell,add_pos_sell');
    Route::resource('sales', SaleController::class)->only(['destroy'])->middleware('permission:delete_pos_sell,view_pos_sell,add_pos_sell');

    // Product purchase routes
    Route::get('/products/purchase', [ProductController::class, 'purchaseCreate'])->name('products.purchase.create')->middleware('permission:add_purchase');
    Route::post('/products/purchase', [ProductController::class, 'purchaseStore'])->name('products.purchase.store')->middleware('permission:add_purchase');

    // Resource routes for POS domain
    $permittedResource('units', UnitController::class, ['view_unit', 'add_unit', 'edit_unit', 'delete_unit'], ['log.activity']);
    $permittedResource('categories', CategoryController::class, ['view_category', 'add_category', 'edit_category', 'delete_category'], ['log.activity']);
    $permittedResource('brands', BrandController::class, ['view_brand', 'add_brand', 'edit_brand', 'delete_brand'], ['log.activity']);
    Route::get('/products/{product}/stock-info', [ProductController::class, 'getStockInfo'])->name('products.stock-info')->middleware('permission:view_product,add_pos_sell');
    Route::get('/products/download-template', [ProductController::class, 'downloadTemplate'])->name('products.download-template')->middleware('permission:add_product');
    Route::post('/products/bulk-upload', [ProductController::class, 'bulkUpload'])->name('products.bulk-upload')->middleware(['permission:add_product', 'log.activity']);
    $permittedResource('products', ProductController::class, ['view_product', 'add_product', 'edit_product', 'delete_product'], ['log.activity']);
    $permittedResource('purchases', PurchaseController::class, ['view_all_purchase', 'add_purchase', 'edit_purchase', 'delete_purchase'], ['log.activity']);

    // Reports
    Route::get('/reports/profit-loss', [ProfitLossController::class, 'index'])->name('reports.profit-loss')->middleware('permission:view_profit_loss_report');
    Route::get('/reports/register-report', [RegisterReportController::class, 'index'])->name('reports.register-report')->middleware('permission:view_register_report');
    Route::get('/reports/register-report/export', [RegisterReportController::class, 'exportCsv'])->name('reports.register-report.export')->middleware('permission:view_register_report');
    Route::get('/reports/stock-expiry', [StockExpiryReportController::class, 'index'])->name('reports.stock-expiry')->middleware('permission:view_stock_expiry_report');
    Route::get('/reports/stock-expiry/export/csv', [StockExpiryReportController::class, 'exportCsv'])->name('reports.stock-expiry.export.csv')->middleware('permission:view_stock_expiry_report');
    Route::get('/reports/products', [ProductReportController::class, 'index'])->name('reports.products.index')->middleware('permission:view_stock_report');
    Route::get('/reports/products/{product}', [ProductReportController::class, 'show'])->name('reports.products.show')->middleware('permission:view_stock_report');
    Route::get('/reports/low-stock', [StockReportController::class, 'lowStock'])->name('reports.low-stock')->middleware('permission:view_stock_report');

    // Stock Report
    Route::middleware('permission:view_stock_report')->group(function () {
        Route::get('/stock-report', [StockReportController::class, 'index'])->name('stock.report');
        Route::get('/stock-report/export/csv', [StockReportController::class, 'exportCsv'])->name('stock.report.export.csv');
        Route::get('/stock-report/export/excel', [StockReportController::class, 'exportExcel'])->name('stock.report.export.excel');
        Route::get('/stock-report/export/pdf', [StockReportController::class, 'exportPdf'])->name('stock.report.export.pdf');
        Route::get('/stock-history/{productId}', [StockReportController::class, 'productStockHistory'])->name('stock.history.product');
    });

    // Expenses (running costs used by the Profit & Loss report)
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('permission:access_all_expenses');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store')->middleware(['permission:add_expense', 'log.activity']);
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy')->middleware(['permission:delete_expense', 'log.activity']);

    // Stock Adjustments
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index')->middleware('permission:view_stock_adjustments');
    Route::middleware('permission:create_stock_adjustment')->group(function () {
        Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::get('/stock-adjustments/bulk', [StockAdjustmentController::class, 'bulk'])->name('stock-adjustments.bulk');
        Route::get('/stock-adjustments/bulk/template', [StockAdjustmentController::class, 'downloadTemplate'])->name('stock-adjustments.bulk.template');
        Route::post('/stock-adjustments/bulk', [StockAdjustmentController::class, 'bulkUpload'])->name('stock-adjustments.bulk.upload')->middleware(['log.activity']);
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store')->middleware(['log.activity']);
    });
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show')->middleware('permission:view_stock_adjustments');

    // Contacts
    Route::post('/contacts/store-supplier', [ContactController::class, 'storeSupplier'])->name('contacts.store.supplier')->middleware('permission:add_supplier');

    // User Management
    Route::prefix('admin')->name('admin.')->group(function () {
        // Users
        Route::get('/users', [UserManagementController::class, 'users'])->name('users.index')->middleware('permission:view_user');
        Route::post('/users', [UserManagementController::class, 'storeUser'])->name('users.store')->middleware(['permission:add_user', 'log.activity']);
        Route::get('/users/{user}/edit', [UserManagementController::class, 'editUser'])->name('users.edit')->middleware('permission:edit_user');
        Route::put('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update')->middleware(['permission:edit_user', 'log.activity']);
        Route::delete('/users/{user}', [UserManagementController::class, 'deleteUser'])->name('users.delete')->middleware(['permission:delete_user', 'log.activity']);

        // Roles
        Route::get('/roles', [UserManagementController::class, 'roles'])->name('roles.index')->middleware('permission:view_role');
        Route::post('/roles', [UserManagementController::class, 'storeRole'])->name('roles.store')->middleware('permission:add_role');
        Route::get('/roles/{role}/edit', [UserManagementController::class, 'editRole'])->name('roles.edit')->middleware('permission:edit_role');
        Route::put('/roles/{role}', [UserManagementController::class, 'updateRole'])->name('roles.update')->middleware('permission:edit_role');
        Route::delete('/roles/{role}', [UserManagementController::class, 'deleteRole'])->name('roles.delete')->middleware('permission:delete_role');
    });

    // Admin-only areas (same rule as the sidebar)
    Route::middleware('role:admin,super_admin')->group(function () {
        // The shop's subscription: days left, how to renew, payments
        Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/proof', [\App\Http\Controllers\BillingController::class, 'submit'])->name('billing.submit')->middleware('throttle:10,60');
        Route::get('/billing/proof/{submission}', [\App\Http\Controllers\BillingController::class, 'proof'])->name('billing.proof')->whereNumber('submission');

        // Activity Log (export is declared before {activityLog} so it is not mistaken for an id)
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
        Route::post('/activity-log/clear-old', [ActivityLogController::class, 'clearOldLogs'])->name('activity-log.clear-old');
        Route::get('/activity-log/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-log.show');
        Route::delete('/activity-log/{activityLog}', [ActivityLogController::class, 'destroy'])->name('activity-log.destroy');

        // Loss Prevention
        Route::get('/loss-prevention', [IntegrityController::class, 'index'])->name('integrity.index');
        Route::post('/loss-prevention/settings', [IntegrityController::class, 'updateSettings'])->name('integrity.settings');
        Route::get('/loss-prevention/{event}', [IntegrityController::class, 'show'])->name('integrity.show');
        Route::patch('/loss-prevention/{event}/review', [IntegrityController::class, 'review'])->name('integrity.review');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/settings', [NotificationController::class, 'updateSettings'])->name('notifications.settings');
        Route::post('/notifications/recipients', [NotificationController::class, 'storeRecipient'])->name('notifications.recipients.store');
        Route::patch('/notifications/recipients/{recipient}', [NotificationController::class, 'toggleRecipient'])->name('notifications.recipients.toggle');
        Route::delete('/notifications/recipients/{recipient}', [NotificationController::class, 'destroyRecipient'])->name('notifications.recipients.destroy');
        Route::post('/notifications/phones', [NotificationController::class, 'storePhone'])->name('notifications.phones.store');
        Route::patch('/notifications/phones/{phone}', [NotificationController::class, 'togglePhone'])->name('notifications.phones.toggle');
        Route::delete('/notifications/phones/{phone}', [NotificationController::class, 'destroyPhone'])->name('notifications.phones.destroy');
        Route::delete('/notifications/logs', [NotificationController::class, 'clearLogs'])->name('notifications.logs.clear');
        Route::post('/notifications/z-report/sms', [NotificationController::class, 'sendTestSms'])->name('notifications.zreport.sms');
        Route::get('/notifications/z-report/preview', [NotificationController::class, 'preview'])->name('notifications.zreport.preview');
        Route::post('/notifications/z-report/send', [NotificationController::class, 'sendTest'])->name('notifications.zreport.send');

        // Settings
        Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::get('/settings/branding', [\App\Http\Controllers\SettingController::class, 'branding'])->name('settings.branding');
        Route::get('/settings/payment-methods', [\App\Http\Controllers\SettingController::class, 'paymentMethods'])->name('settings.payment-methods');
        Route::post('/settings/payment-methods', [\App\Http\Controllers\SettingController::class, 'updatePaymentMethods'])->name('settings.payment-methods.update');
        Route::post('/settings/payment-methods/add', [\App\Http\Controllers\SettingController::class, 'storePaymentMethod'])->name('settings.payment-methods.store');
        Route::delete('/settings/payment-methods/{key}', [\App\Http\Controllers\SettingController::class, 'destroyPaymentMethod'])->name('settings.payment-methods.destroy');
    });

});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Shop link: /{slug} selects the shop in this browser and opens its login.
// A fallback route, so it only answers addresses no other page uses.
Route::fallback(ShopEntryController::class);
