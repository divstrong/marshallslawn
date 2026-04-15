<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('resource'); // e.g. 'CustomerResource'
            $table->timestamps();

            $table->unique(['role_id', 'resource']);
        });

        // Add role_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Seed default admin role
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'label' => 'Administrator',
            'is_admin' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign all existing users to admin
        DB::table('users')->update(['role_id' => $roleId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
