<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('support_reply_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_reply_id')->constrained()->onDelete('cascade');

            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('support_reply_attachments');
    }
};
