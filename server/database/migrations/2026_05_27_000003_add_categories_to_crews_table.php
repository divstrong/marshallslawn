<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crews', function (Blueprint $table) {
            $table->json('categories')->nullable()->after('division');
        });
    }

    public function down(): void
    {
        Schema::table('crews', function (Blueprint $table) {
            $table->dropColumn('categories');
        });
    }
};
