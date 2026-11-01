<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The choices a year was taxed on: the method elected on its Q1 return,
     * and what the taxpayer was registered for. A blank keeps the profile's
     * current value.
     */
    public function up(): void
    {
        Schema::create('tax_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('regime', 20)->nullable();
            $table->string('deduction_method', 20)->nullable();
            $table->boolean('vat_registered')->nullable();
            $table->boolean('registered_percentage_tax')->nullable();
            $table->timestamps();
        });

        // Each Q1 income tax return already filed shows the method chosen for its year.
        $returns = DB::table('filings')->where('form', '1701Q')->where('quarter', 1)->where('amendment', 0)->get(['year', 'snapshot']);

        foreach ($returns as $return) {
            $snapshot = json_decode((string) $return->snapshot, true);

            if (is_array($snapshot) && isset($snapshot['regime'])) {
                DB::table('tax_years')->insert([
                    'year' => $return->year,
                    'regime' => $snapshot['regime'],
                    'deduction_method' => $snapshot['deduction_method'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_years');
    }
};
