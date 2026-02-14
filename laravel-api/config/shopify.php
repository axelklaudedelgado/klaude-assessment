<?php

return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'api_secret' => env('SHOPIFY_API_SECRET'),
    'api_version' => env('SHOPIFY_API_VERSION', '2026-01'),
    'scopes' => env('SHOPIFY_SCOPES', 'read_products,write_products,read_orders,write_orders'),
];