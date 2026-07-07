<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_category_id')->nullable()->constrained('tag_categories')->nullOnDelete();
            $table->string('name')->unique();
            // Whether ServiceNow flagged this as an automation tag ("check icon" in the export).
            $table->boolean('is_automation')->default(false);
            // The "Modified" date from the source export, kept for reference.
            $table->date('source_modified_at')->nullable();
            $table->timestamps();
        });

        // Polymorphic assignment so tags attach to customers, jobs, etc.
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->morphs('taggable');
            $table->timestamps();
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_categories');
    }
};
