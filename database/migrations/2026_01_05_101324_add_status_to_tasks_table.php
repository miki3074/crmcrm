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
        Schema::table('tasks', function (Blueprint $table) {
            // Добавляем статус: new (новая), in_work (в работе)
            // completed у вас уже есть отдельным полем, но можно дублировать логику или использовать status='done'
            // Для минимального вмешательства добавим 'status' для процесса
            $table->enum('status', ['new', 'in_work'])->default('new')->after('priority');
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
