<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * People the taxpayer employs: their monthly taxable pay, from which tax
     * is withheld and remitted on the 1601-C.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('last_name', 60);
            $table->string('first_name', 60);
            $table->string('middle_name', 60)->nullable();
            $table->string('tin', 20)->nullable();
            $table->string('position', 80)->nullable();
            $table->decimal('monthly_pay', 15, 2);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
