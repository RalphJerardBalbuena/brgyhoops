<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an admin officially eliminated the team from its tournament.
     *
     * Nullable: null means the team is still active. Set only through the
     * admin "Mark eliminated" action, never inferred from game results.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('eliminated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('eliminated_at');
        });
    }
};
