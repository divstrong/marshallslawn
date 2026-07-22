<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Short-lived one-time codes emailed to employees signing in to the native
 * app. The app is passwordless: a valid code is the only way in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_login_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            // Only the hash is stored, so a database leak does not hand over
            // live sign-in codes.
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'consumed_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_login_codes');
    }
};
