<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('visibility', 24)->default('PUBLIC')->after('description');
            $table->string('location')->nullable()->after('visibility');
            $table->string('feeling')->nullable()->after('location');
            $table->string('link_url')->nullable()->after('feeling');
            $table->string('link_title')->nullable()->after('link_url');
            $table->text('link_description')->nullable()->after('link_title');
            $table->string('link_thumbnail')->nullable()->after('link_description');
        });

        Schema::table('comments', function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('comments')->cascadeOnDelete();
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->index(['post_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['post_id', 'parent_id']);
            $table->dropColumn(['parent_id', 'deleted_at']);
        });
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['visibility', 'location', 'feeling', 'link_url', 'link_title', 'link_description', 'link_thumbnail']);
        });
    }
};
