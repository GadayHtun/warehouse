<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_count_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('reconciliation_sessions');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('physical_quantity', 12, 3)
                ->comment('Quantity counted during physical verification');
            $table->decimal('system_quantity_at_count', 12, 3)->nullable()
                ->comment('System on-hand quantity SNAPSHOTTED at submission time — not the current system value');
            $table->decimal('variance', 12, 3)->nullable()
                ->comment('physical_quantity − system_quantity_at_count');
            $table->decimal('variance_percentage', 8, 2)->nullable()
                ->comment('(variance / system_quantity_at_count) × 100');
            $table->enum('status', ['pending', 'resolved', 'flagged_recount', 'deferred'])
                ->default('pending');
            $table->enum('resolution_type', ['accept', 'recount', 'defer'])->nullable();
            $table->text('resolution_note')->nullable();
            $table->enum('large_variance_approval_status', [
                'not_required', 'pending_approval', 'approved', 'rejected',
            ])->default('not_required')
                ->comment('Large variances (>5% or >50 units) require a second supervisor approval');
            $table->foreignId('large_variance_approver_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['session_id', 'status'], 'idx_count_lines_session_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_count_lines');
    }
};
