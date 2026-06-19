<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_check_runs', function (Blueprint $table) {
            $table->id();
            $table->string('check_type')
                ->comment('Which agent check was executed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('findings_count')->default(0)
                ->comment('Number of findings produced in this run');
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->text('error_message')->nullable()
                ->comment('Exception message if the check failed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_check_runs');
    }
};
