<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->unique()->after('id');
            $table->string('company_name')->nullable()->after('legacy_id');
            $table->string('customer_type')->nullable()->after('status');
            $table->string('account_number')->nullable()->after('customer_type');
            $table->string('division')->nullable()->after('account_number');
            $table->string('map_code')->nullable()->after('division');
            $table->string('list_id')->nullable()->after('map_code');
        });

        Schema::table('crews', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->unique()->after('id');
            $table->string('code')->nullable()->after('legacy_id');
            $table->string('division')->nullable()->after('status');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->unique()->after('id');
            $table->string('full_name')->nullable()->after('name');
            $table->string('list_id')->nullable()->after('is_active');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('legacy_id')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('legacy_id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('division')->nullable()->after('status');
            $table->string('list_id')->nullable()->after('division');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['legacy_id', 'company_name', 'customer_type', 'account_number', 'division', 'map_code', 'list_id']);
        });

        Schema::table('crews', function (Blueprint $table) {
            $table->dropColumn(['legacy_id', 'code', 'division']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['legacy_id', 'full_name', 'list_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['legacy_id', 'first_name', 'last_name', 'division', 'list_id']);
        });
    }
};
