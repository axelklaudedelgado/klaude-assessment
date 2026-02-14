<?php

namespace App\Http\Controllers;
use App\Services\ShopifyOAuthService;

use Illuminate\Http\Request;

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
        //
    }
}
