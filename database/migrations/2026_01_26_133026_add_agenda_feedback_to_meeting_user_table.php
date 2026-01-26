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
        // Замените 'meeting_user' на реальное название, например 'meeting_participants'
        Schema::table('meeting_participants', function (Blueprint $table) {
            $table->enum('agenda_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('agenda_comment')->nullable();
        });
    }

    public function down()
    {
        Schema::table('meeting_participants', function (Blueprint $table) {
            $table->dropColumn(['agenda_status', 'agenda_comment']);
        });
    }
};
