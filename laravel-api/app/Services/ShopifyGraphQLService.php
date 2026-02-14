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

    public function query(string $query, array $variables = []): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($this->getApiPath(), [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            throw new \Exception('GraphQL request failed');
        }

        $body = $response->json();

        if (isset($body['errors'])) {
            $this->handleGraphQLErrors($body['errors']);
        }

        return $body['data'];
    }

    private function handleGraphQLErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $code = $error['extensions']['code'] ?? null;
            $message = $error['message'] ?? 'Unknown GraphQL error';

            if ($code === 'THROTTLED') {
                throw new \Exception('GraphQL request was throttled by Shopify');
            }

            throw new \Exception("GraphQL Error: {$message}");
        }
    }

    protected function getApiPath(): string
    {
        $version = config('shopify.api_version');
        return "https://{$this->shop->shop_domain}/admin/api/{$version}/graphql.json";
    }
}
