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
        Schema::create('flutter_messages', function (Blueprint $table) {
            $table->id();
            // ID пользователя, который отправил сообщение
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Текст сообщения
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flutter_messages');
    }
};
