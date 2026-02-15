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
        return 0;
    }

    public function syncOrders(?string $since = null): int
    {
        return 0;
    }
}