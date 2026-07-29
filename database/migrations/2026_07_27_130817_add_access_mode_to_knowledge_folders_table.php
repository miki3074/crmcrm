<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_folders', function (Blueprint $table) {
            $table->string('access_mode', 20)
                ->default('all')
                ->after('parent_id');

            $table->index(['company_id', 'access_mode']);
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_folders', function (Blueprint $table) {
            $table->dropIndex([
                'company_id',
                'access_mode',
            ]);

            $table->dropColumn('access_mode');
        });
    }
};
