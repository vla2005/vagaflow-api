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
        Schema::create('source_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 40)->default('meu_padrinho');
            $table->string('source_key');
            $table->string('level', 20);
            $table->string('title');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('work_mode')->nullable();
            $table->text('url')->nullable();
            $table->json('payload');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['source', 'source_key']);
            $table->index(['level', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('source_jobs');
    }
};
