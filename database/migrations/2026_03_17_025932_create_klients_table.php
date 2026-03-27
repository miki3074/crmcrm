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
        Schema::create('klients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название / ФИО
            $table->string('status'); // Действующий, потенциальный и т.д.
            $table->string('segment')->nullable(); // Сегмент
            $table->string('rating', 10)->nullable(); // A, B, C

            // Быстрые контакты
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('messengers')->nullable(); // Для хранения Telegram/WhatsApp

            // Реквизиты
            $table->string('inn')->nullable();
            $table->string('kpp')->nullable();
            $table->string('ogrn')->nullable();
            $table->text('legal_address')->nullable();
            $table->text('actual_address')->nullable();
            $table->string('industry')->nullable(); // Сфера деятельности

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klients');
    }
};
