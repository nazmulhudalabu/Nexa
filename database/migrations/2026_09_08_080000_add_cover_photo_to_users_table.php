<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void { $table->string('cover_photo')->nullable()->after('profile_photo'); });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void { $table->dropColumn('cover_photo'); });
    }
};