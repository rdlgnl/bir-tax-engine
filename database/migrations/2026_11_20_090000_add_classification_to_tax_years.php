<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The size class the BIR assigned the taxpayer for a year (micro, small,
     * medium or large), which decides the reduced penalties.
     */
    public function up(): void
    {
        Schema::table('tax_years', function (Blueprint $table) {
            $table->string('classification', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tax_years', function (Blueprint $table) {
            $table->dropColumn('classification');
        });
    }
};
