<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the "supervisor" role to "foreman" so it backs the mobile
     * app's Foreman experience. Existing employees carrying the old slug
     * are migrated to the new one.
     */
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'supervisor')
            ->update(['name' => 'foreman', 'label' => 'Foreman']);

        DB::table('employees')
            ->where('role', 'supervisor')
            ->update(['role' => 'foreman']);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'foreman')
            ->update(['name' => 'supervisor', 'label' => 'Supervisor']);

        DB::table('employees')
            ->where('role', 'foreman')
            ->update(['role' => 'supervisor']);
    }
};
