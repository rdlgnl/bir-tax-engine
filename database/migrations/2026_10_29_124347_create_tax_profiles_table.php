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
        // One row: the app is single-user and keeps no accounts.
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('income_source')->nullable();
            $table->string('work_type')->nullable();
            $table->string('profession')->nullable();
            $table->string('full_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('line_of_business')->nullable();
            $table->string('city')->nullable();
            $table->boolean('bir_registered')->default(true);
            $table->string('tin', 15)->nullable();
            $table->string('rdo_code', 3)->nullable();
            $table->date('registered_on')->nullable();
            $table->boolean('registered_income_tax')->default(true);
            $table->boolean('registered_percentage_tax')->default(false);
            $table->boolean('vat_registered')->default(false);
            $table->boolean('withholding_agent')->default(false);
            $table->decimal('expected_income', 15, 2)->nullable();
            $table->string('regime')->nullable();
            $table->string('deduction_method')->nullable();
            $table->unsignedTinyInteger('expected_expense_percent')->nullable();
            $table->string('clients_withhold')->nullable();
            $table->timestamp('setup_completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_profiles');
    }
};
