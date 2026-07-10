<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();     // job_scheduled, invoice_issued, …
            $table->string('name');              // human label in Settings → Notifications
            $table->text('body');                // supports {placeholders}
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // Seed the four events the office asked for, active-off until Twilio is
        // configured and the copy is reviewed. Bodies use {placeholders} the
        // office can edit; {name} is the customer's first name.
        $defaults = [
            [
                'key' => 'job_scheduled',
                'name' => 'Job scheduled',
                'body' => "Hi {name}, this is {company}. Your {service} is scheduled for {date}. Reply STOP to opt out, HELP for help.",
            ],
            [
                'key' => 'job_completed',
                'name' => 'Job completed',
                'body' => "Hi {name}, {company} has completed your {service}. Thank you for your business! Reply STOP to opt out.",
            ],
            [
                'key' => 'job_rescheduled',
                'name' => 'Job rescheduled or canceled',
                'body' => "Hi {name}, this is {company}. Your {service} scheduled for {date} has been {status}. We'll be in touch. Reply STOP to opt out.",
            ],
            [
                'key' => 'invoice_issued',
                'name' => 'Invoice issued',
                'body' => "Hi {name}, {company} has issued invoice {invoice_number} for {amount}. View it here: {link}. Reply STOP to opt out.",
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('sms_templates')->updateOrInsert(
                ['key' => $row['key']],
                array_merge($row, ['is_active' => false, 'created_at' => now(), 'updated_at' => now()]),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
