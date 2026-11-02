<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an invoice was paid, so a sale can count when it's collected,
     * and the books' method, invoice date (accrual) or cash, per tax year.
     * Invoices already marked paid didn't record the day: their invoice
     * date stands in for it.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('invoice_paid_on')->nullable()->after('invoice_paid');
        });

        DB::table('transactions')->where('invoice_paid', true)->update(['invoice_paid_on' => DB::raw('occurred_on')]);

        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('books_method', 10)->default('accrual')->after('books_type');
        });

        Schema::table('tax_years', function (Blueprint $table) {
            $table->string('books_method', 10)->nullable()->after('registered_percentage_tax');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', fn (Blueprint $table) => $table->dropColumn('invoice_paid_on'));
        Schema::table('tax_profiles', fn (Blueprint $table) => $table->dropColumn('books_method'));
        Schema::table('tax_years', fn (Blueprint $table) => $table->dropColumn('books_method'));
    }
};
