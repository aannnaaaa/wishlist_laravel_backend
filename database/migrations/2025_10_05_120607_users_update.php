<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('password');
            $table->integer('age')->nullable()->after('avatar');
            $table->integer('height')->nullable()->after('age');
            $table->string('clothing_size')->nullable()->after('height');
            $table->integer('shoe_size')->nullable()->after('clothing_size');
            $table->text('bio')->nullable()->after('shoe_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем только те поля, которые добавили в up()
            $table->dropColumn(['avatar', 'age', 'height', 'clothing_size', 'shoe_size', 'bio']);
        });
    }
};
