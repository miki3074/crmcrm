<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_plan_items', function (Blueprint $table) {
            $table->text('materials_url')
                ->nullable()
                ->after('format');

            $table->text('responsible_text')
                ->nullable()
                ->after('kpi');

            $table->unsignedInteger('sort_order')
                ->default(0)
                ->after('media_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('media_plan_items', function (Blueprint $table) {
            $table->dropColumn([
                'materials_url',
                'responsible_text',
                'sort_order',
            ]);
        });
    }
};