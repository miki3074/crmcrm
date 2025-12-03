<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_template_files', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('task_template_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->timestamps();

            $table->foreign('task_template_id')
                ->references('id')->on('task_templates')
                ->onDelete('cascade');

            $table->foreign('uploaded_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_template_files');
    }
};
