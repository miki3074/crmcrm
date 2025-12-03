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
        Schema::table('task_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('producer_id')->nullable()->after('project_id');
            $table->unsignedBigInteger('buyer_id')->nullable()->after('producer_id');

            $table->foreign('producer_id')->references('id')->on('producers')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('buyers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_templates', function (Blueprint $table) {
            //
        });
    }
};
