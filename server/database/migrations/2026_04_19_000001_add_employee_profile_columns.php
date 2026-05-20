<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('role')->default('field')->after('status');
            $table->date('date_of_birth')->nullable()->after('hire_date');
            $table->string('mobile_phone')->nullable()->after('phone');
            $table->string('alt_phone')->nullable()->after('mobile_phone');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['role', 'date_of_birth', 'mobile_phone', 'alt_phone']);
        });
    }
};
