<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Http;

class ShopifyGraphQLService
{
    private Shop $shop;
    private string $accessToken;

    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
        $this->accessToken = $shop->access_token;
    }

    protected function getApiPath(): string
    {
        $version = config('shopify.api_version');
        return "https://{$this->shop->shop_domain}/admin/api/{$version}/graphql.json";
    }
}
