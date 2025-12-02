<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_attachmentstwo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('path');
            $table->string('mime_type', 100)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size')->nullable(); // в КБ, по желанию
            $table->timestamps();

            $table->foreign('message_id')
                ->references('id')->on('support_messagestwo')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachmentstwo');
    }
};
