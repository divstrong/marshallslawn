<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            // Cached "Job Total" = sum of the approved service lines (drives crew revenue).
            $table->decimal('job_total', 10, 2)->default(0)->after('price');
        });

        // Backfill from the sum of each job's service lines.
        DB::statement('
            UPDATE service_jobs sj
            SET job_total = (
                SELECT COALESCE(SUM(js.price), 0)
                FROM job_services js
                WHERE js.job_id = sj.id
            )
        ');

        // Jobs with no service lines but a direct price fall back to that price.
        DB::statement('
            UPDATE service_jobs
            SET job_total = price
            WHERE job_total = 0 AND price IS NOT NULL AND price > 0
        ');
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropColumn('job_total');
        });
    }
};
