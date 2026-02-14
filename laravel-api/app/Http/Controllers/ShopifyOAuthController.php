<?php

namespace App\Http\Controllers;
use App\Services\ShopifyOAuthService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyOAuthController extends Controller
{
    protected $service;

    public function __construct(ShopifyOAuthService $service)
    {
        $this->service = $service;
    }

    public function install(Request $request)
    {
        $shop = $request->input('shop');

        if (!$this->service->isValidShopDomain($shop)) {
            return response()->json(['error' => 'Invalid shop domain.'], 400);  
        }

        $state = $this->service->generateState();
        
        session([
            'oauth_state' => $state,
            'oauth_shop' => $shop,
        ]);

        $authUrl = $this->service->buildAuthorizationUrl(
            $shop,
            $state,
            config('shopify.api_key'),
            config('shopify.scopes'),
            config('shopify.oauth.redirect_uri')
        );

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $shop = $request->input('shop');

        if (!$this->service->isValidShopDomain($shop)) {
            return response()->json(['error' => 'Invalid shop domain'], 400);
        }

        if ($request->input('state') !== session('oauth_state')) {
            Log::warning('OAuth CSRF attempt detected', [
                'shop' => $shop,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid state parameter'], 403);
        }

        if ($shop !== session('oauth_shop')) {
            return response()->json(['error' => 'Shop mismatch'], 403);
        }

        if (!$this->service->verifyHmac($request, config('shopify.api_secret'))) {
            Log::warning('OAuth HMAC verification failed', [
                'shop' => $shop,
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'HMAC verification failed'], 403);
        }
    }
}
