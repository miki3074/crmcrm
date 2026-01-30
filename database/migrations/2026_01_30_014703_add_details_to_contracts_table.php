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
        Schema::table('contracts', function (Blueprint $table) {
            // Тип договора:
            // dealer - дилерский (мы покупаем у производителя)
            // agency - агентский (мы продаем за % от имени кого-то)
            // sale - прямая продажа
            // purchase - закупка
            $table->enum('type', ['general', 'dealer', 'agency', 'sale', 'purchase'])
                ->default('general')
                ->after('title');

            // Наша прибыль/комиссия (может быть null, если это расходный договор)
            $table->decimal('margin', 15, 2)->nullable()->after('amount');

            // Дата окончания действия договора (для отслеживания актуальности дилерства)
            $table->date('valid_until')->nullable()->after('signed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            //
        });
    }
};
