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
        Schema::create('clients', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null'); // привязка к компании
    $table->string('name'); // ФИО
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->text('notes')->nullable(); // заметки
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
