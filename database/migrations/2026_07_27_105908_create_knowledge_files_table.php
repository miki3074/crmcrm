<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'knowledge_files',
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
                    ->foreignId('uploaded_by')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('article_id')
                    ->nullable()
                    ->constrained('knowledge_articles')
                    ->nullOnDelete();

                $table->string('disk', 50)
                    ->default('local');

                $table->string('path');
                $table->string('original_name');
                $table->string('stored_name');
                $table->string('mime_type', 150)
                    ->nullable();

                $table->string('extension', 30)
                    ->nullable();

                $table
                    ->unsignedBigInteger('size')
                    ->default(0);

                /*
                 * image, video, audio, document, archive, other
                 */
                $table
                    ->string('category', 30)
                    ->default('other');

                $table->timestamps();

                $table->index([
                    'folder_id',
                    'created_at',
                ]);

                $table->index([
                    'article_id',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'knowledge_files'
        );
    }
};