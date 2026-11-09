<?php

/*
| Konfigurasi aplikasi Rumah Mantaaba Sigaling (PRD 5, 6.2, 6.3).
| Satu sumber untuk role, permission, paket hak akses, dan menu sidebar.
*/

$crud = ['view', 'create', 'update', 'delete'];

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
            ['title' => 'Slider', 'route' => 'slider.index', 'can' => 'cms-view', 'icon' => 'fa-images'],
            ['title' => 'Berita', 'route' => 'blog.index', 'can' => 'news-view', 'icon' => 'fa-newspaper'],
            ['title' => 'Kategori Berita', 'route' => 'taxonomy.index', 'params' => ['kategori'], 'active' => 'taxonomy.*', 'can' => 'news-publish', 'icon' => 'fa-tags'],
            ['title' => 'Galeri', 'route' => 'gallery.index', 'can' => 'cms-view', 'icon' => 'fa-camera'],
            ['title' => 'Dokumen', 'route' => 'document.index', 'can' => 'cms-view', 'icon' => 'fa-file-pdf'],
            ['title' => 'Pesan Masuk', 'route' => 'contact-message.index', 'can' => 'contact-message-view', 'icon' => 'fa-envelope'],
            ['title' => 'Setting Situs', 'route' => 'setting.edit', 'can' => 'cms-update', 'icon' => 'fa-sliders-h'],
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
