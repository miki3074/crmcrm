<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_documents', function (Blueprint $table) {
            $table->id();

            // Тип документа: agenda = повестка, protocol = протокол
            $table->enum('type', ['agenda', 'protocol']);

            // Автоматический номер (порядковый для типа)
            $table->unsignedInteger('number');

            // Дата документа (по умолчанию — сегодня)
            $table->date('document_date')->default(DB::raw('CURRENT_DATE'));

            // Привязка к задаче
            $table->unsignedBigInteger('task_id')->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('set null');

            // Кто создал
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            // Основной контент (можно заменить на JSON, если надо сложную структуру)
            $table->text('title')->nullable();   // заголовок встречи
            $table->longText('body')->nullable(); // текст повестки/протокола

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_documents');
    }
};

