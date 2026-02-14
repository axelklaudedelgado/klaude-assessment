<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain');
            $table->string('shopify_order_id');
            $table->string('order_number');
            $table->decimal('total_price', 10, 2);
            $table->string('financial_status')->nullable();
            $table->string('fulfillment_status')->nullable();
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_domain', 'shopify_order_id']);
    
            $table->index('shopify_created_at');
            $table->index(['shop_domain', 'financial_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
