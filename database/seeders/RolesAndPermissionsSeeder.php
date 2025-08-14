<?php

// database/seeders/RolesAndPermissionsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // сброс кеша прав
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ВСЕГДА указываем guard_name = web
        $admin    = Role::firstOrCreate(['name' => 'admin',    'guard_name' => 'web']);
        $manager  = Role::firstOrCreate(['name' => 'manager',  'guard_name' => 'web']);
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'view projects', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit tasks',    'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'assign tasks',  'guard_name' => 'web']);

        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo(['view projects', 'assign tasks']);
        $employee->givePermissionTo(['view projects']);

        // Можно сразу выдать роль первому пользователю, если есть
        if ($user = User::find(1)) {
            $user->assignRole('admin');
        }
    }
}
