<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
    private Shop $shop;
    private string $accessToken;
    private int $maxRetries = 2;

    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
        $this->accessToken = $shop->access_token;
    }

    public function query(string $query, array $variables = []): array
    {
        $attempt = 0;

        while ($attempt <= $this->maxRetries) {
            try { 
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $this->accessToken,
                    'Content-Type' => 'application/json',
                ])->post($this->getApiPath(), [
                    'query' => $query,
                    'variables' => $variables,
                ]);

                $statusCode = $response->status();

                if ($statusCode === 401) {
                    $this->handle401Unauthorized();
                    throw new \Exception('Access token is invalid or has been revoked');
                }
        
                $body = $response->json();
        
                if (isset($body['errors'])) {
                    $this->handleGraphQLErrors($body['errors']);
                }
        
                return $body['data'];
            } catch (\Exception $e) {
                if ($attempt >= $this->maxRetries) {
                    throw new \Exception("Max retries exceeded: " . $e->getMessage());
                }
                $attempt++;
            }
        }

        throw new \Exception("GraphQL request failed after {$this->maxRetries} retries");
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

    private function handle401Unauthorized(): void
    {
        Log::warning('Access token unauthorized', [
            'shop' => $this->shop->shop_domain,
            'shop_id' => $this->shop->id,
        ]);

        $this->shop->update(['is_active' => false]);
    }

    protected function getApiPath(): string
    {
        $version = config('shopify.api_version');
        return "https://{$this->shop->shop_domain}/admin/api/{$version}/graphql.json";
    }
}
