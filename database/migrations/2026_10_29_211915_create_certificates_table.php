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
        // A client's Form 2307: one per client per quarter, covering every
        // payment they withheld tax from in that quarter.
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('payor_name');
            $table->string('payor_tin', 20)->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('tax_withheld', 15, 2);
            $table->string('atc_code', 10)->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->timestamps();

            $table->index(['year', 'quarter']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('certificate_id')->nullable()->after('certificate_received_on')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('certificate_id');
        });

        Schema::dropIfExists('certificates');
    }
};
