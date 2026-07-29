<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_company_roles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');

            $table->string('role')->default('viewer');

            $table->unsignedBigInteger('assigned_by')->nullable();

            $table->timestamps();

            $table->unique(
                ['company_id', 'user_id'],
                'knowledge_company_user_unique'
            );

            $table
                ->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table
                ->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table
                ->foreign('assigned_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_company_roles');
    }
};