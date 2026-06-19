<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // machine key the app sends against
            $table->string('name');            // human label
            $table->string('title');           // push title (supports {placeholders})
            $table->text('body');              // push body (supports {placeholders})
            $table->string('channel')->default('alerts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed the job skip/cancel templates (issue #14). Existing hardcoded copy
        // is preserved as the default text, now editable by an admin.
        $defaults = [
            [
                'key' => 'job_skipped',
                'name' => 'Job skipped',
                'title' => 'Job skipped',
                'body' => '{job_title} ({scheduled_date}) was skipped.',
                'channel' => 'alerts',
            ],
            [
                'key' => 'job_cancelled',
                'name' => 'Job canceled',
                'title' => 'Job canceled',
                'body' => '{job_title} ({scheduled_date}) was canceled.',
                'channel' => 'alerts',
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('notification_templates')->updateOrInsert(
                ['key' => $row['key']],
                array_merge($row, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]),
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
