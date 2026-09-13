<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('bio')->nullable();
            $table->string('profession')->nullable();
            $table->string('location')->nullable();
            $table->string('interests')->nullable();
            $table->string('hobbies')->nullable();
            $table->string('profile_photo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['bio', 'profession', 'location', 'interests', 'hobbies', 'profile_photo']);
        });
    }
};