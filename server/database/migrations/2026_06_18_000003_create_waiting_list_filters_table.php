<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list_filters', function (Blueprint $table) {
            $table->id();
            $table->string('label');                 // shown as a chip on the waiting list
            $table->string('service_group');         // matches Service::GROUPS key
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the two starter filters from issue #26.
        $defaults = [
            ['label' => 'Mulch',  'service_group' => 'mulching', 'sort_order' => 1],
            ['label' => 'Mowing', 'service_group' => 'mowing',   'sort_order' => 2],
        ];

        foreach ($defaults as $row) {
            DB::table('waiting_list_filters')->updateOrInsert(
                ['label' => $row['label']],
                array_merge($row, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list_filters');
    }
};
