<?php

namespace Database\Seeders;

use App\Models\NavMenu;
use App\Models\Page;
use Illuminate\Database\Seeder;

/** Menu default (PRD 13.2); hanya dibuat bila belum ada menu sama sekali. */
class NavMenuSeeder extends Seeder
{
    public function run(): void
    {
        if (NavMenu::exists()) {
            return;
        }
        $pages = Page::pluck('id', 'slug');
        $menus = [
            'header' => [['Beranda', 'route', 'site.home'], ['Tentang', 'page', 'tentang'], ['Program', 'page', 'program'], ['Kurikulum', 'route', 'site.curriculum'],
                ['Ekosistem', 'route', 'site.units'], ['Berita', 'route', 'site.news'], ['Agenda', 'route', 'site.training'], ['Galeri', 'route', 'site.gallery'],
                ['Donasi', 'route', 'site.donation'], ['Kontak', 'route', 'site.contact']],
            'footer' => [['Tentang Kami', 'page', 'tentang'], ['Program', 'page', 'program'], ['Ekosistem', 'route', 'site.units'], ['Dokumen & Laporan', 'route', 'site.documents'],
                ['Pendaftaran', 'route', 'site.admission'], ['Kontak', 'route', 'site.contact']],
        ];
        foreach ($menus as $location => $items) {
            foreach ($items as $i => [$label, $type, $target]) {
                NavMenu::create(['location' => $location, 'label' => $label, 'type' => $type, 'sort_order' => $i,
                    'page_id' => $type === 'page' ? $pages[$target] : null, 'route_name' => $type === 'route' ? $target : null]);
            }
        }
    }
}
