<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopifyOAuthController;

// Shopify OAuth routes
Route::get('/shopify/install', [ShopifyOAuthController::class, 'install'])
    ->name('shopify.install');

Route::get('/shopify/callback', [ShopifyOAuthController::class, 'callback'])
    ->name('shopify.callback');