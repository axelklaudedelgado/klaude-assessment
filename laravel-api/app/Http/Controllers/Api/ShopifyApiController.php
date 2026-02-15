<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncProductsRequest;
use App\Http\Requests\SyncOrdersRequest;
use App\Models\Shop;
use App\Services\ShopifySyncService;
use Illuminate\Http\JsonResponse;

class ShopifyApiController extends Controller
{
    public function syncProducts(SyncProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $shop = Shop::where('shop_domain', $validated['shop_domain'])->first();
        
        if (!$shop->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Shop is not active',
            ], 403);
        }

        try {
            $sync = new ShopifySyncService($shop);
            $count = $sync->syncProducts();

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$count} products",
                'count' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncOrders(SyncOrdersRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $shop = Shop::where('shop_domain', $validated['shop_domain'])->first();
        
        if (!$shop->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Shop is not active',
            ], 403);
        }

        try {
            $sync = new ShopifySyncService($shop);
            $count = $sync->syncOrders($validated['since'] ?? null);

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$count} orders",
                'count' => $count,
                'since' => $validated['since'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
