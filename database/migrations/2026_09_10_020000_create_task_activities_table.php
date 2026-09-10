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
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            // Получатель события — одна строка на одного пользователя,
            // это и есть его лента "Событий" на дашборде.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type');
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
