<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE task_files
            MODIFY COLUMN status
            ENUM('none', 'pending', 'approved', 'rejected', 'replacement')
            DEFAULT 'none'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE task_files
            MODIFY COLUMN status
            ENUM('none', 'pending', 'approved', 'rejected')
            DEFAULT 'none'
        ");
    }
};