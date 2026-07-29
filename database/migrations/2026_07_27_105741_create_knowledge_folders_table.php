<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_folders', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table
                ->foreignId('parent_id')
                ->nullable()
                ->constrained('knowledge_folders')
                ->cascadeOnDelete();

            $table
                ->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name', 255);

            /*
             * private — доступ только по ролям;
             * company — доступ всем сотрудникам компании.
             */
            $table
                ->string('access_type', 20)
                ->default('private');

            $table
                ->unsignedInteger('position')
                ->default(0);

            $table->timestamps();

            $table->index([
                'company_id',
                'parent_id',
                'position',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_folders');
    }
};