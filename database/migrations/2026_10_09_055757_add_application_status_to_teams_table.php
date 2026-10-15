
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('application_status')
                ->default('draft')
                ->index();

            $table->text('admin_review_note')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['application_status']);
            $table->dropColumn([
                'application_status',
                'admin_review_note',
                'applied_at',
                'reviewed_at',
            ]);
        });
    }
};