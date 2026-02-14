<?php

namespace App\Services;

use Illuminate\Http\Request;

class ShopifyOAuthService
{
    public function isValidShopDomain(?string $shop): bool
    {
        return true;
    }

    public function generateState(): string
    {
        return '';
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
