<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds stock_transfer permissions and assigns them to roles that have purchase.view.
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

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['name' => $name, 'guard_name' => $guard]
            );
        }

        $purchaseViewPermission = Permission::where('name', 'purchase.view')->where('guard_name', $guard)->first();
        if (! $purchaseViewPermission) {
            return;
        }

        $rolesWithPurchase = Role::whereHas('permissions', function ($q) use ($guard) {
            $q->where('permissions.name', 'purchase.view')->where('permissions.guard_name', $guard);
        })->get();

        $stockTransferPermissions = Permission::whereIn('name', $names)->where('guard_name', $guard)->pluck('name')->all();

        foreach ($rolesWithPurchase as $role) {
            foreach ($stockTransferPermissions as $permName) {
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
        $guard = 'web';
        $names = [
            'stock_transfer.view',
            'stock_transfer.create',
            'stock_transfer.update',
            'stock_transfer.delete',
            'stock_transfer.view_own',
        ];

        $permissions = Permission::whereIn('name', $names)->where('guard_name', $guard)->get();
        foreach ($permissions as $permission) {
            $permission->roles()->detach();
        }
        Permission::whereIn('name', $names)->where('guard_name', $guard)->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
