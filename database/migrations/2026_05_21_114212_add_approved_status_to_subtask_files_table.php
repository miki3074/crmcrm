<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('subtask_files', function (Blueprint $table) {
            // Просто varchar, не мучаемся с enum
            $table->string('approval_status', 20)->default('pending')->after('status');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');

            // Добавляем индекс для быстрого поиска
            $table->index('approval_status');
        });

        // Обновляем существующие записи
        \DB::table('subtask_files')->update(['approval_status' => 'pending']);
    }

    public function down()
    {
        Schema::table('subtask_files', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approved_at', 'approved_by']);
            $table->dropIndex(['approval_status']);
        });
    }
};
