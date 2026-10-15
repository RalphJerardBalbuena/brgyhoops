<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional primary venue (name and/or address) to tournaments.
     *
     * Nullable, so existing tournaments are unaffected. Independent of
     * games.venue, which keeps storing each game's own venue.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('venue');
        });
    }
};
