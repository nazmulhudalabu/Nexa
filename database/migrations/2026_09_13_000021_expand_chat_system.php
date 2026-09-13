<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->string('type', 12)->default('DIRECT')->after('id');
            $table->string('name')->nullable()->after('type');
            $table->string('picture')->nullable()->after('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('picture');
            $table->boolean('is_muted')->default(false)->after('created_by');
        });
        Schema::create('conversation_members', function (Blueprint $table): void {
            $table->id(); $table->foreignId('conversation_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->boolean('is_admin')->default(false); $table->timestamp('last_read_at')->nullable(); $table->timestamps(); $table->unique(['conversation_id', 'user_id']);
        });
        Schema::table('messages', function (Blueprint $table): void {
            $table->string('type', 16)->default('TEXT')->after('user_id');
            $table->string('status', 16)->default('SENT')->after('type');
            $table->foreignId('reply_to_id')->nullable()->after('status')->constrained('messages')->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('read_at');
            $table->timestamp('deleted_at')->nullable()->after('edited_at');
            $table->boolean('pinned')->default(false)->after('deleted_at');
        });
        Schema::create('message_reactions', function (Blueprint $table): void { $table->id(); $table->foreignId('message_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('reaction_type', 16); $table->timestamps(); $table->unique(['message_id', 'user_id']); });
        Schema::create('message_reads', function (Blueprint $table): void { $table->id(); $table->foreignId('message_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamp('read_at'); $table->unique(['message_id', 'user_id']); });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads'); Schema::dropIfExists('message_reactions');
        Schema::table('messages', function (Blueprint $table): void { $table->dropForeign(['reply_to_id']); $table->dropColumn(['type', 'status', 'reply_to_id', 'edited_at', 'deleted_at', 'pinned']); });
        Schema::dropIfExists('conversation_members');
        Schema::table('conversations', function (Blueprint $table): void { $table->dropForeign(['created_by']); $table->dropColumn(['type', 'name', 'picture', 'created_by', 'is_muted']); });
    }
};
