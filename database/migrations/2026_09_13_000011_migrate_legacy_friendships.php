<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('social_connections')
            ->where('type', 'friend')
            ->where('status', 'accepted')
            ->orderBy('id')
            ->get()
            ->each(function (object $connection): void {
                $userOneId = min($connection->user_id, $connection->target_user_id);
                $userTwoId = max($connection->user_id, $connection->target_user_id);
                $existing = DB::table('friendships')
                    ->where(['user_one_id' => $userOneId, 'user_two_id' => $userTwoId])
                    ->first();

                if ($existing) {
                    DB::table('friendships')->where('id', $existing->id)->update([
                        'status' => 'FRIENDS',
                        'updated_at' => now(),
                    ]);
                    return;
                }

                DB::table('friendships')->insert([
                    'user_one_id' => $userOneId,
                    'user_two_id' => $userTwoId,
                    'sender_id' => $connection->user_id,
                    'status' => 'FRIENDS',
                    'created_at' => $connection->created_at,
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Legacy social connections remain unchanged.
    }
};
