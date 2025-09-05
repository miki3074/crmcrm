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
            $table->boolean('completed')->default(false)->after('progress');
            $table->timestamp('completed_at')->nullable()->after('completed');
        });
    }
    public function down(): void {
        Schema::table('subtasks', function (Blueprint $table) {
            $table->dropColumn(['completed', 'completed_at']);
        });
    }
};
