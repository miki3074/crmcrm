<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('task_checklists', function (Blueprint $table) {
            // Добавляем колонку created_by, которая ссылается на id в таблице users
            $table->foreignId('created_by')
                ->nullable() // Можно сделать nullable для старых записей
                ->constrained('users')
                ->onDelete('set null'); // Если пользователя удалят, поле станет null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_checklists', function (Blueprint $table) {
            //
        });
    }
};
