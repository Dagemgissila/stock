<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SalesReturnsController;
use App\Http\Controllers\Api\PurchaseReturnsController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\CustomersController;
use App\Http\Controllers\Api\SuppliersController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentInController;
use App\Http\Controllers\Api\PaymentOutController;
use App\Http\Controllers\Api\PaymentModeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockHistoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\VariationController;

// Auth
Route::post('/auth/login',   [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);

Route::middleware(['auth.api'])->group(function () {
    Route::post('/auth/logout',  [AuthController::class, 'logout']);
    Route::get('/auth/profile',  [AuthController::class, 'profile']);
    Route::put('/auth/profile',  [AuthController::class, 'updateProfile']);

    Route::apiResource('products',   ProductController::class);
    Route::get('products/search',    [ProductController::class, 'search']);
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/tree',    [CategoryController::class, 'tree']);
    Route::apiResource('brands',     BrandController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('warehouses/{id}/stock',        [WarehouseController::class, 'stock']);
    Route::post('warehouses/{id}/assign-staff',[WarehouseController::class, 'assignStaff']);

    Route::apiResource('sales',             SalesController::class);
    Route::apiResource('purchases',         PurchaseController::class);
    Route::put('purchases/{id}/receive',    [PurchaseController::class, 'receive']);
    Route::apiResource('sales-returns',     SalesReturnsController::class);
    Route::apiResource('purchase-returns',  PurchaseReturnsController::class);
    Route::apiResource('quotations',        QuotationController::class);
    Route::put('quotations/{id}/convert',   [QuotationController::class, 'convert']);

    Route::apiResource('customers', CustomersController::class);
    Route::get('customers/{id}/ledger', [CustomersController::class, 'ledger']);
    Route::apiResource('suppliers', SuppliersController::class);
    Route::get('suppliers/{id}/ledger', [SuppliersController::class, 'ledger']);

    Route::apiResource('payments',      PaymentController::class);
    Route::post('payments/in',          [PaymentInController::class,  'store']);
    Route::post('payments/out',         [PaymentOutController::class, 'store']);
    Route::post('payments/stripe',      [PaymentController::class, 'stripeCharge']);
    Route::apiResource('payment-modes', PaymentModeController::class);
    Route::apiResource('expenses',          ExpenseController::class);
    Route::apiResource('expense-categories',ExpenseCategoryController::class);
    Route::apiResource('stock-adjustments', StockAdjustmentController::class);
    Route::apiResource('stock-transfers',   StockTransferController::class);
    Route::get('stock-history',             [StockHistoryController::class, 'index']);

    Route::get('reports/sales-summary',   [ReportController::class, 'salesSummary']);
    Route::get('reports/purchase-summary',[ReportController::class, 'purchaseSummary']);
    Route::get('reports/profit-loss',     [ReportController::class, 'profitLoss']);
    Route::get('reports/stock',           [ReportController::class, 'stockReport']);
    Route::get('reports/payments',        [ReportController::class, 'paymentReport']);
    Route::get('reports/expenses',        [ReportController::class, 'expenseReport']);
    Route::get('dashboard',               [ReportController::class, 'dashboard']);

    Route::get('pos/products',            [PosController::class, 'products']);
    Route::post('pos/orders',             [PosController::class, 'createOrder']);
    Route::post('pos/orders/{id}/payment',[PosController::class, 'payment']);

    Route::apiResource('users',       UsersController::class);
    Route::post('users/{id}/change-password', [UsersController::class, 'changePassword']);
    Route::apiResource('roles',       RolesController::class);
    Route::post('roles/{id}/sync-permissions',[RolesController::class,'syncPermissions']);
    Route::get('permissions',         [PermissionController::class, 'index']);
    Route::apiResource('taxes',       TaxController::class);
    Route::apiResource('units',       UnitController::class);
    Route::apiResource('variations',  VariationController::class);
});
