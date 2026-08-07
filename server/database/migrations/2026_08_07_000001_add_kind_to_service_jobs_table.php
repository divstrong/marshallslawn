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
            // How the job is priced, and therefore how its form behaves:
            //   service — service lines, each priced, summing to the total
            //   quick   — a flat price plus notes, no services at all
            $table->string('kind')->default('service')->after('type');
            $table->index('kind');
        });

        // Backfill: a job with a direct price and no service lines was already
        // a quick job in everything but name.
        DB::table('service_jobs')
            ->whereNotNull('price')
            ->whereNotExists(fn ($q) => $q
                ->selectRaw(1)
                ->from('job_services')
                ->whereColumn('job_services.job_id', 'service_jobs.id'))
            ->update(['kind' => 'quick']);
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
