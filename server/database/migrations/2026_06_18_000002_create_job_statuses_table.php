<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();      // machine slug stored on service_jobs.status
            $table->string('label');               // human label shown in the UI
            $table->string('color')->default('gray'); // Filament badge color
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_protected')->default(false); // core statuses can't be deleted
            $table->timestamps();
        });

        // Seed the core statuses (issue #25). These are protected from deletion
        // because application logic and the mobile app rely on them.
        $defaults = [
            ['name' => 'pending',     'label' => 'Pending',      'color' => 'gray',    'sort_order' => 1],
            ['name' => 'waiting_list', 'label' => 'Waiting List', 'color' => 'warning', 'sort_order' => 2],
            ['name' => 'scheduled',   'label' => 'Scheduled',    'color' => 'info',    'sort_order' => 3],
            ['name' => 'in_progress', 'label' => 'In Progress',  'color' => 'primary', 'sort_order' => 4],
            ['name' => 'completed',   'label' => 'Completed',    'color' => 'success', 'sort_order' => 5],
            ['name' => 'skipped',     'label' => 'Skipped',      'color' => 'warning', 'sort_order' => 6],
            ['name' => 'cancelled',   'label' => 'Cancelled',    'color' => 'danger',  'sort_order' => 7],
        ];

        foreach ($defaults as $row) {
            DB::table('job_statuses')->updateOrInsert(
                ['name' => $row['name']],
                array_merge($row, ['is_protected' => true, 'created_at' => now(), 'updated_at' => now()]),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_statuses');
    }
};
