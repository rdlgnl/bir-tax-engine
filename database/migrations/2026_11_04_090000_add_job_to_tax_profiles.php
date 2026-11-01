<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A taxpayer with a job and a business: who employs them and their
     * monthly taxable salary, for the annual return.
     */
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('employer_name', 120)->nullable()->after('income_source');
            $table->decimal('monthly_salary', 15, 2)->nullable()->after('employer_name');
        });
    }

    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn(['employer_name', 'monthly_salary']);
        });
    }
};
