<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moderator = Role::updateOrCreate(['name' => 'moderator']);
        $moderatePermission = Permission::updateOrCreate(['name' => 'moderate content']);
        $deletePermission = Permission::updateOrCreate(['name' => 'delete content']);
        $lockPermission = Permission::updateOrCreate(['name' => 'lock content']);

        $moderator->givePermissionTo($moderatePermission);
        $moderator->givePermissionTo($deletePermission);
        $moderator->givePermissionTo($lockPermission);
    }
}
