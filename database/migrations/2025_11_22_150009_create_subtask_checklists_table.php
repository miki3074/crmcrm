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
    Schema::create('subtask_checklists', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('subtask_id');
        $table->string('title');
        $table->boolean('completed')->default(false);
        $table->unsignedBigInteger('responsible_id')->nullable(); // кто отвечает
        $table->unsignedBigInteger('creator_id'); // кто создал
        $table->timestamps();

        $table->foreign('subtask_id')->references('id')->on('subtasks')->onDelete('cascade');
        $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
        $table->foreign('creator_id')->references('id')->on('users')->cascadeOnDelete();
    });
}

};
