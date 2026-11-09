<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// PRD 8.8 AC: PDF jurnal draf tidak boleh bisa diunduh publik -> pindah dari disk public ke local (privat)
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('research')->whereNotNull('file')->pluck('file') as $path) {
            if (Storage::disk('public')->exists($path) && ! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, Storage::disk('public')->get($path));
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('research')->whereNotNull('file')->pluck('file') as $path) {
            if (Storage::disk('local')->exists($path) && ! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, Storage::disk('local')->get($path));
                Storage::disk('local')->delete($path);
            }
        }
    }
};
