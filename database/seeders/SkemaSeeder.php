<?php

namespace Database\Seeders;

use App\Models\Skema;
use App\Services\SkemaImporter;
use Illuminate\Database\Seeder;

/** 5 skema + matriks karakter dari Grand Design 3 & 5 (PRD 13.2); unit kompetensi FND & SIM dari template JSON. Hanya dibuat bila kode belum ada. */
class SkemaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->skemas() as $i => [$code, $name, $month, $description, $modules, $matrix]) {
            $skema = Skema::firstOrCreate(['code' => $code], ['name' => $name, 'month_label' => $month, 'description' => $description, 'character_matrix' => $matrix, 'sort_order' => $i]);
            if (! $skema->wasRecentlyCreated) {
                continue;
            }
            // skema yang sudah punya standar kompetensi resmi: unit, elemen & KUK dari template JSON
            $template = database_path("seeders/data/skema/{$code}.json");
            if (is_file($template)) {
                app(SkemaImporter::class)->importFile($template, $skema);
                continue;
            }
            foreach ($modules as $j => $title) {
                $skema->modules()->create(['code' => "{$code}-" . ($j + 1), 'title' => $title, 'sort_order' => $j]);
            }
        }
    }

    private function skemas(): array
    {
        $row = fn ($competency, $technical, $asabiyah, $fastabiqul) => compact('competency', 'technical', 'asabiyah', 'fastabiqul');

        return [
            ['FND', 'Fundamental', 'Bulan 1', 'Komunikasi bisnis yang aktif, efektif, efisien dan taktis, dipadukan dengan logika matematika bisnis dasar sebagai basis pengambilan keputusan yang presisi.',
                ['Komunikasi Bisnis: mendengar empati & membaca', 'Komunikasi Bisnis: memahami & menganalisis', 'Komunikasi Bisnis: menyampaikan lisan & tulisan', 'Matematika Bisnis: operasi dasar, persentase & persamaan', 'Matematika Bisnis: HPP & margin'],
                [$row('Komunikasi Bisnis', 'Mendengar aktif, efektif dan efisien menyampaikan lisan dan tulisan', 'Mendengar penuh empati atas keluh kesah tim; aktif melatih kawan yang masih tertutup/pemalu.', 'Berinisiatif menganalisis kebuntuan komunikasi dan merumuskan alur koordinasi (SOP) paling solutif.'),
                    $row('Matematika Bisnis', 'Perhitungan HPP, persentase, persamaan & margin', 'Budaya peer-tutoring: yang mahir secara sukarela mengajari yang lambat agar seluruh kelas lulus evaluasi.', 'Berlomba menciptakan efisiensi beban operasional (mis. biaya dapur) secara matematis tanpa mengurangi standar kualitas.')]],
            ['SIM', 'Sistem Informasi Manajemen', 'Bulan 2–3', 'Merumuskan visi-misi organisasi yang berdampak sosial, menyusun struktur organisasi, tupoksi, alur bisnis, SOP, workflow, tertib administrasi, dan kepatuhan legalitas.',
                ['Visi-Misi Organisasi', 'Struktur Organisasi & Tupoksi', 'Alur Bisnis', 'SOP & Workflow', 'Tertib Administrasi & Klasifikasi Dokumen', 'Legalitas'],
                [$row('SIM', 'Visi organisasi, struktur, tupoksi, SOP, workflow', 'Merumuskan visi bisnis yang memajukan masyarakat sekitar; menyusun dokumen dengan rapi demi kemudahan tim pelanjut.', 'Berlomba mendesain dan mengimplementasikan model workflow yang paling minim kesalahan (zero-mistake).')]],
            ['SIA', 'Sistem Informasi Akuntansi', 'Bulan 4', 'Merancang Chart of Accounts, memetakan dokumen transaksi, hingga siklus akuntansi paripurna yang berstandar dan siap diaudit (audit-ready).',
                ['Chart of Accounts', 'Dokumen Transaksi & 8 Voucher', 'Jurnal & Buku Besar', 'Neraca Saldo & Penyesuaian', 'Laporan Keuangan & CALK'],
                [$row('SIA', 'COA, vouching, jurnal, buku besar, laporan keuangan', 'Peer-auditing secara kekeluargaan untuk menjaga saudara dari kekeliruan pencatatan/dosa ghulul.', 'Berlomba mencetak surplus dan memetakan persentase laba untuk direinvestasikan pada program kebaikan/sedekah.')]],
            ['SIP', 'Sistem Informasi Perpajakan', 'Bulan 5', 'Memetakan akun laporan keuangan ke pos SPT, rekonsiliasi, ekualisasi pajak (PPh Badan & PPN), serta uji silang arus uang, piutang/utang, dan barang.',
                ['Mapping Akun ke SPT', 'Rekonsiliasi Fiskal', 'Ekualisasi PPh Badan & PPN', 'Uji Arus Uang, Piutang & Barang'],
                [$row('SIP', 'Rekonsiliasi, ekualisasi pajak, PPh, PPN', "Kesadaran bahwa ketaatan pajak adalah bentuk 'Asabiyah makro untuk mensubsidi pembangunan umat/negara.", 'Proaktif menghitung dan melaporkan pajak tepat waktu, akurat, tanpa menunggu jatuh tempo atau teguran.')]],
            ['IT', 'Teknologi Bisnis', 'Bulan 6', 'Menerapkan Enterprise Resource Planning (ERP) untuk sentralisasi data, serta optimalisasi Artificial Intelligence (prompt engineering) untuk riset dan solusi bisnis.',
                ['ERP & Sentralisasi Data', 'AI & Prompt Engineering'],
                [$row('Teknologi (IT)', 'Integrasi sistem ERP & pemanfaatan AI (prompting)', 'Kesadaran sistemik bahwa keterlambatan input data di satu divisi melumpuhkan divisi saudara yang lain.', 'Memanfaatkan AI secara cerdas untuk riset operasional demi solusi teknis tercepat.')]],
        ];
    }
}
