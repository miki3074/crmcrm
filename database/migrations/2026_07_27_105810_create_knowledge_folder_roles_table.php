<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'knowledge_folder_roles',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('folder_id')
                    ->constrained('knowledge_folders')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->string('role', 30);

                $table
                    ->foreignId('assigned_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique([
                    'folder_id',
                    'user_id',
                ]);

                $table->index([
                    'user_id',
                    'role',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'knowledge_folder_roles'
        );
    }
};