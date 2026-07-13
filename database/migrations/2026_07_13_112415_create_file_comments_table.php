<?php
// database/migrations/xxxx_xx_xx_create_file_comments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_file_id')->constrained('task_files')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('comment');
            $table->enum('type', ['rejection', 'feedback', 'note'])->default('rejection');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_comments');
    }
};
