<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ShopifyApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sync/products', [ShopifyApiController::class, 'syncProducts']);
Route::post('/sync/orders', [ShopifyApiController::class, 'syncOrders']);