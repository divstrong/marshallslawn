<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `hourly_rate` was NOT NULL DEFAULT 0. The admin employee form treats it as
 * optional, and Filament dehydrates an empty optional field as an explicit
 * NULL — which is included in the INSERT and overrides the column default,
 * so creating an employee without a rate failed with a 23000 constraint
 * violation. Make the column nullable so "no rate on file" is a valid state,
 * matching the earlier relaxation of email / hire_date in the employees table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Backfill so the NOT NULL column can be restored without failing.
        DB::table('employees')->whereNull('hourly_rate')->update(['hourly_rate' => 0]);

        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->default(0)->change();
        });
    }
};
