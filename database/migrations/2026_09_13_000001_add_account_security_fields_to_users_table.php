<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('status')->default('PENDING_VERIFICATION')->index()->after('password');
            $table->string('locale', 10)->default('en')->after('status');
            $table->string('timezone', 64)->default('UTC')->after('locale');
            $table->boolean('two_factor_enabled')->default(false)->after('timezone');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->unsignedTinyInteger('login_attempts')->default(0)->after('two_factor_recovery_codes');
            $table->timestamp('locked_until')->nullable()->after('login_attempts');
            $table->timestamp('deactivated_at')->nullable()->after('locked_until');
            $table->timestamp('deleted_at')->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone', 'phone_verified_at', 'status', 'locale', 'timezone',
                'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes',
                'login_attempts', 'locked_until', 'deactivated_at', 'deleted_at',
            ]);
        });
    }
};