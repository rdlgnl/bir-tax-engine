<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 2316 an employer gives for a year: the salary and the tax they
     * withheld, as it goes on the annual return.
     */
    public function up(): void
    {
        Schema::create('compensation_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('employer_name', 120);
            $table->string('employer_tin', 20)->nullable();
            $table->decimal('gross_compensation', 15, 2);
            $table->decimal('non_taxable_compensation', 15, 2)->default(0);
            $table->decimal('tax_withheld', 15, 2);
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_certificates');
    }
};
