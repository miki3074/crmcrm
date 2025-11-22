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
    Schema::table('subtask_comments', function (Blueprint $table) {
        $table->json('mentions')->nullable()->after('comment');
    });
}

public function down()
{
    Schema::table('subtask_comments', function (Blueprint $table) {
        $table->dropColumn('mentions');
    });
}

};
