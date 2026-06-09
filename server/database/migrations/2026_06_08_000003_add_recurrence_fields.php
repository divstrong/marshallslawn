<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            // One-time vs. recurring job (issue #13). Recurring jobs are spawned from a template.
            $table->string('type')->default('one_time')->after('waiting_list');
            $table->index('type');
        });

        Schema::table('recurring_job_templates', function (Blueprint $table): void {
            $table->string('frequency')->default('weekly')->after('interval_days');
            // Total number of occurrences to generate; null means indefinite/ongoing.
            $table->unsignedInteger('occurrences')->nullable()->after('frequency');
        });

        // Existing jobs tied to a template are recurring occurrences.
        DB::table('service_jobs')
            ->whereNotNull('recurring_job_template_id')
            ->update(['type' => 'recurring']);
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });

        Schema::table('recurring_job_templates', function (Blueprint $table): void {
            $table->dropColumn(['frequency', 'occurrences']);
        });
    }
};
