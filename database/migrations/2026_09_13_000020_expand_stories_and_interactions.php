<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->string('type', 12)->default('TEXT')->after('user_id');
            $table->string('privacy', 24)->default('PUBLIC')->after('type');
            $table->json('custom_user_ids')->nullable()->after('privacy');
            $table->json('hidden_user_ids')->nullable()->after('custom_user_ids');
            $table->text('media_url')->nullable()->after('image');
            $table->string('music')->nullable()->after('media_url');
            $table->json('poll_options')->nullable()->after('music');
        });

        Schema::create('story_viewers', function (Blueprint $table): void {
            $table->id(); $table->foreignId('story_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamp('viewed_at')->useCurrent(); $table->unique(['story_id', 'user_id']);
        });
        Schema::create('story_reactions', function (Blueprint $table): void {
            $table->id(); $table->foreignId('story_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('reaction_type', 16); $table->timestamps(); $table->unique(['story_id', 'user_id']);
        });
        Schema::create('story_replies', function (Blueprint $table): void {
            $table->id(); $table->foreignId('story_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->timestamps();
        });
        Schema::create('story_mutes', function (Blueprint $table): void {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('story_owner_id')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->unique(['user_id', 'story_owner_id']);
        });
        Schema::create('story_reports', function (Blueprint $table): void {
            $table->id(); $table->foreignId('story_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('reason', 100); $table->text('details')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_reports'); Schema::dropIfExists('story_mutes'); Schema::dropIfExists('story_replies'); Schema::dropIfExists('story_reactions'); Schema::dropIfExists('story_viewers');
        Schema::table('stories', function (Blueprint $table): void { $table->dropColumn(['type', 'privacy', 'custom_user_ids', 'hidden_user_ids', 'media_url', 'music', 'poll_options']); });
    }
};
