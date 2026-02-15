<?php

namespace App\Services;

use App\Models\Shop;

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

            $hasNextPage = $data['products']['pageInfo']['hasNextPage'] ?? false;
            $cursor = $data['products']['pageInfo']['endCursor'] ?? null;
        }

        return $synced;
    }

    public function syncOrders(?string $since = null): int
    {
        return 0;
    }
}