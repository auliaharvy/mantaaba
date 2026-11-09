<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.12: modul Open Recruitment dihapus (tabel, kolom setting, permission)
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('recruitments');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['spotify', 'recruitment_status', 'recruitment_thumbnail']);
        });

        $ids = DB::table('permissions')->where('name', 'like', 'recruitment-%')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('spotify')->nullable();
            $table->string('recruitment_status')->nullable();
            $table->string('recruitment_thumbnail')->nullable();
        });

        Schema::create('recruitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('file');
            $table->boolean('document_status');
            $table->boolean('interview_status');
            $table->timestamps();
        });
    }
};
