<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

// PRD 4.2: rename role lama (user tetap memegang role yang sama karena id tidak berubah)
return new class extends Migration
{
    private const RENAMES = [
        'administrator' => 'admin_pd',
        'member' => 'anggota',
        'general' => 'umum',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('roles')->where('name', $old)->update(['name' => $new]);
        }

        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            DB::table('roles')->where('name', $new)->update(['name' => $old]);
        }
        DB::table('roles')->whereIn('name', ['editor', 'admin_pc'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
