<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляем новые поля в таблицу clients.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Тип клиента: юр. или физ. лицо
            $table->enum('type', ['jur', 'fiz'])->default('fiz')->after('id');

            // Дополнительные поля
            $table->string('organization_name')->nullable()->after('name');
            $table->string('city')->nullable()->after('organization_name');
            $table->string('address')->nullable()->after('city');

            // Связи
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null')->after('notes');
            $table->foreignId('responsible_id')->nullable()->constrained('users')->onDelete('set null')->after('project_id');
        });
    }

    /**
     * Откат миграции.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['type', 'organization_name', 'city', 'address']);
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->dropForeign(['responsible_id']);
            $table->dropColumn('responsible_id');
        });
    }
};
