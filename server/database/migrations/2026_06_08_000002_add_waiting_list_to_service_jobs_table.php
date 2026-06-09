<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            // Jobs parked on the "Waiting List" queue (future work held back from the
            // near-term Unassigned pile) — issue #16.
            $table->boolean('waiting_list')->default(false)->after('status');
            $table->index('waiting_list');
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table): void {
            $table->dropIndex(['waiting_list']);
            $table->dropColumn('waiting_list');
        });
    }
};
