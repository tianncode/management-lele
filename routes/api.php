<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashTransactionController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseCategoryController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FeedingController;
use App\Http\Controllers\Api\FishCycleController;
use App\Http\Controllers\Api\HarvestController;
use App\Http\Controllers\Api\MortalityController;
use App\Http\Controllers\Api\OtherIncomeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PondController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SamplingController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running.',
    ]);
});

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Operational / Daily Management
    |--------------------------------------------------------------------------
    */

    Route::apiResource('ponds', PondController::class);

    Route::apiResource('products', ProductController::class);

    Route::apiResource('customers', CustomerController::class);

    Route::apiResource('fish-cycles', FishCycleController::class);

    Route::apiResource('feedings', FeedingController::class);

    Route::apiResource('mortalities', MortalityController::class);

    Route::apiResource('samplings', SamplingController::class);

    Route::apiResource('harvests', HarvestController::class);

    Route::apiResource('sales', SaleController::class);

    /*
    |--------------------------------------------------------------------------
    | Sale Payments
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/sales/{sale}/payments',
        [PaymentController::class, 'sale']
    );

    /*
    |--------------------------------------------------------------------------
    | Admin Only
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Master Data
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'product-categories',
            ProductCategoryController::class
        );

        Route::apiResource(
            'suppliers',
            SupplierController::class
        );

        /*
        |--------------------------------------------------------------------------
        | Purchases
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'purchases',
            PurchaseController::class
        );

        Route::post(
            '/purchases/{purchase}/payments',
            [PaymentController::class, 'purchase']
        );

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        */

        Route::get(
            'expense-categories',
            [ExpenseCategoryController::class, 'index']
        );

        Route::apiResource(
            'expenses',
            ExpenseController::class
        );

        /*
        |--------------------------------------------------------------------------
        | Other Income
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'other-incomes',
            OtherIncomeController::class
        );

        /*
        |--------------------------------------------------------------------------
        | Cash Management
        |--------------------------------------------------------------------------
        */

        Route::get(
            'cash-transactions/balance',
            [CashTransactionController::class, 'balance']
        );

        Route::apiResource(
            'cash-transactions',
            CashTransactionController::class
        );
    });
});
