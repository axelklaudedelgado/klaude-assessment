<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ShopifyOAuthService
{
    public function isValidShopDomain(?string $shop): bool
    {
        $name = trim(strtolower($shop ?? ''));
        
        $name = preg_replace("/\A(https?\:\/\/)/", '', $name);
        
        if (strpos($name, ".") === false) {
            $name .= '.myshopify.com';
        }
        
        if (preg_match("/\A[a-zA-Z0-9][a-zA-Z0-9\-]*\.(myshopify\.com|myshopify\.io)\z/", $name)) {
            return true;
        }
        
        return false;
    }

    public function generateState(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function buildAuthorizationUrl(string $shop, string $state, string $clientId, string $scope, string $redirectUri): string
    {
        $params = [
            'client_id' => $clientId,
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ];
        
        return "https://{$shop}/admin/oauth/authorize?" . http_build_query($params);
    }

    public function verifyHmac(Request $request, string $secret): bool
    {
        $hmac = $request->input('hmac');
        if (!$hmac) return false;

        $params = $request->except(['hmac', 'signature']);
        ksort($params);

        $queryString = http_build_query($params);

        $calculatedHmac = hash_hmac(
            'sha256',
            $queryString,
            $secret,
        );

        return hash_equals($calculatedHmac, $hmac);
    }

    public function exchangeCodeForToken(string $shop, string $code, string $clientId, string $clientSecret): string
    {
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new \Exception('Token exchange failed: ' . $response->body());
        }

        $data = $response->json();
        if (!isset($data['access_token'])) {
            throw new \Exception('No access token received');
        }
                
        return $response['access_token'];
    }
}
