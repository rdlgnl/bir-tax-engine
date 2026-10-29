<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->date('occurred_on');
            $table->string('counterparty');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_withheld', 15, 2)->default(0);
            $table->date('certificate_received_on')->nullable();
            $table->string('invoice_number', 40)->nullable();
            $table->boolean('invoice_paid')->default(false);
            $table->string('category')->nullable();
            $table->boolean('has_valid_invoice')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('occurred_on');
            $table->index(['type', 'occurred_on']);
            $table->index('counterparty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
