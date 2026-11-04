<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The eFPS group a taxpayer e-files under (A to E, by industry; blank
     * for eBIRForms), and for the person, the first month whose returns
     * went through eFPS (blank for every month).
     */
    public function up(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('efps_group', 1)->nullable();
            $table->date('efps_from')->nullable();
        });

        Schema::table('corporations', function (Blueprint $table) {
            $table->string('efps_group', 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn(['efps_group', 'efps_from']);
        });

        Schema::table('corporations', function (Blueprint $table) {
            $table->dropColumn('efps_group');
        });
    }
};
