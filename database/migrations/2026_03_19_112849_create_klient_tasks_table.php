<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Таблица задач
        Schema::create('klient_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_id')->constrained('klients')->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users'); // Кто создал задачу
            $table->foreignId('responsible_id')->constrained('users'); // Ответственный

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();

            // Приоритет: high, medium, low
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');

            // Тип: call, meeting, email, document, other
            $table->string('type')->default('other');

            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamps();
        });

        // Таблица файлов для задач
        Schema::create('klient_task_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_task_id')->constrained('klient_tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('klient_task_files');
        Schema::dropIfExists('klient_tasks');
    }
};
