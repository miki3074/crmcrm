<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            // Название договора (или проекта)
            $table->string('title');

            // Контрагент
            $table->string('counterparty')->nullable();

            // Сумма
            $table->decimal('amount', 15, 2)->nullable();

            // Статус: переговоры / заключен / отказались
            $table->enum('status', ['new','negotiation', 'signed', 'rejected'])
                ->default('negotiation');

            // Дата заключения (если "signed")
            $table->date('signed_at')->nullable();

            // Файл договора
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();

            // Кто создал
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
