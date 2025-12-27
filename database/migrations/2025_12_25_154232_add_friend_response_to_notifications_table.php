<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Меняем enum, добавляя новый тип
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('friend_request', 'friend_response', 'reservation', 'wishlist_update', 'other') NOT NULL DEFAULT 'other'");
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Возвращаем старый enum без friend_response
            DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('friend_request', 'reservation', 'wishlist_update', 'other') NOT NULL DEFAULT 'other'");
        });
    }
};
