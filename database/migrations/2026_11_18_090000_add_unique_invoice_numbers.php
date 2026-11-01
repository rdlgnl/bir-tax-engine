<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Each invoice number is used once among invoices, so a sale can't be
     * recorded twice. Payments may still quote the invoice they pay.
     */
    public function up(): void
    {
        DB::statement("CREATE UNIQUE INDEX transactions_invoice_number_unique ON transactions (invoice_number) WHERE type = 'invoice'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS transactions_invoice_number_unique');
    }
};
