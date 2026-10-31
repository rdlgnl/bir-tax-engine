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
        // For VAT-registered taxpayers: amounts stay net of VAT, the VAT sits
        // beside them, and the other party's TIN goes on the VAT lists (SLSP).
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('vat_kind', 12)->nullable()->after('tax_withheld');
            $table->decimal('vat_amount', 15, 2)->default(0)->after('vat_kind');
            $table->string('counterparty_tin', 20)->nullable()->after('counterparty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['vat_kind', 'vat_amount', 'counterparty_tin']);
        });
    }
};
