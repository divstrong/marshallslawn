<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('square_footage', 10, 2)->nullable()->after('lawn_size');
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->decimal('square_footage', 10, 2)->nullable()->after('property_id');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('square_footage');
        });

        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('square_footage');
        });
    }
};
