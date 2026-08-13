<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two additions:
     *  - estimate_id, so an invoice can point back at the estimate it came from.
     *  - allows_payment_plan, the office's decision about whether this invoice may
     *    be split into installments at all. Distinct from is_payment_plan, which
     *    records that a plan is already running.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('estimate_id')->nullable()->after('customer_id')
                ->constrained('estimates')->nullOnDelete();
            $table->boolean('allows_payment_plan')->default(true)->after('is_payment_plan');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estimate_id');
            $table->dropColumn('allows_payment_plan');
        });
    }
};
