<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Team managers' applications to take part in a tournament.
     *
     * Separate from teams.application_status (team roster approval) and from
     * tournament_user (workspace access): an application grants nothing until
     * an admin approves it, which then adds the tournament_user row.
     */
    public function up(): void
    {
        Schema::create('tournament_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('pending');
            $table->text('admin_review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // One application per manager per tournament.
            $table->unique(['tournament_id', 'user_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_applications');
    }
};
