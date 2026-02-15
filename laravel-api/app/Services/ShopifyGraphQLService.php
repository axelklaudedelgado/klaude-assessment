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

                if ($statusCode === 403) {
                    throw new \Exception('Access token lacks required permissions');
                }

                if ($statusCode === 400) {
                    throw new \Exception('GraphQL query syntax is invalid');
                }

                if ($statusCode === 429 && $attempt < $this->maxRetries) {
                    sleep(2);
                    $attempt++;
                    continue;
                }

                if (in_array($statusCode, [502, 503, 504]) && $attempt < $this->maxRetries) {
                    $delay = $this->calculateBackoff($attempt);
                    usleep($delay);
                    $attempt++;
                    continue;
                }

                if ($statusCode >= 400) {
                    throw new \Exception("HTTP error {$statusCode}");
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

    private function calculateBackoff(int $attempt): int
    {
        $baseDelaySeconds = 1;
        $exponential = $baseDelaySeconds * pow(2, $attempt);
        $jitterSeconds = rand(0, 100) / 1000;
        return (int)(($exponential + $jitterSeconds) * 1000000);
    }

    protected function getApiPath(): string
    {
        $version = config('shopify.api_version');
        return "https://{$this->shop->shop_domain}/admin/api/{$version}/graphql.json";
    }

    public function fetchProducts(?string $cursor = null, int $limit = 50): array
    {
        $query = <<<'GRAPHQL'
        query GetProducts($cursor: String, $limit: Int!) {
          products(first: $limit, after: $cursor) {
            edges {
              cursor
              node {
                id
                title
                vendor
                status
                variants(first: 1) {
                  edges {
                    node {
                      price
                    }
                  }
                }
                updatedAt
              }
            }
            pageInfo {
              hasNextPage
              endCursor
            }
          }
        }
        GRAPHQL;

        return $this->query($query, [
            'cursor' => $cursor,
            'limit' => $limit,
        ]);
    }

    public function fetchOrders(?string $cursor = null, int $limit = 50, ?string $since = null): array
    {
        $query = <<<'GRAPHQL'
        query GetOrders($cursor: String, $limit: Int!, $query: String) {
          orders(first: $limit, after: $cursor, query: $query) {
            edges {
              cursor
              node {
                id
                name
                totalPriceSet {
                  shopMoney {
                    amount
                  }
                }
                displayFinancialStatus
                displayFulfillmentStatus
                createdAt
              }
            }
            pageInfo {
              hasNextPage
              endCursor
            }
          }
        }
        GRAPHQL;

        $queryString = $since ? "created_at:>={$since}" : null;

        return $this->query($query, [
            'cursor' => $cursor,
            'limit' => $limit,
            'query' => $queryString,
        ]);
    }
}
