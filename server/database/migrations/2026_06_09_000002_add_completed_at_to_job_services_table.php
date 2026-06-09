<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_services', function (Blueprint $table): void {
            // Foreman checks off each service as it's completed on a job.
            $table->timestamp('completed_at')->nullable()->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('job_services', function (Blueprint $table): void {
            $table->dropColumn('completed_at');
        });
    }
};
