<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\Product;
use App\Models\Order;

class ShopifySyncService
{
    private Shop $shop;
    private ShopifyGraphQLService $graphql;

    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
        $this->graphql = new ShopifyGraphQLService($shop);
    }

    public function syncProducts(): int
    {
        $cursor = null;
        $hasNextPage = true;
        $synced = 0;

        while ($hasNextPage) {
            $data = $this->graphql->fetchProducts($cursor);

            $edges = $data['products']['edges'] ?? [];

            foreach ($edges as $edge) {
                $node = $edge['node'] ?? null;

                if (!$node) {
                    continue;
                }

                $shopifyId = $this->extractNumericId($node['id'], 'Product');
                
                if (!$shopifyId) {
                    continue;
                }

                $price = $node['variants']['edges'][0]['node']['price'] ?? null;

                Product::updateOrCreate(
                    [
                        'shop_domain' => $this->shop->shop_domain,
                        'shopify_product_id' => $shopifyId,
                    ],
                    [
                        'title' => $node['title'] ?? '',
                        'vendor' => $node['vendor'] ?? '',
                        'status' => strtolower($node['status'] ?? 'draft'),
                        'price' => $price,
                        'shopify_updated_at' => $node['updatedAt'] ?? null,
                    ]
                );

                $synced++;
            }

            $hasNextPage = $data['products']['pageInfo']['hasNextPage'] ?? false;
            $cursor = $data['products']['pageInfo']['endCursor'] ?? null;
        }

        return $synced;
    }

    public function syncOrders(?string $since = null): int
    {
        $cursor = null;
        $hasNextPage = true;
        $synced = 0;

        while ($hasNextPage) {
            $data = $this->graphql->fetchOrders($cursor, 50, $since);
            
            $edges = $data['orders']['edges'] ?? [];
            
            foreach ($edges as $edge) {
                $node = $edge['node'] ?? null;
                
                if (!$node) {
                    continue;
                }
                
                $shopifyId = $this->extractNumericId($node['id'], 'Order');
                
                if (!$shopifyId) {
                    continue;
                }

                Order::updateOrCreate(
                    [
                        'shop_domain' => $this->shop->shop_domain,
                        'shopify_order_id' => $shopifyId,
                    ],
                    [
                        'order_number' => $node['name'] ?? '',
                        'total_price' => $node['totalPriceSet']['shopMoney']['amount'] ?? '0.00',
                        'financial_status' => $node['displayFinancialStatus'] ?? 'pending',
                        'fulfillment_status' => $node['displayFulfillmentStatus'] ?? 'unfulfilled',
                        'shopify_created_at' => $node['createdAt'] ?? null,
                    ]
                );

                $synced++;
            }

            $hasNextPage = $data['orders']['pageInfo']['hasNextPage'] ?? false;
            $cursor = $data['orders']['pageInfo']['endCursor'] ?? null;
        }

        return $synced;
    }

    private function extractNumericId(string $gid, string $type): ?string
    {
        $prefix = "gid://shopify/{$type}/";
        return str_starts_with($gid, $prefix) ? substr($gid, strlen($prefix)) : null;
    }
}