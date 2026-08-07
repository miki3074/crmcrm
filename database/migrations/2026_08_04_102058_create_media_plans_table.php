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
        Schema::create('media_plans', function (Blueprint $table) {
    $table->id();

    $table->foreignId('klient_id')
        ->constrained('klients')
        ->cascadeOnDelete();

    $table->foreignId('creator_id')
        ->constrained('users')
        ->cascadeOnDelete();

    $table->string('name');
    $table->text('description')->nullable();

    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();

    $table->string('status')->default('draft');

    $table->decimal('total_amount', 15, 2)
        ->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_plans');
    }
};
