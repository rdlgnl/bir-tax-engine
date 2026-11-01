<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A 2307 the taxpayer linked to their payments although the amounts
     * differ, so it isn't unlinked for not matching.
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->boolean('linked_by_hand')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('linked_by_hand');
        });
    }
};
