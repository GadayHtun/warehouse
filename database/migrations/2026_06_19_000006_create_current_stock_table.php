<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('current_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->constrained('locations');
            $table->decimal('quantity_on_hand', 12, 3)->default(0)
                ->comment('Denormalized stock level — computed from inventory_transactions SUM, updated synchronously');
            $table->timestamp('created_at')->useCurrent()
                ->comment('First time stock recorded for this product-location');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()
                ->comment('Last time this stock level was recalculated');

            $table->unique(['product_id', 'location_id'], 'uq_current_stock_product_location');
            $table->index('quantity_on_hand', 'idx_current_stock_qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('current_stock');
    }
};
