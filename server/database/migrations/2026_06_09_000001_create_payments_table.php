<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('method')->default('card'); // cash | check | card | ach | other
            $table->string('reference')->nullable();    // check #, transaction id, etc.
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['paid_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
