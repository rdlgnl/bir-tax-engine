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
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('books_type')->nullable()->after('clients_withhold');
        });

        // One row per month the taxpayer has copied into their registered books.
        Schema::create('books_months', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamp('copied_at');
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books_months');

        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn('books_type');
        });
    }
};
