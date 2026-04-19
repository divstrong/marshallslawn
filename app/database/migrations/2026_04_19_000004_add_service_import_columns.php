<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->string('parent_service')->nullable()->after('code');
            $table->string('service_mode')->nullable()->after('unit');
            $table->text('estimate_description')->nullable()->after('description');
            $table->text('invoice_description')->nullable()->after('estimate_description');
            $table->boolean('track_chemicals')->default(false)->after('is_active');
            $table->boolean('show_in_snow')->default(false)->after('track_chemicals');
            $table->decimal('minimum_amount', 10, 2)->default(0)->after('default_price');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'parent_service', 'service_mode',
                'estimate_description', 'invoice_description',
                'track_chemicals', 'show_in_snow', 'minimum_amount',
            ]);
        });
    }
};
