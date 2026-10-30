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
        // What the official BIR forms ask for beyond name, TIN and RDO. The
        // name is split because the forms want "Last Name, First Name, Middle
        // Name"; full_name stays as the name the app shows.
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('full_name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->date('birth_date')->nullable()->after('middle_name');
            $table->string('email')->nullable()->after('birth_date');
            $table->string('contact_number', 30)->nullable()->after('email');
            $table->string('citizenship', 40)->nullable()->after('contact_number');
            $table->string('registered_address')->nullable()->after('city');
            $table->string('zip_code', 4)->nullable()->after('registered_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tax_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'last_name', 'first_name', 'middle_name', 'birth_date', 'email',
                'contact_number', 'citizenship', 'registered_address', 'zip_code',
            ]);
        });
    }
};
