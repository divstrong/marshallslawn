<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            // A null price means "TBD" — the office has not quoted this line yet.
            // Distinct from 0.00, which means the line is genuinely free.
            $table->decimal('price', 10, 2)->nullable()->default(null)->change();
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            // Priority is no longer required on a job (issue #52).
            $table->string('priority')->nullable()->default('normal')->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_services', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->nullable(false)->change();
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            $table->string('priority')->default('normal')->nullable(false)->change();
        });
    }
};
