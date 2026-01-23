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
        Schema::table('subtask_files', function (Blueprint $table) {
            // Статус файла: 'ok' (или null) - обычный, 'revision' - на доработке
            $table->string('status')->default('ok')->after('path');
            // Комментарий ответственного
            $table->text('revision_comment')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('subtask_files', function (Blueprint $table) {
            $table->dropColumn(['status', 'revision_comment']);
        });
    }
};
