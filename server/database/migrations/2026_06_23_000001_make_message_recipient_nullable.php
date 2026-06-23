<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A message can be a job note with no specific recipient (it's addressed to
     * the office/crew at large), so the recipient morph must be nullable. The
     * original table used morphs() which made these NOT NULL.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('recipient_type')->nullable()->change();
            $table->unsignedBigInteger('recipient_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('recipient_type')->nullable(false)->change();
            $table->unsignedBigInteger('recipient_id')->nullable(false)->change();
        });
    }
};
