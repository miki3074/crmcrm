<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meeting_documents', function (Blueprint $table) {
            // Если полей нет — добавляем
            if (!Schema::hasColumn('meeting_documents', 'task_id')) {
                $table->foreignId('task_id')
                    ->nullable()
                    ->constrained('tasks')
                    ->nullOnDelete()
                    ->after('type');
            }

            if (!Schema::hasColumn('meeting_documents', 'subtask_id')) {
                $table->foreignId('subtask_id')
                    ->nullable()
                    ->constrained('subtasks')
                    ->nullOnDelete()
                    ->after('task_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meeting_documents', function (Blueprint $table) {

            // Сначала удаляем FK, потом столбец
            if (Schema::hasColumn('meeting_documents', 'task_id')) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            }

            if (Schema::hasColumn('meeting_documents', 'subtask_id')) {
                $table->dropForeign(['subtask_id']);
                $table->dropColumn('subtask_id');
            }

        });
    }
};
