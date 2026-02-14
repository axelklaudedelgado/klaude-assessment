<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'shop_domain',
        'shopify_order_id',
        'order_number',
        'total_price',
        'financial_status',
        'fulfillment_status',
        'shopify_created_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shopify_created_at' => 'datetime',
    ];
}
