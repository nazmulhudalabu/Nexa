<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id(); $table->string('name')->unique(); $table->string('slug')->unique(); $table->string('color')->default('#d96745'); $table->timestamps();
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id(); $table->string('name')->unique(); $table->string('slug')->unique(); $table->timestamps();
        });
        Schema::create('post_tag', function (Blueprint $table): void {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete(); $table->foreignId('tag_id')->constrained()->cascadeOnDelete(); $table->primary(['post_id', 'tag_id']);
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id(); $table->foreignId('post_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->timestamps();
        });
        Schema::create('likes', function (Blueprint $table): void {
            $table->id(); $table->foreignId('post_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes'); Schema::dropIfExists('comments'); Schema::dropIfExists('post_tag'); Schema::dropIfExists('tags'); Schema::dropIfExists('categories');
    }
};