<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A corporation the taxpayer runs, with each quarter's figures from its
     * own books and whether its 1702Q was filed.
     */
    public function up(): void
    {
        Schema::create('corporations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('tin', 20)->nullable();
            $table->string('rdo_code', 3)->nullable();
            $table->string('registered_address')->nullable();
            $table->string('zip_code', 4)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->unsignedSmallInteger('operations_began');
            $table->decimal('total_assets', 15, 2)->default(0);
            $table->string('deduction_method', 10)->default('itemized');
            $table->boolean('vat_registered')->default(false);
            $table->boolean('withholding_agent')->default(false);
            $table->unsignedSmallInteger('employees')->default(0);
            $table->timestamps();
        });

        Schema::create('corporation_quarters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->decimal('sales', 15, 2)->default(0);
            $table->decimal('cost_of_sales', 15, 2)->default(0);
            $table->decimal('other_income', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('withheld', 15, 2)->default(0);
            $table->date('filed_on')->nullable();
            $table->string('reference_number', 40)->nullable();
            $table->decimal('amount_paid', 15, 2)->nullable();
            $table->timestamps();
            $table->unique(['corporation_id', 'year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporation_quarters');
        Schema::dropIfExists('corporations');
    }
};
