<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('progress')->default(0)->after('due_date'); // 0..100
        });
    }
    public function down(): void {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->dropColumn('progress');
        });
    }
};
