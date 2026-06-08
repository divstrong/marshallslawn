<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            // Optional icon shown on dispatch job cards / map pins (issue #8).
            $table->string('icon_path')->nullable()->after('service_mode');
            // High-level grouping used by the dispatch service filters (issue #15).
            $table->string('service_group')->nullable()->after('icon_path');
        });

        // Best-effort backfill: classify existing services by keywords in their
        // name / parent so the Spraying / Mowing / Mulching filters work out of the
        // box. Admins can refine any service afterwards in the Services resource.
        $services = DB::table('services')->get(['id', 'name', 'parent_service', 'full_name']);

        foreach ($services as $service) {
            $haystack = strtolower(trim(
                ($service->name ?? '') . ' ' . ($service->parent_service ?? '') . ' ' . ($service->full_name ?? '')
            ));

            $group = match (true) {
                str_contains($haystack, 'mulch') => 'mulching',
                str_contains($haystack, 'mow') => 'mowing',
                str_contains($haystack, 'spray'),
                str_contains($haystack, 'fertil'),
                str_contains($haystack, 'weed'),
                str_contains($haystack, 'chemical'),
                str_contains($haystack, 'pest'),
                str_contains($haystack, 'herbicide'),
                str_contains($haystack, 'treatment') => 'spraying',
                default => null,
            };

            if ($group !== null) {
                DB::table('services')->where('id', $service->id)->update(['service_group' => $group]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn(['icon_path', 'service_group']);
        });
    }
};
