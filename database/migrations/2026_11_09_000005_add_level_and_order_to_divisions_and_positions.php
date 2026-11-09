<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// PRD 8.4: divisi per level, urutan tampil, jabatan inti tanpa divisi
return new class extends Migration
{
    public const CORE_POSITIONS = ['Ketua', 'Wakil Ketua', 'Sekretaris', 'Wakil Sekretaris', 'Bendahara', 'Wakil Bendahara'];

    public function up(): void
    {
        Schema::table('divisions', function (Blueprint $table) {
            $table->enum('level', ['pd', 'pc'])->default('pd')->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('level');
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->foreignId('division_id')->nullable()->change();
            $table->unsignedInteger('sort_order')->default(0)->after('name');
        });

        // Q5 default: jabatan inti; bidang diisi admin
        foreach (self::CORE_POSITIONS as $i => $name) {
            if (! DB::table('positions')->whereNull('division_id')->where('name', $name)->exists()) {
                DB::table('positions')->insert(['name' => $name, 'sort_order' => $i + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('positions')->whereNull('division_id')->delete();
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn('sort_order');
            $table->foreignId('division_id')->nullable(false)->change();
        });
        Schema::table('divisions', fn (Blueprint $table) => $table->dropColumn(['level', 'sort_order']));
    }
};
