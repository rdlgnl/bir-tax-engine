<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The taxpayer's own changes to a year's holidays, over the rule pack's
     * list: a day added or renamed, or a listed day that isn't a holiday.
     */
    public function up(): void
    {
        Schema::create('holiday_changes', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('name', 80)->nullable();
            $table->boolean('holiday');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_changes');
    }
};
