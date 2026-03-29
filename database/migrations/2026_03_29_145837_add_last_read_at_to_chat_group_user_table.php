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
        // Если таблицы еще нет, используйте Schema::create
        // Если есть, используйте Schema::table
        Schema::table('chat_group_user', function (Blueprint $table) {
            $table->timestamp('last_read_at')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_group_user', function (Blueprint $table) {
            //
        });
    }
};
