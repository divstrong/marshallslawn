<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreman job clock: when a foreman arrives at a job they start it,
     * and when the work is done they finish it. This is a per-job timer,
     * distinct from the day-level shift tracked in time_logs.
     */
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            $table->timestamp('started_at')->nullable()->after('completed_date');
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            $table->dropColumn(['started_at', 'finished_at']);
        });
    }
};
