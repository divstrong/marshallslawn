<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // A null default rate means "no standard price" — the job/estimate grid
            // then starts that service's line as TBD rather than a misleading $0.
            $table->decimal('default_price', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('default_price', 10, 2)->default(0)->nullable(false)->change();
        });
    }
};
