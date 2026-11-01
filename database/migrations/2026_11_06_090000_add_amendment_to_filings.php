<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An amended return is filed beside the original: 0 is the original,
     * 1 the first amendment, and so on.
     */
    public function up(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            $table->unsignedTinyInteger('amendment')->default(0)->after('form');
            $table->dropUnique(['year', 'quarter', 'month', 'form']);
            $table->unique(['year', 'quarter', 'month', 'form', 'amendment']);
        });
    }

    public function down(): void
    {
        Schema::table('filings', function (Blueprint $table) {
            $table->dropUnique(['year', 'quarter', 'month', 'form', 'amendment']);
            $table->unique(['year', 'quarter', 'month', 'form']);
            $table->dropColumn('amendment');
        });
    }
};
