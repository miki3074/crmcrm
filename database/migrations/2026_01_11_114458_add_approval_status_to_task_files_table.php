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
            // Статусы: pending (на проверке), approved (согласовано), rejected (на доработке)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('file_path');

            // Комментарий (причина отказа)
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('task_files', function (Blueprint $table) {
            $table->dropColumn(['status', 'rejection_reason']);
        });
    }
};
