<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Основная таблица сделок
        Schema::create('klient_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_id')->constrained('klients')->onDelete('cascade');
            $table->foreignId('contact_person_id')->nullable()->constrained('klient_contact_persons')->onDelete('set null');
            $table->foreignId('creator_id')->constrained('users');

            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->string('status'); // Этап (Переговоры и т.д.)
            $table->string('attribute'); // Атрибут (Продажа, Услуга и т.д.)
            $table->decimal('total_amount', 15, 2)->default(0); // Общая сумма сделки
            $table->timestamps();
        });

        // 2. Товары и услуги в сделке
        Schema::create('klient_deal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_deal_id')->constrained('klient_deals')->onDelete('cascade');
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0); // Считается автоматически
            $table->timestamps();
        });

        // 3. Ответственные (Многие ко многим)
        Schema::create('klient_deal_responsible', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_deal_id')->constrained('klient_deals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });

        // 4. Связь сделки с задачами (Многие ко многим)
        Schema::create('klient_deal_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_deal_id')->constrained('klient_deals')->onDelete('cascade');
            $table->foreignId('klient_task_id')->constrained('klient_tasks')->onDelete('cascade');
        });

        // 5. Файлы сделки
        Schema::create('klient_deal_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_deal_id')->constrained('klient_deals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('klient_deal_files');
        Schema::dropIfExists('klient_deal_task');
        Schema::dropIfExists('klient_deal_responsible');
        Schema::dropIfExists('klient_deal_items');
        Schema::dropIfExists('klient_deals');
    }
};
