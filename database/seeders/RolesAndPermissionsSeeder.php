<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_applications',
            'validate_applications',
            'manage_exams',
            'view_exam_results',
            'manage_dispatch_requests',
            'send_dispatch_offers',
            'generate_indentures',
            'manage_users',
            'view_audit_logs',
            'edit_system_settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $applicantRole = Role::create(['name' => 'applicant']);

        $chiefRole = Role::create(['name' => 'chief']);
        $chiefRole->givePermissionTo(['manage_dispatch_requests']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view_applications',
            'validate_applications',
            'manage_exams',
            'view_exam_results',
            'manage_dispatch_requests',
            'send_dispatch_offers',
            'generate_indentures',
        ]);

        $superAdminRole = Role::create(['name' => 'super_admin']);
        $superAdminRole->givePermissionTo(Permission::all());
    }
}
