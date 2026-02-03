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
        Schema::table('meetings', function (Blueprint $table) {
            // Добавляем поле task_id (nullable, так как совещание может быть без задачи)
            // Размещаем после поля agenda (или любого другого по вашему выбору)
            $table->foreignId('task_id')
                ->nullable()
                ->after('agenda') // Укажите поле, после которого вставить
                ->constrained('tasks')
                ->onDelete('set null'); // Если задачу удалят, в совещании поле станет NULL (история сохранится)

            // Добавляем поле subtask_id
            $table->foreignId('subtask_id')
                ->nullable()
                ->after('task_id')
                ->constrained('subtasks')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Удаляем внешние ключи и сами поля при откате
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');

            $table->dropForeign(['subtask_id']);
            $table->dropColumn('subtask_id');
        });
    }
};
