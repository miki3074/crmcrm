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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('reminded_before3')->default(false);
            $table->boolean('reminded_today')->default(false);
            $table->boolean('reminded_after3')->default(false);
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['reminded_before3', 'reminded_today', 'reminded_after3']);
        });
    }
};
