<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per outbound SMS attempt — including the ones that never left the
     * building. A send that is skipped (no credentials, opted-out customer, bad
     * number) is recorded just like a delivered one, because "why didn't the
     * customer get a text?" is the question this table exists to answer.
     *
     * `message_sid` is Twilio's handle for the message; the status webhook uses it
     * to walk a row from queued -> sent -> delivered (or failed/undelivered).
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();

            // job_scheduled, invoice_issued, opt_in_request, chat_reply,
            // test:job_scheduled, … — matches the sms_templates key where there is one.
            $table->string('template_key')->nullable();
            $table->string('to_number', 32);
            $table->text('body');
            $table->string('message_sid', 64)->nullable()->unique();

            // queued|sent|delivered|failed|undelivered|skipped
            $table->string('status', 32)->default('queued');
            $table->string('reason', 64)->nullable();       // why a skipped row was skipped
            $table->string('error_code', 32)->nullable();   // Twilio error code, e.g. 21610
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('template_key');
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
