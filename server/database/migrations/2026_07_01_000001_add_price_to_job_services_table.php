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

        // Backfill each line from its service's default rate.
        DB::statement('
            UPDATE job_services js
            JOIN services s ON s.id = js.service_id
            SET js.price = s.default_price
            WHERE js.service_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
