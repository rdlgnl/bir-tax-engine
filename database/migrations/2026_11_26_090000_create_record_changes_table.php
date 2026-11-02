<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every change to the taxpayer's records, appended and never edited:
     * what changed, from what to what, when, and through which screen.
     */
    public function up(): void
    {
        Schema::create('record_changes', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 40);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event', 10);
            $table->string('label');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('cause', 80)->nullable();
            $table->dateTime('at');

            $table->index(['subject', 'subject_id']);
            $table->index('at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_changes');
    }
};
