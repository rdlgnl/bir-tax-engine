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
        // The figures as they were when the return was filed, so history shows
        // what was actually filed even after records or rules change.
        Schema::table('filings', function (Blueprint $table) {
            $table->json('snapshot')->nullable()->after('payment_channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });
    }
};
