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
        Schema::table('klients', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained('companies')->onDelete('set null');
            $table->foreignId('project_id')->nullable()->after('company_id')->constrained('projects')->onDelete('set null');
            $table->foreignId('task_id')->nullable()->after('project_id')->constrained('tasks')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('klients', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['project_id']);
            $table->dropForeign(['task_id']);
            $table->dropColumn(['company_id', 'project_id', 'task_id']);
        });
    }
};
