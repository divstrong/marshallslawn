<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the denormalized job_total column. A job's total is now computed on
     * read from its service line prices (Job::total() / the crew revenue widget),
     * so there's nothing to keep in sync when line items change.
     */
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('job_total');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->decimal('job_total', 10, 2)->default(0)->after('price');
        });
    }
};
