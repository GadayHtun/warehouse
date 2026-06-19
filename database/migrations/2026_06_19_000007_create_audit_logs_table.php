<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')
                ->comment('User who performed the action; null for system actions');
            $table->string('event')->comment('Action name: stock_in, stock_out, adjustment_created, etc.');
            $table->string('entity_type')->comment('Model class or entity name');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Primary key of the affected entity');
            $table->json('old_values')->nullable()->comment('Previous state as JSON diff');
            $table->json('new_values')->nullable()->comment('New state as JSON diff');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()
                ->comment('Append-only audit record — no UPDATE or DELETE');

            $table->index(['entity_type', 'entity_id', 'created_at'], 'idx_audit_entity_date');
            $table->index('event', 'idx_audit_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
