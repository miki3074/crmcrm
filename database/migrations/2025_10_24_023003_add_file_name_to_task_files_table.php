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
    Schema::table('task_files', function (Blueprint $table) {
        $table->string('file_name')->after('file_path')->nullable();
    });
}

public function down()
{
    Schema::table('task_files', function (Blueprint $table) {
        $table->dropColumn('file_name');
    });
}

};
