<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'knowledge_articles',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('company_id')
                    ->constrained('companies')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('folder_id')
                    ->constrained('knowledge_folders')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('created_by')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('title');

                /*
                 * Основной формат Tiptap.
                 */
                $table->json('content');

                /*
                 * Необязательно, но удобно для поиска
                 * и быстрого отображения.
                 */
                $table->longText('content_text')
                    ->nullable();

                $table
                    ->string('status', 20)
                    ->default('published');

                $table
                    ->unsignedInteger('position')
                    ->default(0);

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'position',
                ]);

                $table->index([
                    'company_id',
                    'status',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'knowledge_articles'
        );
    }
};