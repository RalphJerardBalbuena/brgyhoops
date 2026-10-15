<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional exact map pin (latitude/longitude) to games.
     *
     * Both columns are nullable, so existing games are unaffected. decimal(10,7)
     * stores ~1 cm precision and fits longitudes up to ±180.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('venue');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
