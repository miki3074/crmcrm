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
       
Schema::create('interactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('client_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['call', 'meeting', 'email', 'comment']);
    $table->text('content')->nullable(); // текст звонка / письма / комментария
    $table->timestamp('interaction_date')->default(now());
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // кто добавил
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
