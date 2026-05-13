<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->foreignId('job_id')
                ->nullable()
                ->after('route_id')
                ->constrained('service_jobs')
                ->nullOnDelete();
        });

        // customer_id was NOT NULL. When a stop points at a job_id, customer/property/service
        // are derivable from the job. Keep the columns for ad-hoc stops, but make them optional.
        Schema::table('route_stops', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropForeign(['job_id']);
            $table->dropColumn('job_id');
        });
    }
};
