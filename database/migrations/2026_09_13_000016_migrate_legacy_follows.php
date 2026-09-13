<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('social_connections')
            ->where('type', 'follow')
            ->orderBy('id')
            ->get(['user_id', 'target_user_id', 'created_at'])
            ->each(function (object $connection): void {
                DB::table('followers')->updateOrInsert(
                    ['follower_id' => $connection->user_id, 'following_id' => $connection->target_user_id],
                    ['created_at' => $connection->created_at ?? now()],
                );
            });
    }

    public function down(): void
    {
        // Legacy social connections remain unchanged.
    }
};
