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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->string('shop_domain');
            $table->string('webhook_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at');
            $table->enum('processing_status', ['pending', 'processed', 'failed'])->default('pending');
            $table->timestamps();

            $table->index(['shop_domain', 'topic']);
            $table->unique(['webhook_id', 'topic']);
            $table->index('processing_status');
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
