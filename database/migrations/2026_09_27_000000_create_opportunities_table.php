<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('level', 20);
            $table->string('title');
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('work_mode')->nullable();
            $table->text('url')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('status', 20)->default('new');
            $table->json('payload');
            $table->timestamp('discovered_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'fingerprint']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['user_id', 'level', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
