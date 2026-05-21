<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expo push tokens for the native app — one row per device so a
     * foreman can be reached on whichever device they signed in on.
     */
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};
