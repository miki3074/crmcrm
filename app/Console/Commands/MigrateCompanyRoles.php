<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class MigrateCompanyRoles extends Command
{
    protected $signature = 'app:migrate-company-roles';
    protected $description = 'Миграция существующих ролей в pivot company_user';

    public function handle()
    {
        $this->info('Start migration...');

        // 1) владельцы компаний
        Company::with('user')->chunk(100, function ($companies) {
            foreach ($companies as $company) {
                if (!$company->user) continue;
                $company->usersPivot()->syncWithoutDetaching([
                    $company->user->id => [
                        'role' => 'owner',
                        'created_by' => $company->user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ]);
            }
        });

        // 2) пользователи, у которых company_id указан
        User::whereNotNull('company_id')->chunk(200, function ($users) {
            foreach ($users as $user) {
                $companyId = $user->company_id;
                if (!$companyId) continue;
                // если у пользователя глобальная роль manager — сделаем manager в pivot
                $role = $user->hasRole('manager') ? 'manager' : 'employee';
                \App\Models\Company::find($companyId)?->usersPivot()->syncWithoutDetaching([
                    $user->id => [
                        'role' => $role,
                        'created_by' => $user->created_by ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                ]);
            }
        });

        $this->info('Migration finished.');
    }
}
