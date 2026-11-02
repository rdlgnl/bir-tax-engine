<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What's left out of an employee's taxable pay (their share of SSS,
     * PhilHealth and Pag-IBIG, de minimis benefits, a minimum wage), and
     * what an earlier employer paid and withheld in the year they started.
     * The monthly pay recorded so far was already after contributions, so
     * contributions start at zero.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('contributions', 15, 2)->default(0)->after('monthly_pay');
            $table->decimal('de_minimis', 15, 2)->default(0)->after('contributions');
            $table->boolean('minimum_wage')->default(false)->after('de_minimis');
            $table->decimal('previous_pay', 15, 2)->nullable()->after('ended_on');
            $table->decimal('previous_withheld', 15, 2)->nullable()->after('previous_pay');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['contributions', 'de_minimis', 'minimum_wage', 'previous_pay', 'previous_withheld']);
        });
    }
};
