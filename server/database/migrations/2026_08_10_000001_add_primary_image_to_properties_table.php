<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A single "this is what the place looks like" photo, kept on the property
     * itself rather than in property_media: crews and office staff need one
     * canonical shot to recognise the address, not a gallery to pick from.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('primary_image_path')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('primary_image_path');
        });
    }
};
