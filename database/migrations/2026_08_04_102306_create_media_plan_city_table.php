<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_plan_city', function (Blueprint $table) {
            $table->id();

            $table->foreignId('media_plan_id')
                ->constrained('media_plans')
                ->cascadeOnDelete();

            $table->foreignId('city_id')
                ->constrained('cities')
                ->cascadeOnDelete();

            $table->unique([
                'media_plan_id',
                'city_id',
            ], 'media_plan_city_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_plan_city');
    }
};