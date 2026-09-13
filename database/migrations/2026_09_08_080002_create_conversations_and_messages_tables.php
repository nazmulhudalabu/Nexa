<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete(); $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->unique(['user_one_id', 'user_two_id']);
        });
        Schema::create('messages', function (Blueprint $table): void {
            $table->id(); $table->foreignId('conversation_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('messages'); Schema::dropIfExists('conversations'); }
};