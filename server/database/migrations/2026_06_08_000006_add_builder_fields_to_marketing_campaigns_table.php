<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table): void {
            $table->string('template')->default('announcement')->after('subject');
            // Editable content blocks (headline, body, image, button, etc.).
            $table->json('content')->nullable()->after('template');
            // Recipient targeting: explicit customer ids + customer tag groups.
            $table->json('recipient_tags')->nullable()->after('content');
            $table->json('recipient_customer_ids')->nullable()->after('recipient_tags');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['template', 'content', 'recipient_tags', 'recipient_customer_ids']);
        });
    }
};
