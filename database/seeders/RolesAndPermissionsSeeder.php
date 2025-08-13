<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Очистка кэша (важно!)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Создание ролей
        $admin    = Role::firstOrCreate(['name' => 'admin']);
        $manager  = Role::firstOrCreate(['name' => 'manager']);
        $employee = Role::firstOrCreate(['name' => 'employee']);

        // (Опционально) Создание прав
        Permission::firstOrCreate(['name' => 'view projects']);
        Permission::firstOrCreate(['name' => 'edit tasks']);
        Permission::firstOrCreate(['name' => 'assign tasks']);

        // Назначение прав ролям
        $admin->givePermissionTo(Permission::all());
        $manager->givePermissionTo(['view projects', 'assign tasks']);
        $employee->givePermissionTo(['view projects']);

        // Назначение роли пользователю (например, первому пользователю)
        $user = User::find(1);
        if ($user) {
            $user->assignRole('admin');
        }
    }
}
