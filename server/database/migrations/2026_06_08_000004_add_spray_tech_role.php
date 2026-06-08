<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Spray Tech mirrors the Foreman field experience (issue #12).
        DB::table('roles')->updateOrInsert(
            ['name' => 'spray_tech'],
            [
                'label' => 'Spray Tech',
                'is_admin' => false,
                'can_see_routes' => true,
                'can_see_chemicals' => true,
                'can_see_estimates' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'spray_tech')->delete();
    }
};
