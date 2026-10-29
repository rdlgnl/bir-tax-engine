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
        Schema::create('filings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            // 1 to 3 are the quarterly returns, 4 is the annual return.
            $table->unsignedTinyInteger('quarter');
            $table->string('form', 10);
            $table->date('filed_on');
            $table->string('reference_number', 60)->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->date('paid_on')->nullable();
            $table->string('payment_channel')->nullable();
            $table->timestamps();

            $table->unique(['year', 'quarter', 'form']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filings');
    }
};
