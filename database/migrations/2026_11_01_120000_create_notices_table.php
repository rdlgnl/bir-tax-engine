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
        // Things that happened, for the notifications page: a return filed, a
        // 2307 matched, an estimate that moved, a rule pack installed.
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 30);
            $table->string('title');
            $table->string('detail')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
