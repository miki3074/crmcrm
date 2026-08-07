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
       Schema::create('media_plan_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('media_plan_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('city_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    $table->foreignId('radio_station_id')
        ->nullable()
        ->constrained()
        ->nullOnDelete();

    /*
     * radio, social, offline, award, other
     */
    $table->string('type')->default('radio');

    /*
     * Для нерадио-активностей:
     * VK, Telegram, очная встреча и так далее.
     */
    $table->string('platform_name')->nullable();

    $table->text('format')->nullable();

    $table->unsignedInteger('duration_seconds')
        ->nullable();

    $table->unsignedInteger('outputs_per_day')
        ->default(0);

    $table->unsignedInteger('days_count')
        ->default(0);

    $table->unsignedInteger('total_outputs')
        ->default(0);

    $table->decimal('price_per_second', 12, 2)
        ->default(0);

    $table->decimal('total_price', 15, 2)
        ->default(0);

    $table->text('kpi')->nullable();

    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_plan_items');
    }
};
