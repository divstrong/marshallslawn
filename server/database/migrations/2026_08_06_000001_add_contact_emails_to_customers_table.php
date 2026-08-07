<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Routing addresses for the three customer-facing streams. Each is
            // optional; blank falls back to the primary `email` column, so an
            // account with one contact keeps working untouched.
            $table->string('estimate_email')->nullable()->after('email');
            $table->string('billing_email')->nullable()->after('estimate_email');
            $table->string('service_email')->nullable()->after('billing_email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['estimate_email', 'billing_email', 'service_email']);
        });
    }
};
