<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreman <-> office chat. Each row belongs to one foreman's thread
     * (employee_id); `sender` says which side wrote it. A message carries
     * text and/or a single photo/video attachment.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('sender'); // 'foreman' | 'office'
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('attachment_type')->nullable(); // 'photo' | 'video'
            $table->string('attachment_disk')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
