<?php

namespace App\Services;

use Illuminate\Http\Request;

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
        return '';
    }

    public function verifyHmac(Request $request, string $secret): bool
    {
        return false;
    }

    public function exchangeCodeForToken(string $shop, string $code, string $clientId, string $clientSecret): string
    {
        return '';
    }
}
