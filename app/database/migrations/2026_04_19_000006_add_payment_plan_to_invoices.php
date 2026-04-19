<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_payment_plan')->default(false)->after('paid_at');
            $table->integer('payment_plan_installments')->default(12)->after('is_payment_plan');
            $table->decimal('payment_plan_amount', 10, 2)->nullable()->after('payment_plan_installments');
            $table->decimal('cc_fee_rate', 5, 4)->default(0.0375)->after('payment_plan_amount');
            $table->date('payment_plan_started_at')->nullable()->after('cc_fee_rate');
            $table->integer('payment_plan_payments_made')->default(0)->after('payment_plan_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'is_payment_plan',
                'payment_plan_installments',
                'payment_plan_amount',
                'cc_fee_rate',
                'payment_plan_started_at',
                'payment_plan_payments_made',
            ]);
        });
    }
};
