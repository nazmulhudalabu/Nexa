<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('api_token_hash')->nullable()->unique()->after('remember_token');
            $table->timestamp('api_token_expires_at')->nullable()->after('api_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['api_token_hash', 'api_token_expires_at']);
        });
    }
};