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
            // header ringkas: 5 menu utama, sisanya dikelompokkan sebagai sub-menu
            'header' => [['Beranda', 'route', 'site.home'],
                ['Tentang Kami', 'page', 'tentang', [['Profil', 'page', 'tentang'], ['Program', 'page', 'program'], ['Kurikulum', 'route', 'site.curriculum'], ['Ekosistem', 'route', 'site.units']]],
                ['Informasi', 'route', 'site.news', [['Berita', 'route', 'site.news'], ['Agenda', 'route', 'site.training'], ['Galeri', 'route', 'site.gallery'], ['Dokumen & Laporan', 'route', 'site.documents']]],
                ['Donasi', 'route', 'site.donation'], ['Kontak', 'route', 'site.contact']],
            'footer' => [['Tentang Kami', 'page', 'tentang'], ['Program', 'page', 'program'], ['Ekosistem', 'route', 'site.units'], ['Dokumen & Laporan', 'route', 'site.documents'],
                ['Pendaftaran', 'route', 'site.admission'], ['Kontak', 'route', 'site.contact']],
        ];
        $make = function (string $location, array $items, ?int $parent = null) use (&$make, $pages) {
            foreach ($items as $i => [$label, $type, $target]) {
                $menu = NavMenu::create(['location' => $location, 'parent_id' => $parent, 'label' => $label, 'type' => $type, 'sort_order' => $i,
                    'page_id' => $type === 'page' ? $pages[$target] : null, 'route_name' => $type === 'route' ? $target : null]);
                $make($location, $items[$i][3] ?? [], $menu->id);
            }
        };
        foreach ($menus as $location => $items) {
            $make($location, $items);
        }
    }
}
