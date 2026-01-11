<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // В миграции
    public function up()
    {
        // ВАЖНО: Изменение ENUM зависит от БД. Для MySQL это выглядит так:
        DB::statement("ALTER TABLE task_files MODIFY COLUMN status ENUM('none', 'pending', 'approved', 'rejected') DEFAULT 'none'");

        // Если у тебя PostgreSQL или другая БД, подход может отличаться.
        // Суть: добавить 'none' в список и сделать DEFAULT 'none'.
    }

    public function down()
    {
        // Возврат обратно (если нужно)
        DB::statement("ALTER TABLE task_files MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
    }
};
