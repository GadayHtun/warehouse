<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('direction', ['in', 'out'])->comment('in = stock received, out = stock leaving');
            $table->decimal('quantity', 12, 3)->comment('Supports fractional units (kg, L)');
            $table->decimal('unit_cost_at_movement', 12, 2)->nullable()
                ->comment('Snapshotted cost price at time of movement — does not retroactively change');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->text('reference_note')->nullable();
            $table->string('batch_lot')->nullable();
            $table->string('idempotency_key')->unique()
                ->comment('UUID to prevent duplicate processing');
            $table->timestamps();

            $table->index(['product_id', 'location_id', 'created_at'], 'idx_stock_movements_product_location_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
