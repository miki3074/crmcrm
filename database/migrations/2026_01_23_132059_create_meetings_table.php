<?php

// database/migrations/xxxx_xx_xx_create_meetings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // К какой компании относится
            $table->foreignId('creator_id')->constrained('users'); // Кто создал (технически)
            $table->foreignId('responsible_id')->nullable()->constrained('users'); // Ответственный (секретарь/организатор)

            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            // Этапы: agenda (повестка), protocol (протокол)
            $table->longText('agenda')->nullable();
            $table->longText('minutes')->nullable(); // Протокол по итогам

            // Статусы: draft (черновик), scheduled (назначено), completed (проведено), on_approval (на согласовании), signed (подписано)
            $table->string('status')->default('draft');

            $table->timestamps();
        });

        // Таблица участников
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Статус участия: invited, accepted, declined
            $table->string('status')->default('invited');

            // Подписал ли протокол?
            $table->boolean('is_signed')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('meetings');
    }
};
