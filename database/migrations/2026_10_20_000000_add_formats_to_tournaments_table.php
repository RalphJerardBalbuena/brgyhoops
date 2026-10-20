<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the tournament format and finals format chosen by the admin.
     *
     * Nullable with no default: existing tournaments never chose a format, so
     * they keep NULL ("Not specified") until an admin edits them. New and
     * edited tournaments must pick both (validated in the controller).
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->string('tournament_format', 32)->nullable()->after('prize_pool');
            $table->string('finals_format', 32)->nullable()->after('tournament_format');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn(['tournament_format', 'finals_format']);
        });
    }
};
