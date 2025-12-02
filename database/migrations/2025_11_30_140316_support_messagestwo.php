<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_messagestwo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body')->nullable(); // текст
            $table->boolean('is_support')->default(false); // сообщение от поддержки?
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('thread_id')
                ->references('id')->on('support_threads')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messagestwo');
    }
};
