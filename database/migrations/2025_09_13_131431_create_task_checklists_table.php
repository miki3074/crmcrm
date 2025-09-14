<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('title'); // название пункта
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // ответственный за пункт
            $table->boolean('important')->default(false);
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });

        Schema::create('task_checklist_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('task_checklists')->cascadeOnDelete();
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklist_files');
        Schema::dropIfExists('task_checklists');
    }
};
