<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriks permission PRD 4.3. Idempoten: aman dijalankan ulang (juga dari migrasi).
 * super_administrator tidak diberi permission karena lolos semua lewat Gate::before.
 */
class RolePermissionSeeder extends Seeder
{
    private const CRUD = ['view', 'create', 'update', 'delete'];

    private const MODULES = [
        'organization', 'member', 'due', 'board', 'division', 'position',
        'page', 'slider', 'gallery', 'document', 'blog', 'thread', 'comment',
        'research', 'event', 'participant', 'certificate', 'user',
        'contact-message', 'setting', 'profile',
    ];

    private const EXTRA = ['dashboard-view', 'blog-publish', 'thread-moderate', 'activity-log-view'];

    public static function permissions(): array
    {
        $all = self::EXTRA;
        foreach (self::MODULES as $module) {
            foreach (self::CRUD as $action) {
                $all[] = "{$module}-{$action}";
            }
        }

        return $all;
    }

    /** @return array<string, string[]> role => permissions */
    public static function matrix(): array
    {
        $crud = fn (string ...$modules) => collect($modules)
            ->flatMap(fn ($m) => array_map(fn ($a) => "{$m}-{$a}", self::CRUD))
            ->all();
        $base = ['dashboard-view', ...$crud('profile')];

        return [
            'super_administrator' => [],
            'admin_pd' => self::permissions(),
            'editor' => [
                ...$base,
                ...$crud('page', 'slider', 'gallery', 'document', 'blog', 'thread', 'comment', 'research', 'contact-message'),
                'blog-publish', 'thread-moderate',
            ],
            // blog/thread/comment untuk admin_pc & anggota = "create + sendiri": kepemilikan dibatasi policy
            'admin_pc' => [
                ...$base,
                'organization-view', 'member-view', 'due-view', 'board-view', 'research-view',
                ...$crud('blog', 'thread', 'comment'),
                ...$crud('event', 'participant', 'certificate'),
            ],
            'anggota' => [
                ...$base,
                'research-view',
                ...$crud('blog', 'thread', 'comment'),
            ],
            // Q6 default: forum dibaca publik, menulis hanya anggota
            'umum' => [...$base, 'research-view', 'thread-view', 'comment-view'],
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissions() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        // buang permission lama yang tidak ada di matriks (general-*, admin-*, recruitment-*)
        Permission::whereNotIn('name', self::permissions())->delete();

        foreach (self::matrix() as $role => $permissions) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'])->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
