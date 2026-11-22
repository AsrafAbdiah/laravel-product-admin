<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The single place where BandarOS's roles and permissions are defined.
 *
 * Roles are global (team_id = null) but are *assigned* per team, so a user
 * can be the owner of one shop and only staff in another.
 */
final class Roles
{
    public const OWNER = 'owner';

    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    public const VIEWER = 'viewer';

    /**
     * Every permission, with what it allows.
     */
    public const PERMISSIONS = [
        'products.view' => 'View products, categories and the dashboard',
        'products.create' => 'Add products',
        'products.update' => 'Edit products and stock',
        'products.delete' => 'Move products to the trash',
        'products.import' => 'Import products from a file',
        'products.export' => 'Export products to Excel',
        'trash.manage' => 'Restore or permanently delete products',
        'categories.manage' => 'Create, rename and delete categories',
        'team.manage' => 'Invite members and change their roles',
    ];

    /**
     * Which permissions each role has.
     */
    public const ROLE_PERMISSIONS = [
        self::OWNER => '*',
        self::ADMIN => '*',
        self::STAFF => ['products.view', 'products.create', 'products.update', 'products.import', 'products.export'],
        self::VIEWER => ['products.view', 'products.export'],
    ];

    /**
     * Short descriptions for the team page.
     */
    public const DESCRIPTIONS = [
        self::OWNER => 'Full access. Created the shop; cannot be removed.',
        self::ADMIN => 'Full access, including inviting and managing members.',
        self::STAFF => 'Adds and edits products and stock. Cannot delete or manage the team.',
        self::VIEWER => 'Read-only access, plus exports.',
    ];

    /**
     * Roles that can be given to someone from the team page. "Owner" is
     * only ever the person who created the shop.
     *
     * @return array<int, string>
     */
    public static function assignable(): array
    {
        return [self::ADMIN, self::STAFF, self::VIEWER];
    }

    /**
     * Create or update all permissions and roles. Safe to run repeatedly.
     */
    public static function sync(): void
    {
        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web', 'team_id' => null]);
            $role->syncPermissions($permissions === '*' ? array_keys(self::PERMISSIONS) : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
