<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_media', function (Blueprint $table): void {
            $table->id(); $table->foreignId('post_id')->constrained()->cascadeOnDelete(); $table->string('path'); $table->string('type'); $table->string('original_name'); $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('post_media'); }
};