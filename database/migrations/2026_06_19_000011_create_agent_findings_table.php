<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_findings', function (Blueprint $table) {
            $table->id();
            $table->string('check_type')
                ->comment('Which agent check produced this finding (e.g., negative_stock, variance_drift)');
            $table->enum('severity', ['info', 'warning', 'critical'])
                ->comment('info=awareness, warning=action needed, critical=data integrity at risk');
            $table->foreignId('product_id')->nullable()->constrained('products');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->string('title');
            $table->text('description');
            $table->timestamp('detected_at')->useCurrent();
            $table->enum('status', ['open', 'acknowledged', 'dismissed'])->default('open');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')
                ->comment('Supervisor who reviewed this finding');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable()
                ->comment('Required when dismissing a finding');
            $table->string('dedup_hash')->index()
                ->comment('Hash of (check_type, product_id, location_id) to prevent duplicate open findings');

            $table->timestamps();

            $table->index(['status', 'severity', 'detected_at'], 'idx_findings_status_severity_date');
            $table->index('check_type', 'idx_findings_check_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_findings');
    }
};
