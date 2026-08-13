<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Record the customer's agreement to the terms, not just the acceptance. The
     * terms text is snapshotted because Settings → Terms can be edited later, and
     * what matters is the wording that was on screen when they ticked the box.
     */
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('accepted_at');
            $table->text('accepted_terms')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'accepted_terms']);
        });
    }
};
