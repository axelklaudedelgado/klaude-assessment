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
    }

    public function callback(Request $request)
    {
        //
    }
}
