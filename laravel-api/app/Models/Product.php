<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'shop_domain',
        'shopify_product_id',
        'title',
        'vendor',
        'status',
        'price',
        'shopify_updated_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'shopify_updated_at' => 'datetime',
    ];
}
