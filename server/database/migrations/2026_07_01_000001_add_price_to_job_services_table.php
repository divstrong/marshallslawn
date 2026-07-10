<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            // Approved price for this service line on the job (issue: crew revenue).
            $table->decimal('price', 10, 2)->default(0)->after('service_id');
        });

        // Backfill each line from its service's default rate. Written as a correlated
        // subquery rather than UPDATE..JOIN so it runs on SQLite as well as MySQL.
        DB::table('job_services')
            ->whereNotNull('service_id')
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('services')
                ->whereColumn('services.id', 'job_services.service_id'))
            ->update([
                'price' => DB::raw('(select default_price from services where services.id = job_services.service_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
