<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            // Estimated time to completion, stored as total minutes.
            $table->unsignedInteger('estimated_minutes')->nullable()->after('priority');
            // When true, admins should not change this job's scheduled date.
            $table->boolean('do_not_move')->default(false)->after('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn(['estimated_minutes', 'do_not_move']);
        });
    }
};
