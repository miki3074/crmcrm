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
        Schema::create('klient_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('klient_id')->constrained('klients')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Кто загрузил
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('klient_files');
    }
};
