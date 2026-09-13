<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reaction_type', 16);
            $table->timestamps();
            $table->unique(['post_id', 'user_id']);
        });
        Schema::create('shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('share_type', 24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
        Schema::dropIfExists('reactions');
    }
};
