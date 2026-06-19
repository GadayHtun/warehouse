<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('user_id')->constrained('users')
                ->comment('Supervisor who initiated this reconciliation session');
            $table->enum('status', ['draft', 'in_progress', 'submitted', 'under_review', 'closed'])
                ->default('draft')
                ->comment('Lifecycle: draft → in_progress → submitted → under_review → closed');
            $table->string('category_filter')->nullable()
                ->comment('Optional product category filter for this session');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['location_id', 'status'], 'idx_recon_sessions_location_status');
            $table->index('status', 'idx_recon_sessions_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_sessions');
    }
};
