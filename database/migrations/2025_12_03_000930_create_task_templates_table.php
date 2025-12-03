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
        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('project_id');

            $table->unsignedBigInteger('creator_id'); // кто создал шаблон

            $table->string('title');
            $table->text('description')->nullable();

            // JSON с массивами id пользователей
            $table->json('executor_ids')->nullable();    // исполнители по умолчанию
            $table->json('responsible_ids')->nullable(); // ответственные по умолчанию
            $table->json('watcher_ids')->nullable();     // наблюдатели по умолчанию

            // "Крайний срок через N дней после создания задачи"
            $table->unsignedInteger('due_in_days')->nullable(); // например 3, 7 и т.д.

            $table->enum('priority', ['low', 'medium', 'high'])->default('low');

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_templates');
    }
};
