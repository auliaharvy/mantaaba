<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD 8.10: admin_pc diikat ke satu PC; user nonaktif tidak bisa login.
// gender dibuat opsional karena import CSV anggota (8.2) tidak memuatnya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
            $table->enum('gender', ['L', 'P'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn('is_active');
        });
    }
};
