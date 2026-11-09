<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;

// M1 menambah permission activity-log-view; matriks disinkronkan ulang (idempoten)
return new class extends Migration
{
    public function up(): void
    {
        (new RolePermissionSeeder)->run();
    }

    public function down(): void
    {
        // tidak perlu: permission tambahan tidak berbahaya bila tertinggal
    }
};
