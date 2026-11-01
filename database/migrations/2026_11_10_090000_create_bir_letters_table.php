<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Letters from the BIR the taxpayer kept: what kind, the return they
     * name, when to reply, and a photo kept on this computer.
     */
    public function up(): void
    {
        Schema::create('bir_letters', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->date('dated_on')->nullable();
            $table->string('office', 120)->nullable();
            $table->string('form', 10)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->date('reply_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->date('resolved_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bir_letters');
    }
};
