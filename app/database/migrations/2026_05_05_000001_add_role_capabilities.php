<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_see_routes')->default(false)->after('is_admin');
            $table->boolean('can_see_chemicals')->default(false)->after('can_see_routes');
            $table->boolean('can_see_estimates')->default(false)->after('can_see_chemicals');
        });

        $defaults = [
            ['name' => 'field',      'label' => 'Field Technician', 'is_admin' => false, 'can_see_routes' => false, 'can_see_chemicals' => false, 'can_see_estimates' => false],
            ['name' => 'office',     'label' => 'Office',           'is_admin' => false, 'can_see_routes' => false, 'can_see_chemicals' => false, 'can_see_estimates' => false],
            ['name' => 'spray_tech', 'label' => 'Spray Tech',       'is_admin' => false, 'can_see_routes' => false, 'can_see_chemicals' => true,  'can_see_estimates' => false],
            ['name' => 'estimator',  'label' => 'Estimator',        'is_admin' => false, 'can_see_routes' => false, 'can_see_chemicals' => false, 'can_see_estimates' => true],
            ['name' => 'supervisor', 'label' => 'Supervisor',       'is_admin' => false, 'can_see_routes' => true,  'can_see_chemicals' => true,  'can_see_estimates' => true],
        ];

        foreach ($defaults as $row) {
            if (! DB::table('roles')->where('name', $row['name'])->exists()) {
                DB::table('roles')->insert(array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['can_see_routes', 'can_see_chemicals', 'can_see_estimates']);
        });
    }
};
