<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an optional prize pool amount (Philippine pesos) to tournaments.
     *
     * Nullable, so existing tournaments are unaffected.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->decimal('prize_pool', 12, 2)->nullable()->after('venue');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('prize_pool');
        });
    }
};
