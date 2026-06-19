<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('location_id')->constrained('locations');
            $table->enum('type', ['stock_in', 'stock_out', 'adjustment_in', 'adjustment_out'])
                ->comment('Append-only — no UPDATE or DELETE allowed on this table');
            $table->decimal('quantity', 12, 3)
                ->comment('Positive quantity. Type column determines whether this adds to or subtracts from inventory');
            $table->string('reference_type')->nullable()
                ->comment('Polymorphic reference to the source entity (e.g., StockMovement, ReconciliationCountLine)');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('Polymorphic reference ID');
            $table->foreignId('user_id')->constrained('users');
            $table->string('idempotency_key')->unique()
                ->comment('UUID to prevent duplicate transaction entries');
            $table->timestamp('created_at')->useCurrent()
                ->comment('Append-only — no updated_at column');

            $table->index(['product_id', 'location_id', 'created_at'], 'idx_inventory_trans_product_location_date');
            $table->index(['reference_type', 'reference_id'], 'idx_inventory_trans_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
