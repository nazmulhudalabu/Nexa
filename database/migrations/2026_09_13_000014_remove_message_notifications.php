<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->where('type', 'App\\Notifications\\ActivityNotification')
            ->get(['id', 'data'])
            ->each(function (object $notification): void {
                $data = json_decode($notification->data, true);
                if (($data['event'] ?? null) === 'message') {
                    DB::table('notifications')->where('id', $notification->id)->delete();
                }
            });
    }

    public function down(): void
    {
        // Removed message notifications cannot be restored safely.
    }
};
