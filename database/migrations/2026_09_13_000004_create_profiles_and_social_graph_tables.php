<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('username', 50)->unique();
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->string('relationship_status')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->json('visibility')->nullable();
            $table->timestamps();
        });

        foreach (['user_bios', 'user_workplaces', 'user_education', 'user_locations', 'user_relationships'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->text(match ($tableName) {
                    'user_bios' => 'bio',
                    'user_workplaces' => 'workplace',
                    'user_education' => 'education',
                    'user_locations' => 'location',
                    default => 'relationship_status',
                })->nullable();
                $table->timestamps();
            });
        }

        Schema::create('profile_photos', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('path'); $table->boolean('is_current')->default(false); $table->timestamps();
        });
        Schema::create('cover_photos', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('path'); $table->boolean('is_current')->default(false); $table->timestamps();
        });
        Schema::create('social_connections', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete(); $table->string('type'); $table->string('status')->default('accepted'); $table->timestamps(); $table->unique(['user_id', 'target_user_id', 'type']);
        });
        Schema::create('profile_blocks', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('blocked_user_id')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->unique(['user_id', 'blocked_user_id']);
        });
        Schema::create('profile_reports', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('reported_user_id')->constrained('users')->cascadeOnDelete(); $table->string('reason'); $table->text('details')->nullable(); $table->string('status')->default('open'); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_reports'); Schema::dropIfExists('profile_blocks'); Schema::dropIfExists('social_connections'); Schema::dropIfExists('cover_photos'); Schema::dropIfExists('profile_photos');
        Schema::dropIfExists('user_relationships'); Schema::dropIfExists('user_locations'); Schema::dropIfExists('user_education'); Schema::dropIfExists('user_workplaces'); Schema::dropIfExists('user_bios'); Schema::dropIfExists('profiles');
    }
};