<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'supervisor', 'agent'])
                ->default('agent')
                ->after('email')
                ->comment('User role for authorization: admin (full access), supervisor (oversight), agent (operations)');

            $table->timestamp('last_active_at')
                ->nullable()
                ->after('remember_token')
                ->comment('Updated on each authenticated request via middleware');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'last_active_at']);
        });
    }
};
