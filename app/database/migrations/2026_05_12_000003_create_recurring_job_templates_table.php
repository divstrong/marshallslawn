<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_job_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('crew_id')->nullable()->constrained('crews')->nullOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('interval_days')->default(7);
            $table->unsignedTinyInteger('preferred_day_of_week')->nullable(); // 0=Sun .. 6=Sat
            $table->unsignedTinyInteger('season_start_month')->nullable();    // 1-12 inclusive
            $table->unsignedTinyInteger('season_end_month')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_generation_date')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['active', 'next_generation_date']);
        });

        Schema::table('service_jobs', function (Blueprint $table) {
            $table->foreignId('recurring_job_template_id')
                ->nullable()
                ->after('estimate_id')
                ->constrained('recurring_job_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_jobs', function (Blueprint $table) {
            $table->dropForeign(['recurring_job_template_id']);
            $table->dropColumn('recurring_job_template_id');
        });

        Schema::dropIfExists('recurring_job_templates');
    }
};
