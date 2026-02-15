<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncProductsRequest;
use App\Http\Requests\SyncOrdersRequest;
use App\Http\Requests\ListProductsRequest;
use App\Http\Requests\ListOrdersRequest;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;
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

    public function getProducts(ListProductsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Product::query();

        if (!empty($validated['vendor'])) {
            $query->where('vendor', $validated['vendor']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $query->where('title', 'like', '%' . $validated['search'] . '%');
        }

        $products = $query->orderBy('shopify_updated_at', 'desc')
            ->paginate($validated['pageSize']);

        return response()->json($products);
    }

    public function getOrders(ListOrdersRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Order::query();

        if (!empty($validated['financial_status'])) {
            $query->where('financial_status', $validated['financial_status']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('shopify_created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->where('shopify_created_at', '<=', $validated['date_to']);
        }

        $orders = $query->orderBy('shopify_created_at', 'desc')
            ->paginate($validated['pageSize']);

        return response()->json($orders);
    }
}
