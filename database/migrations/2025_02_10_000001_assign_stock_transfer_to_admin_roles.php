<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Assigns stock_transfer permissions to all Admin roles (Admin#business_id).
     */
    public function up(): void
    {
        $guard = 'web';
        $names = [
            'stock_transfer.view',
            'stock_transfer.create',
            'stock_transfer.update',
            'stock_transfer.delete',
            'stock_transfer.view_own',
        ];

        $adminRoles = Role::where('name', 'like', 'Admin#%')->where('guard_name', $guard)->get();
        $permissions = Permission::whereIn('name', $names)->where('guard_name', $guard)->pluck('name')->all();

        foreach ($adminRoles as $role) {
            foreach ($permissions as $permName) {
                if (! $role->hasPermissionTo($permName)) {
                    $role->givePermissionTo($permName);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا نزيل الصلاحيات من أدوار Admin عند الـ rollback
    }
};
