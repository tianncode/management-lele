<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PondController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\FishCycleController;
use App\Http\Controllers\Api\FeedingController;
use App\Http\Controllers\Api\MortalityController;
use App\Http\Controllers\Api\SamplingController;
use App\Http\Controllers\Api\HarvestController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\OtherIncomeController;
use App\Http\Controllers\Api\CashTransactionController;
use Illuminate\Support\Facades\Route;


Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running.',
    ]);
});
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::apiResource('ponds', PondController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource(
    'product-categories',
    ProductCategoryController::class
);
Route::apiResource('suppliers', SupplierController::class);
Route::apiResource('customers', CustomerController::class);
Route::apiResource('fish-cycles', FishCycleController::class);
Route::apiResource('feedings', FeedingController::class);
Route::apiResource('mortalities', MortalityController::class);
Route::apiResource('samplings', SamplingController::class);
Route::apiResource('harvests', HarvestController::class);
Route::apiResource('purchases', PurchaseController::class);
Route::apiResource('sales', SaleController::class);
Route::get(
    'expense-categories',
    [ExpenseCategoryController::class, 'index']
);
Route::apiResource('expenses', ExpenseController::class);
Route::apiResource('other-incomes', OtherIncomeController::class);
Route::apiResource('cash-transactions', CashTransactionController::class);
