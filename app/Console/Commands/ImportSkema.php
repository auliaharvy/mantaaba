<?php

namespace App\Console\Commands;

use App\Services\SkemaImporter;
use Illuminate\Console\Command;

/** Impor template JSON skema (unit kompetensi, elemen, KUK, penilaian). Contoh: php artisan skema:import database/seeders/data/skema/SIM.json */
class ImportSkema extends Command
{
    protected $signature = 'skema:import {file : path file JSON}';

    protected $description = 'Impor/perbarui skema & unit kompetensi dari template JSON';

    public function handle(SkemaImporter $importer): int
    {
        $stats = $importer->importFile($this->argument('file'));
        $this->info("{$stats['skema']->code}: {$stats['created']} unit baru, {$stats['updated']} diperbarui, {$stats['removed']} dihapus.");
        foreach ($stats['kept'] as $kept) {
            $this->warn("Dipertahankan (sudah punya materi): {$kept}");
        }

        return self::SUCCESS;
    }
}
