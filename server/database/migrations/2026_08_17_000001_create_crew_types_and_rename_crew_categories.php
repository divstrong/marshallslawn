<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crew "categories" become "crew types": the same multi-select field,
     * renamed, with its options moved out of a PHP const and into an editable
     * table so the office can manage them from Settings -> Crew Types.
     *
     * The dispatch board filters crews by these, replacing the old filter on
     * Service::service_group.
     */
    public function up(): void
    {
        Schema::create('crew_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // machine key, stored inside crews.type
            $table->string('label');            // human label shown in the UI
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seeded with the keys crews already carry so no existing selection is
        // orphaned by the rename. 'spray_techs' keeps its key — that string is
        // what sits in the JSON column — but takes the requested "Spraying"
        // label. 'mulching' is new.
        $defaults = [
            ['name' => 'mowing', 'label' => 'Mowing', 'sort_order' => 1],
            ['name' => 'spray_techs', 'label' => 'Spraying', 'sort_order' => 2],
            ['name' => 'mulching', 'label' => 'Mulching', 'sort_order' => 3],
            ['name' => 'projects', 'label' => 'Projects', 'sort_order' => 4],
            ['name' => 'managers', 'label' => 'Managers', 'sort_order' => 5],
        ];

        foreach ($defaults as $row) {
            DB::table('crew_types')->updateOrInsert(
                ['name' => $row['name']],
                array_merge($row, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        Schema::table('crews', function (Blueprint $table) {
            $table->renameColumn('categories', 'type');
        });
    }

    public function down(): void
    {
        Schema::table('crews', function (Blueprint $table) {
            $table->renameColumn('type', 'categories');
        });

        Schema::dropIfExists('crew_types');
    }
};
