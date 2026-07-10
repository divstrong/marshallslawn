<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            // sha1 of (source_locale|target_locale|source text) — one row per unique phrase.
            $table->string('hash', 40)->unique();
            $table->string('source_locale', 8);
            $table->string('target_locale', 8);
            $table->text('source_text');
            $table->text('translated_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
