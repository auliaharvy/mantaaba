<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // B10: nama jabatan unik per divisi
        Schema::table('positions', function (Blueprint $table) {
            $table->unique(['division_id', 'name']);
        });

        // B11: buang pendaftaran dobel lama; simpan yang punya sertifikat, lalu yang paling awal
        $duplicates = DB::table('participants')
            ->select('event_id', 'user_id')
            ->groupBy('event_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $keep = DB::table('participants')
                ->where('event_id', $dup->event_id)
                ->where('user_id', $dup->user_id)
                ->orderByRaw('certificate IS NULL')
                ->orderBy('id')
                ->value('id');

            DB::table('participants')
                ->where('event_id', $dup->event_id)
                ->where('user_id', $dup->user_id)
                ->where('id', '!=', $keep)
                ->delete();
        }

        Schema::table('participants', function (Blueprint $table) {
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        // index unik dipakai FK, jadi buat index biasa dulu sebelum unik dihapus
        Schema::table('participants', function (Blueprint $table) {
            $table->index('event_id');
            $table->dropUnique(['event_id', 'user_id']);
        });
        Schema::table('positions', function (Blueprint $table) {
            $table->index('division_id');
            $table->dropUnique(['division_id', 'name']);
        });
    }
};
