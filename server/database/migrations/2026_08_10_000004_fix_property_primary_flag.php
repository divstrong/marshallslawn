<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_primary` defaulted to true, so every property saved without an explicit
     * flag claimed primary status — which made "the customer's primary property"
     * meaningless wherever it is read (dispatch, the customer overview, the job and
     * estimate forms).
     *
     * Two fixes: the column now defaults to false (PropertyObserver promotes a
     * customer's first property instead), and the existing rows are reduced to
     * exactly one primary per customer.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->change();
        });

        $this->backfillOnePrimaryPerCustomer();
    }

    /**
     * For each customer, choose one property to be primary and clear the rest:
     *   1. a property whose street address matches the customer's own address —
     *      the home address is the primary one far more often than not;
     *   2. otherwise the earliest property on file.
     * Ordering by id keeps the choice deterministic and re-runnable.
     */
    private function backfillOnePrimaryPerCustomer(): void
    {
        DB::table('properties')
            ->select('customer_id')
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderBy('customer_id')
            ->pluck('customer_id')
            ->chunk(500)
            ->each(function ($customerIds) {
                $customers = DB::table('customers')
                    ->whereIn('id', $customerIds)
                    ->pluck('address', 'id');

                $properties = DB::table('properties')
                    ->whereIn('customer_id', $customerIds)
                    ->orderBy('id')
                    ->get(['id', 'customer_id', 'address'])
                    ->groupBy('customer_id');

                foreach ($properties as $customerId => $rows) {
                    $customerAddress = trim((string) ($customers[$customerId] ?? ''));

                    $chosen = null;
                    if ($customerAddress !== '') {
                        $chosen = $rows->first(fn ($row) => strcasecmp(
                            trim((string) $row->address),
                            $customerAddress,
                        ) === 0);
                    }
                    $chosen ??= $rows->first();

                    DB::table('properties')
                        ->where('customer_id', $customerId)
                        ->where('id', '!=', $chosen->id)
                        ->update(['is_primary' => false]);

                    DB::table('properties')
                        ->where('id', $chosen->id)
                        ->update(['is_primary' => true]);
                }
            });
    }

    public function down(): void
    {
        // Only the default is reversible; which property is primary is now real data.
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_primary')->default(true)->change();
        });
    }
};
