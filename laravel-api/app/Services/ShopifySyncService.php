<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\Product;

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
        return 0;
    }

    private function extractNumericId(string $gid, string $type): ?string
    {
        $prefix = "gid://shopify/{$type}/";
        return str_starts_with($gid, $prefix) ? substr($gid, strlen($prefix)) : null;
    }
}