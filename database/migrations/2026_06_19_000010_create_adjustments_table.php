<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('count_line_id')->constrained('reconciliation_count_lines');
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')
                ->comment('The inventory_transactions row created by this adjustment');
            $table->text('reason')->comment('Justification — minimum 10 characters');
            $table->foreignId('approved_by')->nullable()->constrained('users')
                ->comment('Supervisor who approved; null for non-large-variance auto-approved adjustments');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
