<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'executor_id')) {
                $table->dropForeign(['executor_id']);
                $table->dropColumn('executor_id');
            }

            if (Schema::hasColumn('tasks', 'responsible_id')) {
                $table->dropForeign(['responsible_id']);
                $table->dropColumn('responsible_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('executor_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('responsible_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
        });
    }
};
