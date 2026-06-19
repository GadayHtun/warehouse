<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique()->comment('Stock Keeping Unit — immutable product identifier');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->enum('unit_of_measure', ['pcs', 'kg', 'L'])->default('pcs');
            $table->decimal('min_stock_threshold', 12, 3)->default(0)
                ->comment('When on-hand falls below this, product is flagged as low stock');
            $table->decimal('reorder_point', 12, 3)->default(0)
                ->comment('Suggested reorder quantity trigger');
            $table->decimal('cost_price', 12, 2);
            $table->decimal('retail_price', 12, 2);
            $table->string('barcode')->nullable();
            $table->softDeletes()->comment('Soft delete — not allowed if transaction history exists');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
