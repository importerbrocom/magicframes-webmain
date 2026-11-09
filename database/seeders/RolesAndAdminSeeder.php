<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndAdminSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage gallery', 'manage videos', 'manage homepage', 'manage header', 'manage pages',
        'manage services', 'manage testimonials', 'manage blog', 'manage enquiries',
        'manage settings', 'manage users',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Super admin: everything (also enforced by Gate::before).
        Role::findOrCreate('super-admin', 'web')->syncPermissions(self::PERMISSIONS);

        // Editor: all content, but not settings or users.
        Role::findOrCreate('editor', 'web')->syncPermissions(array_diff(self::PERMISSIONS, ['manage settings', 'manage users']));

        // Enquiry handler: only the enquiries inbox.
        Role::findOrCreate('enquiry-handler', 'web')->syncPermissions(['manage enquiries']);

        // First super admin comes from the environment — never a hard-coded password.
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if ($email && $password) {
            $user = User::firstOrNew(['email' => $email]);
            $user->name = $user->name ?: env('ADMIN_NAME', 'Administrator');
            $user->password = $password;
            $user->is_active = true;
            $user->save();
            $user->syncRoles(['super-admin']);
        }
    }
}
