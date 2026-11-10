<?php

/*
| Konfigurasi aplikasi Rumah Mantaaba Sigaling (PRD 5, 6.2, 6.3).
| Satu sumber untuk role, permission, paket hak akses, dan menu sidebar.
*/

$crud = ['view', 'create', 'update', 'delete'];

// Field blok halaman (PRD 9.1.3). type: text|textarea|rich|url|image|number|select|icon|embed|gallery|units
$title = ['type' => 'text', 'label' => 'Judul section'];
$button = fn ($n = '') => ["button{$n}_text" => ['type' => 'text', 'label' => "Teks tombol {$n}"], "button{$n}_url" => ['type' => 'url', 'label' => "Tautan tombol {$n}", 'help' => 'Alamat di situs ini (mis. /pendaftaran) atau URL lengkap https://']];

return [
    // PRD 5.2
    'roles' => [
        'super_admin' => 'Super Admin',
        'pengurus' => 'Pengurus',
        'anggota' => 'Anggota',
    ],

    'max_super_admins' => 3,

    // PRD 13.1: email Super Admin awal (SuperAdminSeeder)
    'admin_email' => env('MANTAABA_ADMIN_EMAIL'),

    /*
    | PRD 5.3: modul => label, aksi standar ({modul}-{aksi}), aksi khusus (nama permission lengkap => label).
    | Modul yang belum dibangun tetap terdaftar agar layar Hak Akses stabil sejak awal.
    */
    'modules' => [
        'cms' => ['label' => 'Website (halaman, menu, slider, galeri, dokumen, unit bisnis, setting situs)', 'actions' => $crud],
        'news' => ['label' => 'Berita', 'actions' => $crud, 'extra' => ['news-publish' => 'Terbitkan']],
        'contact-message' => ['label' => 'Pesan Kontak', 'actions' => ['view', 'delete']],
        'announcement' => ['label' => 'Pengumuman', 'actions' => $crud],
        'batch' => ['label' => 'Angkatan', 'actions' => $crud],
        'admission' => ['label' => 'Pendaftaran', 'actions' => $crud, 'extra' => ['admission-decide' => 'Terima/tolak', 'admission-export' => 'Export']],
        'member' => ['label' => 'Data Anggota', 'actions' => $crud, 'extra' => ['member-export' => 'Export', 'member-private' => 'Lihat data pribadi']],
        'coaching' => ['label' => 'Catatan Pembinaan', 'actions' => $crud],
        'health' => ['label' => 'Kesehatan', 'actions' => $crud],
        'learning' => ['label' => 'Pembelajaran', 'actions' => $crud, 'extra' => ['learning-review' => 'Nilai tugas']],
        'assessment' => ['label' => 'Penilaian & Sertifikat', 'actions' => $crud, 'extra' => ['certificate-issue' => 'Terbitkan sertifikat', 'certificate-revoke' => 'Cabut sertifikat']],
        'agenda' => ['label' => 'Agenda', 'actions' => $crud, 'extra' => ['agenda-attendance' => 'Kehadiran']],
        'dorm' => ['label' => 'Asrama & Piket', 'actions' => $crud, 'extra' => ['duty-verify' => 'Verifikasi piket']],
        'stock' => ['label' => 'Stok & Gudang', 'actions' => $crud, 'extra' => ['stock-move' => 'Mutasi stok', 'stock-opname' => 'Stock opname']],
        'farm' => ['label' => 'Kebun & Kolam', 'actions' => $crud, 'extra' => ['farm-log' => 'Catat log & panen']],
        'kitchen' => ['label' => 'Dapur', 'actions' => $crud, 'extra' => ['kitchen-log' => 'Catat masak']],
        'asset' => ['label' => 'Aset & Kendaraan', 'actions' => $crud, 'extra' => ['asset-usage' => 'Pemakaian', 'asset-maintenance' => 'Perawatan']],
        'coa' => ['label' => 'Master Akuntansi (COA, pihak, pusat biaya, anggaran)', 'actions' => $crud],
        'journal' => ['label' => 'Voucher & Jurnal', 'actions' => $crud, 'extra' => ['journal-submit' => 'Ajukan', 'journal-approve' => 'Setujui & posting', 'period-close' => 'Tutup periode']],
        'finance-report' => ['label' => 'Laporan Keuangan', 'actions' => ['view']],
        'donation' => ['label' => 'Donasi', 'actions' => $crud, 'extra' => ['donation-verify' => 'Verifikasi']],
        'user' => ['label' => 'Pengguna', 'actions' => $crud],
        'activity-log' => ['label' => 'Log Aktivitas', 'actions' => ['view']],
    ],

    /*
    | PRD 5.4: paket = semua permission modul `modules` + `permissions` − `except`.
    */
    'packages' => [
        'pengurus_inti' => [
            'label' => 'Pengurus Inti',
            'modules' => ['cms', 'news', 'contact-message', 'announcement', 'batch', 'admission', 'member', 'coaching', 'health', 'learning', 'assessment', 'agenda', 'dorm', 'stock', 'farm', 'kitchen', 'asset', 'coa', 'journal', 'finance-report', 'donation', 'activity-log'],
            'except' => ['journal-approve', 'period-close'],
        ],
        'bendahara' => [
            'label' => 'Bendahara',
            'modules' => ['coa', 'journal', 'finance-report', 'donation'],
            'permissions' => ['stock-view', 'asset-view'],
        ],
        'mentor' => [
            'label' => 'Mentor',
            'modules' => ['learning', 'assessment', 'coaching', 'agenda'],
            'permissions' => ['member-view'],
        ],
        'koordinator' => [
            'label' => 'Koordinator Operasional',
            'modules' => ['dorm', 'stock', 'farm', 'kitchen', 'asset'],
            'permissions' => ['journal-view', 'journal-create', 'journal-submit'],
        ],
        'redaksi' => [
            'label' => 'Redaksi Website',
            'modules' => ['cms', 'news', 'contact-message', 'announcement'],
        ],
        'siswa_dapur' => [
            'label' => 'Siswa Petugas Dapur',
            'permissions' => ['kitchen-view', 'kitchen-log', 'stock-view', 'stock-move', 'journal-view', 'journal-create', 'journal-submit'],
        ],
        'siswa_kebun' => [
            'label' => 'Siswa Petugas Kebun/Kolam',
            'permissions' => ['farm-view', 'farm-log', 'stock-view', 'stock-move', 'journal-view', 'journal-create', 'journal-submit'],
        ],
        'siswa_keuangan' => [
            'label' => 'Siswa Petugas Keuangan',
            'permissions' => ['journal-view', 'journal-create', 'journal-submit', 'finance-report-view'],
        ],
    ],

    /*
    | PRD 9.1.3: blok halaman. fields = field data blok; items = daftar berulang (maks `max`).
    | Menambah blok = tambah definisi di sini + partial resources/views/components/site/block/{type}.blade.php.
    */
    'blocks' => [
        'hero' => ['label' => 'Hero (judul besar + gambar)', 'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true],
            'subtitle' => ['type' => 'textarea', 'label' => 'Subjudul'],
            'image' => ['type' => 'image', 'label' => 'Gambar latar (≥ 1600px)'],
            'overlay' => ['type' => 'number', 'label' => 'Gelap overlay (0–80%)', 'min' => 0, 'max' => 80, 'default' => 55],
        ] + $button('1') + $button('2')],
        'slider' => ['label' => 'Slider (dari menu Slider)', 'fields' => []],
        'text' => ['label' => 'Teks', 'fields' => [
            'title' => $title,
            'body' => ['type' => 'rich', 'label' => 'Isi', 'required' => true],
        ]],
        'image_text' => ['label' => 'Gambar + teks', 'fields' => [
            'image' => ['type' => 'image', 'label' => 'Gambar'],
            'position' => ['type' => 'select', 'label' => 'Posisi gambar', 'options' => ['left' => 'Kiri', 'right' => 'Kanan'], 'default' => 'left'],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true],
            'body' => ['type' => 'rich', 'label' => 'Isi'],
        ] + $button()],
        'cards' => ['label' => 'Kartu (fitur, pilar, program)', 'fields' => [
            'title' => $title,
            'intro' => ['type' => 'textarea', 'label' => 'Pengantar'],
            'columns' => ['type' => 'select', 'label' => 'Kolom', 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
        ], 'items' => ['label' => 'Kartu', 'max' => 12, 'fields' => [
            'icon' => ['type' => 'icon', 'label' => 'Ikon'],
            'image' => ['type' => 'image', 'label' => 'atau gambar'],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true],
            'text' => ['type' => 'textarea', 'label' => 'Teks'],
            'url' => ['type' => 'url', 'label' => 'Tautan'],
        ]]],
        'stats' => ['label' => 'Angka statistik', 'fields' => ['title' => $title], 'items' => ['label' => 'Angka', 'max' => 6, 'fields' => [
            'auto' => ['type' => 'select', 'label' => 'Ambil otomatis', 'options' => 'stats'],
            'value' => ['type' => 'text', 'label' => 'Angka (bila tidak otomatis)'],
            'label' => ['type' => 'text', 'label' => 'Keterangan', 'required' => true],
        ]]],
        'timeline' => ['label' => 'Linimasa (roadmap)', 'fields' => [
            'title' => $title,
            'intro' => ['type' => 'textarea', 'label' => 'Pengantar'],
        ], 'items' => ['label' => 'Tahap', 'max' => 12, 'fields' => [
            'time' => ['type' => 'text', 'label' => 'Waktu (mis. Bulan 1)'],
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true],
            'text' => ['type' => 'textarea', 'label' => 'Teks'],
        ]]],
        'team' => ['label' => 'Tim / pengurus', 'fields' => ['title' => $title], 'items' => ['label' => 'Orang', 'max' => 16, 'fields' => [
            'photo' => ['type' => 'image', 'label' => 'Foto'],
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true],
            'role' => ['type' => 'text', 'label' => 'Jabatan'],
            'quote' => ['type' => 'textarea', 'label' => 'Kutipan singkat'],
        ]]],
        'gallery' => ['label' => 'Galeri foto', 'fields' => [
            'title' => $title,
            'gallery_id' => ['type' => 'gallery', 'label' => 'Album', 'help' => 'Kosong = foto terbaru dari semua album'],
            'limit' => ['type' => 'number', 'label' => 'Jumlah foto', 'min' => 3, 'max' => 12, 'default' => 6],
        ]],
        'latest' => ['label' => 'Berita / agenda terbaru', 'fields' => [
            'title' => $title,
            'source' => ['type' => 'select', 'label' => 'Sumber', 'options' => ['berita' => 'Berita', 'agenda' => 'Agenda'], 'default' => 'berita', 'required' => true],
            'count' => ['type' => 'number', 'label' => 'Jumlah', 'min' => 3, 'max' => 9, 'default' => 3],
        ]],
        'units' => ['label' => 'Unit bisnis', 'fields' => [
            'title' => $title,
            'intro' => ['type' => 'textarea', 'label' => 'Pengantar'],
            'unit_ids' => ['type' => 'units', 'label' => 'Unit yang ditampilkan', 'help' => 'Kosong = semua unit publik'],
        ]],
        'members' => ['label' => 'Siswa & alumni (yang mengizinkan tampil)', 'fields' => [
            'title' => $title,
            'category' => ['type' => 'select', 'label' => 'Kategori', 'options' => ['semua' => 'Siswa & alumni', 'siswa' => 'Siswa', 'alumni' => 'Alumni'], 'default' => 'semua'],
            'limit' => ['type' => 'number', 'label' => 'Jumlah', 'min' => 3, 'max' => 24, 'default' => 8],
        ]],
        'skemas' => ['label' => '5 skema pembelajaran', 'fields' => [
            'title' => $title,
            'intro' => ['type' => 'textarea', 'label' => 'Pengantar'],
        ]],
        'testimonial' => ['label' => 'Testimoni', 'fields' => ['title' => $title], 'items' => ['label' => 'Testimoni', 'max' => 9, 'fields' => [
            'photo' => ['type' => 'image', 'label' => 'Foto'],
            'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true],
            'role' => ['type' => 'text', 'label' => 'Peran (mis. Alumni Angkatan 1)'],
            'quote' => ['type' => 'textarea', 'label' => 'Kutipan', 'required' => true],
        ]]],
        'faq' => ['label' => 'Tanya jawab (FAQ)', 'fields' => ['title' => $title], 'items' => ['label' => 'Pertanyaan', 'max' => 20, 'fields' => [
            'question' => ['type' => 'text', 'label' => 'Pertanyaan', 'required' => true],
            'answer' => ['type' => 'textarea', 'label' => 'Jawaban', 'required' => true],
        ]]],
        'cta' => ['label' => 'Ajakan (CTA)', 'fields' => [
            'title' => ['type' => 'text', 'label' => 'Judul', 'required' => true],
            'text' => ['type' => 'textarea', 'label' => 'Teks'],
            'style' => ['type' => 'select', 'label' => 'Gaya', 'options' => ['primer' => 'Cokelat (primer)', 'aksen' => 'Marun (aksen)', 'krem' => 'Krem'], 'default' => 'primer'],
        ] + $button('1') + $button('2')],
        'quote' => ['label' => 'Kutipan / ayat', 'fields' => [
            'arabic' => ['type' => 'textarea', 'label' => 'Teks Arab (opsional)'],
            'translation' => ['type' => 'textarea', 'label' => 'Terjemahan / kutipan', 'required' => true],
            'source' => ['type' => 'text', 'label' => 'Sumber (mis. QS. Al-Baqarah: 148)'],
        ]],
        'embed' => ['label' => 'Video YouTube / peta Google Maps', 'fields' => [
            'title' => $title,
            'url' => ['type' => 'embed', 'label' => 'URL YouTube atau Google Maps embed', 'required' => true],
            'caption' => ['type' => 'text', 'label' => 'Keterangan'],
        ]],
    ],

    // Statistik otomatis blok `stats` (key => label); nilai dihitung App\Support\SiteStats. Anggota/alumni/angkatan aktif setelah M2.
    'stats' => [
        'anggota_aktif' => 'Anggota aktif',
        'alumni' => 'Alumni',
        'angkatan' => 'Angkatan',
        'sertifikat_terbit' => 'Sertifikat terbit',
        'agenda_selesai' => 'Agenda terselenggara',
        'berita' => 'Berita terbit',
    ],

    // Ikon situs untuk blok kartu (nama komponen x-site.icon)
    'icons' => ['shield', 'academic-cap', 'book-open', 'users', 'heart', 'home', 'building', 'chart-bar', 'computer', 'sun', 'fire', 'sparkles', 'check', 'document', 'calendar', 'chat', 'photo', 'map-pin'],

    // PRD 9.1.2: route publik yang boleh dipilih di Menu Manager (route => label)
    'menu_routes' => [
        'site.home' => 'Beranda',
        'site.curriculum' => 'Kurikulum',
        'site.units' => 'Ekosistem (unit bisnis)',
        'site.news' => 'Berita',
        'site.training' => 'Agenda',
        'site.gallery' => 'Galeri',
        'site.documents' => 'Dokumen',
        'site.admission' => 'Pendaftaran',
        'site.donation' => 'Donasi',
        'site.contact' => 'Kontak',
    ],

    // Paket yang otomatis diberikan saat akun pengurus dibuat (bisa diubah di layar Hak Akses)
    'default_package' => ['pengurus' => 'pengurus_inti'],

    /*
    | PRD 6.3: sidebar. can = permission (null = semua pengguna login), role = batasi ke role,
    | active = pola routeIs (default "{route prefix}.*"), params = parameter route.
    */
    'menu' => [
        ['items' => [
            ['title' => 'Beranda', 'route' => 'home', 'active' => 'home', 'icon' => 'fa-home'],
            ['title' => 'Lihat Situs', 'route' => 'site.home', 'active' => 'site.*', 'icon' => 'fa-globe'],
            ['title' => 'Agenda Saya', 'route' => 'my-training.index', 'icon' => 'fa-ticket-alt'],
        ]],
        ['title' => 'Website', 'items' => [
            ['title' => 'Halaman', 'route' => 'page.index', 'can' => 'cms-view', 'icon' => 'fa-file-alt'],
            ['title' => 'Menu', 'route' => 'menu.index', 'can' => 'cms-view', 'icon' => 'fa-bars'],
            ['title' => 'Slider', 'route' => 'slider.index', 'can' => 'cms-view', 'icon' => 'fa-images'],
            ['title' => 'Berita', 'route' => 'blog.index', 'can' => 'news-view', 'icon' => 'fa-newspaper'],
            ['title' => 'Kategori Berita', 'route' => 'taxonomy.index', 'params' => ['kategori'], 'active' => 'taxonomy.*', 'can' => 'news-publish', 'icon' => 'fa-tags'],
            ['title' => 'Galeri', 'route' => 'gallery.index', 'can' => 'cms-view', 'icon' => 'fa-camera'],
            ['title' => 'Dokumen', 'route' => 'document.index', 'can' => 'cms-view', 'icon' => 'fa-file-pdf'],
            ['title' => 'Unit Bisnis', 'route' => 'business-unit.index', 'can' => 'cms-view', 'icon' => 'fa-store'],
            ['title' => 'Pesan Masuk', 'route' => 'contact-message.index', 'can' => 'contact-message-view', 'icon' => 'fa-envelope'],
            ['title' => 'Setting Situs', 'route' => 'setting.edit', 'can' => 'cms-update', 'icon' => 'fa-sliders-h'],
        ]],
        ['title' => 'Penerimaan', 'items' => [
            ['title' => 'Angkatan', 'route' => 'batch.index', 'can' => 'batch-view', 'icon' => 'fa-layer-group'],
            ['title' => 'Pendaftar', 'route' => 'applicant.index', 'can' => 'admission-view', 'icon' => 'fa-user-plus'],
        ]],
        ['title' => 'Anggota', 'items' => [
            ['title' => 'Data Anggota', 'route' => 'member.index', 'can' => 'member-view', 'icon' => 'fa-users'],
            ['title' => 'Catatan Pembinaan', 'route' => 'coaching.index', 'can' => 'coaching-view', 'icon' => 'fa-clipboard-list'],
            ['title' => 'Kesehatan', 'route' => 'health.index', 'can' => 'health-view', 'icon' => 'fa-notes-medical'],
            ['title' => 'Pengumuman', 'route' => 'announcement.index', 'can' => 'announcement-view', 'icon' => 'fa-bullhorn'],
        ]],
        ['title' => 'Kegiatan', 'items' => [
            ['title' => 'Agenda', 'route' => 'event.index', 'can' => 'agenda-view', 'icon' => 'fa-calendar-alt'],
        ]],
        ['title' => 'Sistem', 'items' => [
            ['title' => 'Pengguna & Hak Akses', 'route' => 'user.index', 'can' => 'user-view', 'icon' => 'fa-user-shield'],
            ['title' => 'Log Aktivitas', 'route' => 'activity-log.index', 'can' => 'activity-log-view', 'icon' => 'fa-history'],
        ]],
    ],
];
