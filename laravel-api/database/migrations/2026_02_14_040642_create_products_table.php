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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain');
            $table->string('shopify_product_id');
            $table->string('title');
            $table->string('vendor')->nullable();
            $table->string('status')->default('active');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['shop_domain', 'shopify_product_id']);
    
            $table->index('shopify_updated_at');
            $table->index(['shop_domain', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
