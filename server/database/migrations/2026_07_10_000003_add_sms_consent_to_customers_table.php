<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // A2P / CTIA consent state for outbound SMS.
            //   pending    — no opt-in yet (default)
            //   confirmed  — replied YES (or ticked the public opt-in form + confirmed)
            //   opted_out  — replied STOP
            $table->string('sms_consent_status')->default('pending')->after('phone');
            $table->timestamp('sms_opt_in_sent_at')->nullable()->after('sms_consent_status');
            $table->timestamp('sms_consent_at')->nullable()->after('sms_opt_in_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['sms_consent_status', 'sms_opt_in_sent_at', 'sms_consent_at']);
        });
    }
};
