<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-referential pivot: a package can include other packages (issue #20).
        Schema::create('package_sub_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('child_package_id')->constrained('packages')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['parent_package_id', 'child_package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_sub_package');
    }
};
