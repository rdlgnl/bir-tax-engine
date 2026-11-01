<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Loans the taxpayer signed: each owes documentary stamp tax, filed on
     * Form 2000 unless the lender (usually a bank) files it.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->date('signed_on');
            $table->decimal('amount', 15, 2);
            $table->string('lender', 120);
            $table->string('lender_tin', 20)->nullable();
            $table->string('lender_kind', 10);
            $table->unsignedSmallInteger('term_months');
            $table->date('filed_on')->nullable();
            $table->string('reference_number', 60)->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
