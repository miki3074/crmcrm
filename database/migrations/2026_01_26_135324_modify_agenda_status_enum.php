<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // В Laravel изменение ENUM через Schema работает плохо, лучше использовать сырой SQL
        // Убедитесь, что название таблицы правильное (meeting_participants)
        DB::statement("ALTER TABLE meeting_participants MODIFY COLUMN agenda_status ENUM('pending', 'approved', 'rejected', 'fixed') NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE meeting_participants MODIFY COLUMN agenda_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
