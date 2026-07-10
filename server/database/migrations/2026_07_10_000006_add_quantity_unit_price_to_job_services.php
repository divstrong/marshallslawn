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
            // Estimate-style line: quantity × unit_price = price (the line total).
            // `price` stays the source of truth for job/crew revenue; a null price
            // still means TBD.
            $table->decimal('quantity', 8, 2)->default(1)->after('service_id');
            $table->decimal('unit_price', 10, 2)->nullable()->after('quantity');
        });

        // Existing rows carried a single amount in `price`; treat it as the unit
        // price at quantity 1 so quantity × unit_price still equals that total.
        DB::table('job_services')->update(['unit_price' => DB::raw('price')]);
    }

    public function down(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit_price']);
        });
    }
};
