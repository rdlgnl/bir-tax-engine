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
        // Tax the taxpayer withheld, as a withholding agent, from someone they
        // paid. Kept apart from tax_withheld, which is tax withheld from them.
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('atc', 10)->nullable()->after('vat_amount');
            $table->decimal('agent_withheld', 15, 2)->default(0)->after('atc');
        });

        // The 0619-E is monthly: two in each quarter.
        Schema::table('filings', function (Blueprint $table) {
            $table->unsignedTinyInteger('month')->default(0)->after('quarter');
            $table->dropUnique(['year', 'quarter', 'form']);
            $table->unique(['year', 'quarter', 'month', 'form']);
        });

        // The 2307s the taxpayer gave the people they withheld from.
        Schema::create('issued_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->string('payee');
            $table->date('given_on');
            $table->timestamps();

            $table->unique(['year', 'quarter', 'payee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issued_certificates');

        Schema::table('filings', function (Blueprint $table) {
            $table->dropUnique(['year', 'quarter', 'month', 'form']);
            $table->unique(['year', 'quarter', 'form']);
            $table->dropColumn('month');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['atc', 'agent_withheld']);
        });
    }
};
