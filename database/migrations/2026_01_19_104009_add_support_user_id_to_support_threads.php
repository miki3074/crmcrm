<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_threads', function (Blueprint $table) {
            // Добавляем поле для ID сотрудника поддержки (может быть null, если сотрудников нет)
            $table->unsignedBigInteger('support_user_id')->nullable()->after('user_id');

            $table->foreign('support_user_id')
                ->references('id')->on('users')
                ->nullOnDelete(); // Если сотрудника удалят, тикет не удалится, а станет "ничейным"
        });
    }

    public function down(): void
    {
        Schema::table('support_threads', function (Blueprint $table) {
            $table->dropForeign(['support_user_id']);
            $table->dropColumn('support_user_id');
        });
    }
};
