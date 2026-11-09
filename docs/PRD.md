# PRD — Sistem Informasi & Website Rumah Mantaaba Sigaling

| | |
|---|---|
| Versi | 2.0 (dasar pembangunan) |
| Tanggal | 9 November 2026 |
| Pemilik produk | Yayasan Mantaaba / Rumah Mantaaba Sigaling |
| Repo | [github.com/auliaharvy/mantaaba](https://github.com/auliaharvy/mantaaba) (riwayat bersih) |
| Database | `mantaaba` (dev: MAMP MySQL 127.0.0.1:8889), test: `mantaaba_test` |
| Basis kode | `mantaabaproject/` — fork sistem PD AKP2I Jawa Barat (Laravel 12) |
| Dokumen acuan | [Grand Design & Konsep Pendidikan](Grand%20Design%20%26%20Konsep%20Pendidikan%20Rumah%20Mantaaba%20Sigaling%20-%20Master%20Document.md) · [Whitepaper Framework 4 Pilar](WHITEPAPER%20—%20FRAMEWORK%20TATA%20KELOLA%20MANAJEMEN%20PERUSAHAAN%20MAZHAB%20MANTAABA.md) |

Cara membaca: **[TBD Qn]** = keputusan belum final. Default di Bagian 15 berlaku sampai ada jawaban, sehingga pembangunan tidak tertahan. **Hard-stop** = aturan yang ditegakkan di server (bukan hanya di tampilan), sesuai Whitepaper 2.4.2.

---

## 1. Ringkasan & Tujuan

### 1.1 Latar belakang
Rumah Mantaaba Sigaling adalah lembaga nirlaba di bawah Yayasan Mantaaba dengan misi **menciptakan masyarakat Indonesia yang transparan dalam perpajakan**. Lembaga ini memberi pendidikan gratis bagi pemuda lulusan SMA/sederajat (atau seusianya):

- **Fase I — Inkubasi 6 bulan**: Tashawwur Islami, 5 skema kompetensi berbasis SKKNI (Fundamental, SIM, SIA, SIP, IT), dan laboratorium kehidupan (asrama, kebun, kolam ikan, dapur).
- **Fase II — Pembinaan berkelanjutan**: magang (bulan 7–12), bekerja di unit bisnis dengan rotasi peran (staf → supervisor → manajer → direktur), kuliah S1 di Universitas Terbuka, ownership di tahun ke-9, dan ikatan alumni *Sedulur Selawase*.

Seluruh fasilitas (asrama, makan, kesehatan, pembelajaran) ditanggung lembaga, sehingga pencatatan operasional dan keuangan yang rapi dan transparan menjadi kebutuhan inti, bukan pelengkap. Saat ini website [yayasan-mantaaba.com](https://www.yayasan-mantaaba.com/) belum bisa dikelola mandiri dan belum ada sistem untuk data siswa, pembelajaran, operasional, maupun keuangan.

### 1.2 Tujuan
1. **Website publik yang sepenuhnya dikelola dari dashboard (CMS)** — menu, halaman, beranda, berita, galeri, program, unit bisnis, kontak, identitas, dan warna dapat diubah tanpa coding.
2. **Satu sumber data anggota** — penerimaan per angkatan, data anggota (siswa, alumni, relawan, mitra, donatur), jejak perjalanan, catatan pembinaan, kesehatan.
3. **Pembelajaran & sertifikat per skema** — materi, progres, tugas, penilaian 4 dimensi, sertifikat ber-QR per skema yang lulus, kelulusan inkubasi.
4. **Operasional laboratorium kehidupan** — asrama & piket, kebun & kolam, dapur, stok, aset & kendaraan.
5. **Akuntansi double-entry penuh untuk nirlaba (ISAK 35)** — 8 jenis voucher Whitepaper, buku besar, neraca saldo, laporan keuangan lengkap, donasi, dan jurnal otomatis dari operasional.
6. **Sistem sebagai alat praktik** — siswa menjalankan operasional nyata di sistem dengan prinsip Framework 4 Pilar: dokumen standar (SIM), voucher & jurnal (SIA), persetujuan berjenjang, hard-stop, dan jejak audit (IT).

### 1.3 Metrik sukses (6 bulan setelah go-live)
| Metrik | Target |
|---|---|
| Perubahan konten website tanpa developer | 100% |
| Pendaftar baru tercatat di sistem | 100% |
| Siswa aktif dengan progres & nilai skema tercatat | 100% |
| Sertifikat skema terbit dari sistem & terverifikasi lewat QR | 100% |
| Transaksi keuangan berbentuk voucher berlampiran | 100% |
| Tutup buku bulanan | ≤ 5 hari kerja setelah akhir bulan (Whitepaper KPI SIA) |
| Neraca saldo seimbang | selalu (dijamin sistem) |
| Biaya makan per orang per hari tercatat | setiap hari |

---

## 2. Ruang Lingkup

### 2.1 Dalam lingkup v1
- Website publik + CMS (menu, halaman berbasis blok, berita, galeri, slider, dokumen, unit bisnis, setting identitas & warna).
- Penerimaan anggota baru per angkatan (formulir publik, seleksi, cek status, terima → akun).
- Data anggota, kategori, status, fase, jejak perjalanan, pakta integritas, catatan pembinaan, catatan kesehatan.
- Pembelajaran (skema → modul → materi, tugas), penilaian 4 dimensi, sertifikat per skema + verifikasi publik.
- Agenda & kehadiran (kajian Tajug, Sedulur Selawase, kelas, kegiatan umum).
- Asrama (kamar, penghuni) & jadwal piket.
- Stok multi-gudang (nilai rata-rata tertimbang), kebun & kolam, dapur.
- Aset & kendaraan (register, QR label, pemakaian, perawatan, pengingat, penyusutan).
- Akuntansi double-entry: COA, 8 voucher, buku besar, neraca saldo, tutup periode & tutup buku, anggaran, laporan ISAK 35, donasi, jurnal otomatis.
- Pengumuman internal, dashboard per peran, notifikasi.
- 3 role + hak akses per pengguna yang diatur Super Admin.

### 2.2 Backlog v2 (dirancang agar mudah ditambahkan, tidak dibangun di v1)
| Fitur | Catatan desain v1 yang menyiapkannya |
|---|---|
| Kelas online gratis untuk masyarakat umum + kuis otomatis + sertifikat penyelesaian | Kolom `skemas.is_public` sudah ada; sertifikat sudah generik |
| Modul SIP (mapping akun → pos SPT, ekualisasi PPh/PPN, uji arus) | Kolom `coa_accounts.tax_mapping` (json, nullable) disiapkan |
| Buku latihan akuntansi per angkatan (ledger terpisah untuk praktik) | – |
| Pencatatan penuh keuangan/operasional per unit bisnis (multi-entitas) | Unit bisnis sudah menjadi pusat biaya |
| Integrasi WhatsApp API, payment gateway donasi, mutasi bank otomatis | v1 memakai tautan `wa.me` & konfirmasi manual |
| Aplikasi mobile / API publik | – |

### 2.3 Di luar lingkup
Payroll, perpajakan karyawan, e-Faktur, multi-bahasa, form builder bebas.

---

## 3. Kondisi Awal

### 3.1 Stack & struktur yang ada
- **Laravel 12** (`laravel/framework ^12`, PHP ^8.2) dengan **struktur folder Laravel 8** yang dipertahankan: `app/Http/Kernel.php`, `app/Console/Kernel.php`, `RouteServiceProvider`, provider di `config/app.php`. Tidak perlu dimigrasikan ke struktur `bootstrap/app.php`.
- Package: spatie/laravel-permission 6, yajra/laravel-datatables 12, barryvdh/laravel-dompdf 3, simplesoftwareio/simple-qrcode 4, laravel/sanctum 4, guzzle.
- Frontend publik: Vite 6 + Tailwind 3 (+forms) + Alpine (`resources/css/app.css`, `resources/js/app.js`).
- Dashboard: **Metronic 8** statis di `public/` (`css/style.bundle.css`, `plugins/*`, `js/scripts.bundle.js`), jQuery, DataTables, select2, SweetAlert, TinyMCE.
- Route (±238): `routes/web.php` memuat `auth.php`, `permission/public.php` (33 route `site.*`) dan `permission/administrator.php` (±190 route, prefix `dashboard`, middleware `auth:sanctum`, nama route tanpa prefix, mis. `slider.index`).
- PHP tidak ada di PATH shell dev → gunakan PHP bawaan MAMP untuk `artisan`/`composer`.

### 3.2 Pola kode yang menjadi acuan semua modul baru
| Hal | Acuan di kode |
|---|---|
| Controller CRUD + DataTables | `app/Http/Controllers/SliderController.php` (pola terbaru) |
| View modul | `resources/views/dashboard/slider/{index,form}.blade.php` — `index` memakai `<x-dashboard.page>` + `<x-dashboard.datatable>`, satu `form` untuk create & edit (`$model->exists`) |
| Komponen dashboard | `x-dashboard.page` (title, crumbs, alert, handler `[data-delete-url]` + SweetAlert), `x-dashboard.datatable` (server-side, filter `data-dt-filter`), `x-dashboard.field`, `x-dashboard.row-actions`, `x-dashboard.role-badge` |
| DataTables server-side | route terpisah `{modul}-datatable`, `DataTables::eloquent(...)->addColumn('action', view('components.dashboard.row-actions'))->addIndexColumn()` |
| Otorisasi | `$this->middleware('permission:x-view')->only(...)` + `authorizeResource`; policy turunan `app/Policies/ModulePolicy.php` (permission `{modul}-{aksi}`) atau `OwnedPolicy` (pemilik) |
| Upload | `App\Support\Upload::store()` (disk public, nama acak, resize GD, `IMAGE_RULE` 3 MB, `PDF_RULE` 10 MB). File privat: disk `local` + action controller ber-policy yang men-stream file (contoh `CertificateController@download`) |
| Rich text | partial `dashboard.partials.tinymce` + sanitasi `App\Support\Html::clean()` |
| Select2 AJAX | `BoardPeriodController@candidates` → `[{id, text}]` |
| Audit | trait `App\Models\Concerns\LogsActivity` → `ActivityLog::record()` (before/after, `status_changed`) |
| Cache situs | `Setting::current()` (rememberForever) + trait `FlushesSiteCache` |
| PDF + QR | job `GenerateCertificatePdf` + view `certificates/pdf.blade.php` + QR SVG data-URI |
| Transaksi berkuota | `app/Services/EventRegistrar.php` (DB transaction + `lockForUpdate`) |
| Notifikasi | `app/Notifications/*` (queued, mail) |
| Komponen situs | `resources/views/components/site/*` (layout, header, footer, hero-slider, card-*, empty-state, button, input, dll.) |

Pola lama (view `create/edit/show` terpisah + DataTable inline) di modul division/position/general/member/user/home/profile **tidak** ditiru; modul yang dipertahankan dirapikan ke pola baru saat disentuh.

### 3.3 Keputusan per bagian kode
| Bagian | Keputusan | Catatan |
|---|---|---|
| Auth (Breeze) — `routes/auth.php`, `Auth/*Controller`, `LoginRequest` | **Pakai, ubah** | Route register dimatikan; pesan login "PD AKP2I" diganti; login/forgot/reset (Metronic polos) diganti tampilan `x-site.auth-card` |
| User, ProfileController, AdministratorController, GeneralController, MemberController (akun) | **Gabung** | Menjadi satu `UserController` + layar Hak Akses (9.18) |
| Setting + `SettingController` | **Pakai, perluas** | Field AKP2I dihapus (`url_registration`, `url_member_service`, `url_ejournal`, `dues_amounts`), field baru (9.1.1) |
| Page + `PageController` | **Pakai, perluas** | Tambah create/delete, kolom `blocks`, slug bebas (sekarang hanya slug tetap di konstanta `PAGES`) |
| Slider, Gallery(+Photo), Document, ContactMessage | **Pakai, ubah enum** | Kategori dokumen, subjek pesan kontak diganti; dokumen tambah `visibility` |
| Blog + Category + Tag + `BlogController` + `Site\NewsController` | **Pakai, sederhanakan** | Jadi "Berita"; hapus alur review anggota & `BlogAwaitingReview`, hapus `organization_id` |
| Event, Participant, `EventRegistrar`, check-in, `MyTrainingController` | **Pakai, sederhanakan** | Jadi "Agenda"; hapus biaya, bukti bayar, SKP, `organization_id`; pendaftaran publik tanpa akun |
| Certificate, `CertificateController`, `GenerateCertificatePdf`, `certificates/pdf.blade.php`, `site.certificate` | **Pakai, ubah relasi** | `participant_id` → `skema_result_id`; `IssueEventCertificates` diganti `IssueSkemaCertificates` |
| Member, `MemberRegistrar`, `MemberImporter`, `MembershipController` | **Pakai, ubah skema** | Kolom AKP2I (KIP, brevet, sertifikasi, NPWP, kantor, SK) dihapus; kolom Mantaaba ditambah (9.4) |
| ActivityLog, `ActivityLogController`, concern `LogsActivity`, `HasSlug`, `FlushesSiteCache` | **Pakai** | Dipasang di model baru |
| `App\Support\Upload`, `App\Support\Html`, komponen `x-dashboard.*`, `x-site.*` | **Pakai** | – |
| Organization, BoardPeriod, BoardMember, Division, Position (+controller, view, seeder, test) | **Hapus** | Struktur yayasan → blok "Tim" di CMS |
| MemberDue, `MemberDueController`, `SuspensionController`, command `members:suspension-candidates`, `SuspensionCandidatesReport` | **Hapus** | Termasuk jadwal di `app/Console/Kernel.php` |
| Thread, Comment, ForumCategory, Report (+`Site\ForumController`, `ForumReplyPosted`) | **Hapus** | – |
| Research (+`Site\JournalController`) | **Hapus** | Diganti Dokumen |
| `routes/api.php` (sanctum), dashboard middleware `auth:sanctum` | **Ubah** | Dashboard memakai `auth`; route API dikosongkan |
| `config/akp2i.php` | **Ganti** | `config/mantaaba.php` (label role, batas super admin, definisi menu sidebar) |
| Test `tests/Feature/M1/*`, `M3/ContentTest` (forum/jurnal), bagian organisasi/iuran di test lain | **Hapus** | Test Auth, `AuthorizationTest`, `M2/SiteTest`, `M4/EventTest` (sertifikat & check-in), `DashboardSmokeTest`, `DatatableServerSideTest` **diadaptasi** |
| Seeder & `Demo/*` | **Tulis ulang** | Bagian 13 |
| Migrasi | **Squash** | Belum ada data produksi Mantaaba → migrasi AKP2I dirangkum ulang menjadi migrasi bersih Mantaaba di M0 |

### 3.4 Perbaikan teknis yang ikut dikerjakan
| # | Masalah | Perbaikan |
|---|---|---|
| T1 | Nomor sertifikat dihitung dari posisi peserta di daftar (`IssueEventCertificates`) → bentrok dengan `certificates.number` unik saat terbit ulang setelah daftar berubah | Nomor urut = `max(urut)+1` per skema per tahun, diambil di dalam transaksi + `lockForUpdate` |
| T2 | `tailwind.config.js` tidak memindai `resources/views/auth/**` & `dashboard/**` | Tambah ke `content` |
| T3 | `MembershipController` membaca `Setting::first()` (melewati cache) | Pakai `Setting::current()` |
| T4 | Sertifikat manual disimpan di disk public | Simpan di disk `local` |
| T5 | Check-in QR memakai `BarcodeDetector` (tidak tersedia di semua browser, mis. Safari lama) | Pertahankan input kode manual; tampilkan pesan bila API tidak ada |
| T6 | Menu header situs hard-coded di `components/site/header.blade.php`; sidebar dashboard hard-coded di `aside/list.blade.php` + `AsideProvider` | Header dari tabel `nav_menus`; sidebar dari array `config('mantaaba.menu')` yang di-loop dengan `@can` (hapus `AsideProvider`) |
| T7 | Role name hard-coded di binding `RouteServiceProvider` (`general`, `member`) dan `role-badge` | Disesuaikan ke role baru |

### 3.5 Permukaan rebranding
- ±170 kemunculan "akp2i" (app 15, views 53, config 10, database 64, tests 26), istilah PD/PC/admin_pd/admin_pc/Jawa Barat/PPL/brevet/SKP tersebar di app, views, database, tests.
- Warna `#00923f`: `tailwind.config.js`, `certificates/pdf.blade.php`, dan 223× di `public/css/style.bundle.css` → **find-replace sekali** (beserta varian hover/light) ke warna primer dashboard Mantaaba (Bagian 7.2).
- Logo: `public/media/logos/akp2i.png`, `akp2i-icon.png`, `logo-baru.webp` (logo "Satu Dekade AKP2I") → dihapus; diganti `logo-mantaaba.jpeg` (dan versi turunan, Bagian 7.4). Default `Setting::imageUrl()` dan layout dashboard ikut diganti.
- Teks: `config/app.php` name, `config/mail.php` from-name, `components/dashboard/head/meta.blade.php`, fallback deskripsi layout situs, email default `Setting::current()`, judul halaman di migrasi CMS, pesan `EventRegistrar` "khusus anggota AKP2I".
- **AC M0**: `grep -ri "akp2i\|admin_pd\|admin_pc\|00923f" app config database resources routes tests` tidak menemukan apa pun (kecuali catatan sejarah di README).

### 3.6 Identitas & konten
- Logo resmi: `public/media/logos/logo-mantaaba.jpeg` — lencana bundar bergaya ornamen kayu & emas, aksen marun dan bunga oranye, teks **"MAN TAABA · من تاب · SIGALING"**, latar putih, 640×640.
- Website lama & lokasi Google Maps tidak dapat dibaca otomatis (verifikasi bot / pembatasan Google). Alamat, kontak, foto, dan konten lama diisi lewat CMS saat M8 **[TBD Q1]**.
- Konten awal halaman Tentang/Program/Kurikulum diambil dari Grand Design (Bagian 13).

---

## 4. Setup Repo & Lingkungan (langkah pertama M0)

1. **Lepas dari repo AKP2I**: pindahkan `.git` lama ke cadangan (`mv .git ../akp2i-git-backup`, riwayat AKP2I tetap ada di GitHub `auliaharvy/akp2i_pd_jabar`), lalu `git init -b main`.
2. `.gitignore` memastikan `.env`, `vendor/`, `node_modules/`, `.DS_Store`, `public/storage`, `public/build`, `storage/*.key`, `.phpunit.cache` tidak ikut.
3. Commit awal "Fondasi Mantaaba dari basis AKP2I" (kode apa adanya + PRD + dokumen acuan), `git remote add origin https://github.com/auliaharvy/mantaaba.git`, `git push -u origin main`.
4. Database MAMP: `CREATE DATABASE mantaaba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` dan `mantaaba_test` dengan collation sama.
5. `.env`: `APP_NAME="Rumah Mantaaba Sigaling"`, `DB_DATABASE=mantaaba`, `MANTAABA_ADMIN_EMAIL=...`; `.env.example` diperbarui (hapus `AKP2I_*`). `phpunit.xml`: `DB_DATABASE=mantaaba_test`.
6. `php artisan migrate --seed` di `mantaaba` dan `php artisan test` hijau **sebelum** pembersihan modul dimulai (baseline).
7. Alur kerja selanjutnya: branch per milestone (`m0-fondasi`, `m1-cms`, …), checklist `docs/milestones/M{n}.md` diperbarui tiap langkah, satu commit per langkah, merge ke `main` setelah review di akhir milestone.

---

## 5. Persona & Peran

### 5.1 Persona
| Persona | Kebutuhan utama |
|---|---|
| Super Admin | Pengguna, hak akses, setting sistem, semua data |
| Pengurus — Ketua/Sekretariat | Penerimaan siswa, data anggota, konten website, pengumuman |
| Pengurus — Mentor/Pengajar | Materi skema, tugas, penilaian 4 dimensi, catatan pembinaan, sertifikat |
| Pengurus — Bendahara | Menyetujui & memposting voucher, tutup periode, laporan, verifikasi donasi, anggaran |
| Pengurus — Koordinator operasional | Asrama, piket, kebun, kolam, dapur, stok, aset |
| Anggota — Siswa (Fase I) | Belajar, nilai & sertifikat, piket, mencatat operasional & voucher draf sesuai tugasnya |
| Anggota — Alumni / Fase II | Perjalanan, sertifikat, agenda Sedulur Selawase, pengumuman |
| Anggota — Relawan/Mitra/Donatur | Tercatat; akun opsional **[TBD Q3]** |
| Calon siswa & wali | Mengisi formulir, mengecek status |
| Pengunjung | Profil lembaga, program, unit bisnis, berita, donasi, verifikasi sertifikat |

### 5.2 Role
Hanya **3 role** (spatie). Hak rinci per modul diberikan sebagai **permission langsung per pengguna** dari layar Hak Akses (9.18), dengan **paket** sebagai jalan pintas.

| Role | Default |
|---|---|
| `super_admin` | Semua (`Gate::before`). Satu-satunya yang mengelola pengguna, hak akses, setting sistem, buka-ulang periode akuntansi. Minimal 1, maksimal 3 orang. Tidak bisa dihapus/diturunkan oleh dirinya sendiri bila tinggal satu |
| `pengurus` | Akses dashboard; permission modul dari paket/centang |
| `anggota` | Profil sendiri, pembelajaran, sertifikat, piket sendiri, agenda, pengumuman. Permission operasional bisa ditambahkan per orang |

Calon siswa tidak punya akun. `/register` dimatikan. Akun dibuat saat pendaftar diterima atau oleh pengurus.

### 5.3 Daftar permission
Pola `{modul}-{view|create|update|delete}` + aksi khusus.

| Modul (kode) | Aksi khusus |
|---|---|
| `cms` (menu, halaman, slider, galeri, dokumen, unit bisnis, setting situs) | – |
| `news` | `news-publish` |
| `contact-message` | – |
| `announcement` | – |
| `batch` | – |
| `admission` | `admission-decide`, `admission-export` |
| `member` | `member-export`, `member-private` (lihat NIK, KK, data keluarga, kesehatan) |
| `coaching` | – |
| `health` | – |
| `learning` (skema, modul, materi, tugas) | `learning-review` (beri umpan balik tugas) |
| `assessment` | `certificate-issue`, `certificate-revoke` |
| `agenda` | `agenda-attendance` |
| `dorm` (kamar, area piket, jadwal) | `duty-verify` |
| `stock` | `stock-move`, `stock-opname` |
| `farm` | `farm-log` |
| `kitchen` | `kitchen-log` |
| `asset` | `asset-usage`, `asset-maintenance` |
| `coa` (COA, pihak, pusat biaya, anggaran) | – |
| `journal` | `journal-submit`, `journal-approve` (approve = posting), `period-close` |
| `finance-report` | – |
| `donation` | `donation-verify` |
| `user` | `user-permission` (hanya SA) |
| `setting` | – (hanya SA) |
| `activity-log` | – |

### 5.4 Paket hak akses (seed, bisa diubah SA)
| Paket | Isi |
|---|---|
| Pengurus Inti | Semua modul kecuali `user`, `setting`, `journal-approve`, `period-close` |
| Bendahara | `coa-*`, `journal-*` (termasuk approve & period-close), `finance-report-view`, `donation-*`, `stock-view`, `asset-view` |
| Mentor | `learning-*`, `assessment-*`, `coaching-*`, `member-view`, `agenda-*` |
| Koordinator Operasional | `dorm-*`, `stock-*`, `farm-*`, `kitchen-*`, `asset-*`, `journal-create`, `journal-submit` |
| Redaksi Website | `cms-*`, `news-*`, `contact-message-*`, `announcement-*` |
| Siswa Petugas Dapur | `kitchen-log`, `stock-view`, `stock-move`, `journal-create`, `journal-submit` |
| Siswa Petugas Kebun/Kolam | `farm-log`, `stock-view`, `stock-move`, `journal-create`, `journal-submit` |
| Siswa Petugas Keuangan | `journal-view`, `journal-create`, `journal-submit`, `finance-report-view` |

Aturan:
- Setiap aksi controller memanggil policy. Menu sidebar hanya menampilkan modul yang diizinkan.
- **Segregation of duties (hard-stop)**: pembuat voucher tidak bisa menyetujui voucher sendiri, termasuk Super Admin.
- Anggota hanya melihat datanya sendiri; query di-scope `user_id`/`member_id`.
- Data privat (`member-private`) tidak dimuat di query DataTables bila user tidak berhak (bukan hanya disembunyikan kolomnya).

---

## 6. Arsitektur & Konvensi

### 6.1 Stack
| Lapisan | Pilihan |
|---|---|
| Framework | Laravel 12 (struktur L8 dipertahankan), PHP 8.2+ |
| Database | MySQL 5.7+/8 (dev MAMP), utf8mb4; angka uang `decimal(18,2)`, kuantitas `decimal(14,3)` |
| Auth & otorisasi | Breeze + spatie/laravel-permission 6 + Policy |
| Dashboard | Metronic 8, DataTables server-side, select2, SweetAlert, TinyMCE, SortableJS & ApexCharts (sudah dibundel Metronic) |
| Situs publik | Blade + Tailwind + Alpine (`x-site.*`) |
| PDF & QR | dompdf + simple-qrcode (sertifikat, kartu anggota, label aset, laporan keuangan, voucher) |
| File | Disk `public` untuk konten situs; disk `local` untuk dokumen pendaftar/anggota, lampiran voucher, bukti donasi, sertifikat |
| Queue & scheduler | Driver `database`; cron `schedule:run`; di shared hosting cron juga menjalankan `queue:work --stop-when-empty` |
| Mail | SMTP via queue; WhatsApp lewat tautan `wa.me` dengan pesan terisi (tanpa API) |
| Locale | `id`, `Asia/Jakarta`, Rupiah `Rp 1.234.567`, tanggal `16 Nov 2026` |
| Hosting | Shared hosting cPanel **[TBD Q9]** |

Prinsip: tanpa package baru. Import/export CSV pakai `fgetcsv`/`fputcsv`. Perhitungan uang dengan `bcadd`/integer sen atau `decimal` di SQL, bukan float PHP.

### 6.2 Konvensi
- **Route**: URL `/dashboard/{slug-indonesia}` (mis. `/dashboard/kebun`), nama route `{modul}.*` mengikuti konvensi yang ada (mis. `farm-cycle.index`), DataTables `{modul}-datatable`. Route publik `site.*`. Satu file route per area: `routes/permission/public.php`, `routes/permission/administrator.php` (dikelompokkan per modul dengan komentar).
- **Controller**: satu resource controller per entitas; aksi khusus (approve, post, issue) sebagai method terpisah dengan route `POST`.
- **View**: `resources/views/dashboard/{modul}/{index,form,show}.blade.php`, publik `resources/views/site/{modul}/...`.
- **Service**: logika lintas tabel di `app/Services/*` (contoh `JournalPoster`, `StockLedger`, `AdmissionDecider`, `SkemaCertificateIssuer`), selalu dalam `DB::transaction` + `lockForUpdate` bila menyangkut nomor/stok/saldo.
- **Enum**: konstanta array di model (pola yang ada, mis. `Event::TYPES`), label Bahasa Indonesia.
- **Sidebar**: `config('mantaaba.menu')` = daftar grup → item `[permission, route, routePattern, title, icon]`.

### 6.3 Peta sidebar dashboard
```
Beranda
Website        Halaman · Menu · Slider · Berita · Galeri · Dokumen · Unit Bisnis · Pesan Masuk · Setting Situs
Penerimaan     Angkatan · Pendaftar
Anggota        Data Anggota · Catatan Pembinaan · Kesehatan · Pengumuman
Pembelajaran   Skema & Materi · Tugas Masuk · Penilaian · Sertifikat
Asrama         Kamar · Jadwal Piket · Agenda
Operasional    Kebun & Kolam · Dapur · Stok & Gudang · Aset & Kendaraan
Keuangan       Voucher · Buku Besar · Laporan · Donasi · Anggaran · COA & Master · Periode
Sistem         Pengguna & Hak Akses · Log Aktivitas · Setting Sistem
--- (anggota) ---
Beranda Saya · Belajar · Sertifikat · Piket Saya · Agenda · Profil
```

---

## 7. Design System

### 7.1 Situs publik (token di Setting → CSS variable di layout)
Diturunkan dari logo: kayu, emas, marun, krem.

| Token | Default | Pemakaian |
|---|---|---|
| `--color-primary` | `#5B3A1F` (cokelat tua) | Header, tombol utama, judul |
| `--color-primary-dark` | `#3E2714` | Hover, footer |
| `--color-secondary` | `#C9A04A` (emas) | Garis ornamen, ikon, badge — **tidak untuk teks di atas putih** (kontras rendah) |
| `--color-accent` | `#8A2D1E` (marun) | CTA sekunder, highlight, label donasi |
| `--color-surface` | `#F7F1E6` (krem kayu) | Latar section selang-seling |
| `--color-ink` | `#2B1D12` | Teks utama |
| `--color-muted` | `#6E5B4B` | Teks sekunder |
| Font display | `Cinzel` | Judul hero & section pendek (huruf kapital, selaras logo) |
| Font teks | `Inter` | Isi, navigasi, form |
| Font Arab | `Amiri` | Teks Arab & kutipan ayat |
| Radius / spasi | 10px / skala 4px | |

- Motif: garis ornamen emas tipis sebagai pembatas section (SVG inline), foto asli (asrama, kebun, kolam, dapur, kegiatan) sebagai visual utama.
- `tailwind.config.js`: warna `brand.*` mengacu ke CSS variable (`rgb(var(--color-primary) / <alpha-value>)`), sehingga Setting langsung berlaku tanpa build ulang.
- Kontras teks ≥ 4.5:1 (cokelat/ink di atas putih/krem lolos; emas hanya dekoratif).
- Komponen `x-site.*` yang ada dipakai ulang, ditambah: `ornament-divider`, `block-*` (renderer blok halaman), `stat`, `timeline` (roadmap Fase I–II), `pillar-card` (5 skema).

### 7.2 Dashboard
Warna primer Metronic diganti satu kali di `style.bundle.css` (find-replace `#00923f` + varian) menjadi `#5B3A1F`, aksen `#C9A04A`. Logo dashboard dari Setting. Dashboard tidak mengikuti warna dinamis Setting (cukup statis).

### 7.3 Dokumen PDF
Sertifikat, kartu anggota, voucher, dan laporan keuangan memakai kop: logo, nama lembaga, alamat (Setting), garis ornamen emas, font serif untuk judul. Template latar sertifikat bisa diunggah per skema.

### 7.4 Aset logo yang dibutuhkan
Dari `logo-mantaaba.jpeg` dibuat (M1): `logo-mantaaba.png` transparan (bila tersedia dari desainer **[TBD Q2]**), `logo-mantaaba-512.webp`, `favicon` 32/180 px (dipotong ke bagian lencana tengah agar terbaca di ukuran kecil).

---

## 8. Sitemap Situs Publik

Menu header & footer diatur di Menu Manager. Halaman di bawah adalah default seed.

| URL | Route | Isi | Sumber |
|---|---|---|---|
| `/` | `site.home` | Beranda | Halaman blok `home` |
| `/tentang` | `site.page` | Profil, makna nama, visi-misi, filosofi 'Asabiyah & Fastabiqul Khairat, struktur yayasan | Halaman blok |
| `/program` | `site.page` | Inkubasi 6 bulan, roadmap Fase I–II, profil lulusan, penilaian 4 dimensi | Halaman blok |
| `/kurikulum` | `site.curriculum` | 5 skema + modul + matriks karakter (Grand Design 5) | Data skema (`is_public`) |
| `/ekosistem`, `/ekosistem/{slug}` | `site.units.*` | Unit bisnis & laboratorium kehidupan | Unit bisnis |
| `/berita`, `/berita/{slug}`, `/berita/kategori/{slug}` | `site.news.*` | Berita & artikel | Berita |
| `/agenda`, `/agenda/{slug}` | `site.agenda.*` | Kegiatan publik + pendaftaran hadir | Agenda |
| `/galeri`, `/galeri/{slug}` | `site.gallery.*` | Album foto | Galeri |
| `/dokumen` | `site.documents` | Laporan transparansi, profil, unduhan | Dokumen (`visibility=publik`) |
| `/pendaftaran` | `site.admission` | Info angkatan dibuka + syarat + formulir | Angkatan & Setting |
| `/pendaftaran/status` | `site.admission.status` | Cek status | Pendaftar |
| `/donasi` | `site.donation` | Rekening, QRIS, ringkasan transparansi, konfirmasi donasi | Setting + donasi + laporan |
| `/kontak` | `site.contact` | Alamat, peta, WA, formulir pesan | Setting + pesan kontak |
| `/sertifikat/{kode}` | `site.certificate` | Verifikasi sertifikat | Sertifikat |
| `/kebijakan-privasi` | `site.page` | Kebijakan privasi | Halaman |
| `/{slug}` | `site.page` | Halaman bebas | Halaman blok |
| `/sitemap.xml`, `/robots.txt` | yang ada | SEO | Otomatis |

Semua halaman: meta title/description, Open Graph (gambar default dari Setting), responsif 360px–desktop, empty state ramah bila belum ada konten.

---

## 9. Modul & Kebutuhan Fungsional

### 9.1 CMS Website

#### 9.1.1 Setting situs (`settings`, satu baris)
| Grup | Field |
|---|---|
| Identitas | `site_name` ("Rumah Mantaaba Sigaling"), `foundation_name` ("Yayasan Mantaaba"), `tagline`, `logo`, `logo_footer`, `favicon`, `og_image`, `meta_description` |
| Tampilan | `color_primary`, `color_secondary`, `color_accent`, `color_surface` (validasi hex) |
| Kontak | `address`, `village`, `district`, `regency`, `province`, `postal_code`, `latitude`, `longitude`, `maps_embed` (hanya URL `google.com/maps/embed`), `phone`, `whatsapp`, `email`, `office_hours` |
| Media sosial | `instagram`, `facebook`, `youtube`, `tiktok`, `linkedin` |
| Donasi | `bank_accounts` (json, maks. 3: bank, nomor, atas nama), `qris_image`, `donation_note` |
| Pendaftaran | `admission_requirements` (rich text), `parent_consent_template` (file), `integrity_pact_template` (file), `priority_districts` (daftar kecamatan prioritas) |
| Akademik | `assessment_weights` (json, default K20/S25/C30/SC25), `default_pass_score` (70), `min_character_score` (70) |
| Operasional | `meal_budget_per_person_day` (Rp), `default_residents` |
| Akuntansi | `accounting_start_date`, `report_signatories` (json: ketua, bendahara), `require_separate_approver` (selalu true; hanya informasi) |
| Lainnya | `privacy_policy` (rich text), `footer_text` |

`Setting::current()` + cache dipertahankan; setiap simpan membersihkan cache situs.

#### 9.1.2 Menu Manager
`nav_menus`: id, location (`header`,`footer`), parent_id, label, type (`page`,`route`,`url`), page_id null, route_name null (daftar route publik yang diizinkan), url null, open_new_tab, sort_order, is_active.
- Drag & drop (SortableJS), maksimal 2 level untuk header, 1 level untuk footer.
- Menghapus halaman yang dipakai menu → ditolak dengan daftar menu yang memakainya.

#### 9.1.3 Halaman berbasis blok
`pages` ditambah: `blocks` (json), `template` (`blocks`,`rich`), `is_system` (beranda & kebijakan privasi tidak bisa dihapus), `show_in_sitemap`.

| Blok | Field |
|---|---|
| `hero` | judul, subjudul, gambar latar, overlay (0–80%), tombol ×2 (teks, URL) |
| `slider` | pakai modul Slider |
| `text` | rich text |
| `image_text` | gambar, posisi kiri/kanan, judul, rich text, tombol |
| `cards` | judul section, kolom (2/3/4); item: ikon (FontAwesome) atau gambar, judul, teks, tautan |
| `stats` | item manual (angka, label) atau otomatis (`anggota_aktif`, `alumni`, `angkatan`, `sertifikat_terbit`) |
| `timeline` | item: label waktu, judul, teks (untuk roadmap Fase I–II) |
| `team` | item: foto, nama, jabatan, kutipan singkat |
| `gallery` | album |
| `latest` | sumber (`berita`,`agenda`), jumlah |
| `units` | unit bisnis terpilih / semua |
| `skemas` | ringkasan 5 skema dari data pembelajaran |
| `testimonial` | item: foto, nama, peran, kutipan |
| `faq` | item: pertanyaan, jawaban |
| `cta` | judul, teks, tombol, gaya (primer/aksen/krem) |
| `quote` | teks Arab (opsional), terjemahan, sumber |
| `embed` | URL YouTube atau Google Maps embed saja |

Editor (dashboard): daftar blok yang bisa ditambah, diurutkan (drag), diduplikasi, disembunyikan, dihapus; form per blok dirender dari definisi field di `config/mantaaba.php` (`blocks`), sehingga menambah blok baru = menambah definisi + partial Blade. Tombol **Pratinjau** membuka halaman draf di tab baru (route bertanda tangan, hanya pengurus). Gambar blok lewat `Upload::store()`.

Validasi: setiap blok divalidasi sesuai definisinya; rich text lewat `Html::clean()`; URL embed whitelist domain.

#### 9.1.4 Konten lain
| Modul | Perubahan dari basis |
|---|---|
| Berita (`blogs`) | Status `draft`,`published`; `published_at` bisa dijadwalkan; kategori & tag tetap; tanpa review anggota |
| Slider | Tetap |
| Galeri | Tetap; `event_id` → relasi ke Agenda |
| Dokumen | Kategori `Profil Lembaga`, `Laporan Transparansi`, `Pedoman & Tata Tertib`, `Formulir`, `Lainnya`; kolom `visibility` (`publik`,`anggota`) — dokumen `anggota` hanya untuk pengguna login, file lewat route ber-policy |
| Pesan kontak | Subjek `Pendaftaran`, `Donasi`, `Kemitraan`, `Kunjungan`, `Lainnya`; tombol balas via email/WA |

#### 9.1.5 Unit Bisnis
`business_units`: id, name, slug, type (`unit_bisnis`,`laboratorium`), field (bidang usaha), summary, body, logo, cover, gallery_id null, contact_name, phone, website, address, is_public, sort_order, cost_center_id (dibuat otomatis).
Dipakai di: halaman `/ekosistem`, penempatan anggota (9.4), pusat biaya akuntansi (9.13).

**AC 9.1**
- Pengurus membuat halaman baru dari blok, menambah ke menu, dan halaman tampil tanpa deploy.
- Mengubah `color_primary` langsung mengubah header & tombol situs.
- Blok `embed` dengan domain di luar whitelist → error validasi.
- Halaman `is_system` tidak bisa dihapus (403).
- Dokumen `anggota` diakses tanpa login → redirect ke login; file tidak punya URL publik.
- Cache halaman dibersihkan saat halaman/menu/setting/berita berubah.

### 9.2 Angkatan
`batches`: id, code (`2027-1`), name, admission_opens_at, admission_closes_at, starts_at, ends_at, quota (default 6), status (`draft`,`open`,`selection`,`running`,`done`), description, requirements_note.

- Hanya satu angkatan `open` pada satu waktu (hard-stop).
- Status `running` → semua siswa diterima otomatis di-enroll ke skema Fundamental (dibuka) dan skema lain (terkunci).
- Halaman detail angkatan: tab Pendaftar · Siswa · Progres skema (matriks siswa × skema) · Piket · Agenda.

### 9.3 Penerimaan Anggota Baru

#### Formulir publik (`/pendaftaran`)
Tanpa login, rate limit 5/jam per IP, honeypot, langkah (wizard Alpine, satu form POST di akhir):

| Langkah | Field (wajib *) |
|---|---|
| 1. Data diri | nama*, NIK* (16 digit), tempat lahir*, tanggal lahir* (usia 16–25 → di luar rentang tampil peringatan, tidak ditolak **[TBD Q5]**), jenis kelamin*, no. WA*, email, alamat*, desa*, kecamatan*, kabupaten* |
| 2. Pendidikan | pendidikan terakhir*, nama sekolah, tahun lulus |
| 3. Keluarga | nama ayah, nama ibu, nama wali, pekerjaan orang tua, no. WA orang tua/wali*, status keluarga* (`lengkap`,`yatim`,`piatu`,`yatim_piatu`), kondisi ekonomi* (`mampu`,`kurang_mampu`), jumlah saudara |
| 4. Motivasi | motivasi* (min. 100 karakter), sumber informasi*, riwayat kesehatan/penyakit khusus |
| 5. Berkas | foto*, KTP/KK*, ijazah/SKL, SKTM, surat persetujuan orang tua* (template dari Setting) — jpg/png/pdf, maks. 2 MB, disk `local` |
| 6. Pernyataan | bersedia tinggal di asrama*, bersedia menandatangani pakta integritas*, setuju kebijakan privasi* |

Selesai → halaman sukses dengan **nomor pendaftaran** `PMB-{kode angkatan}-{urut 3 digit}` + tombol simpan sebagai PDF bukti pendaftaran + tautan WA ke admin.

`applicants`: id, batch_id, registration_number (unik), semua field di atas, documents (json path), is_priority (otomatis), status, scores (json), interview_at null, interview_note, home_visit_at null, home_visit_note, decision_note, decided_by, decided_at, member_id null, softDeletes.

#### Seleksi (dashboard)
```
baru → verifikasi_berkas → wawancara → kunjungan_rumah (opsional) → diterima
  └───────────┴───────────────┴──────────────┴──→ ditolak | mengundurkan_diri
```

```
┌ Pendaftar · Angkatan 2027-1 (kuota 6 · diterima 2) ──────── [Export CSV] ┐
│ [Baru 7] [Verifikasi 3] [Wawancara 4] [Kunjungan 1] [Diterima 2] [Ditolak 5] │
│ Cari nama/NIK…        Filter: [Prioritas ▾] [Kecamatan ▾]                   │
│ No.            Nama            Kec.        Status keluarga  Skor  Tahap      │
│ PMB-2027-1-004 Rizal Maulana   Sigaling ★  Yatim            4,3   Wawancara  │
│ PMB-2027-1-007 Ahmad Fauzi     Cibeber     Lengkap          3,8   Verifikasi │
└──────────────────────────────────────────────────────────────────────────────┘
★ = prioritas (domisili kecamatan prioritas, yatim/piatu, atau kurang mampu)

Detail pendaftar: data · berkas (pratinjau) · skor · riwayat status · [Pindah tahap ▾] [Terima] [Tolak]
```

- Skor 1–5: motivasi, komitmen, kondisi keluarga, hasil wawancara, hasil kunjungan → rata-rata.
- Pindah tahap wajib catatan; jadwal wawancara/kunjungan mengisi tanggal + tautan WA pemberitahuan ke pendaftar.
- **Terima** (`admission-decide`, service `AdmissionDecider`, dalam transaksi + lock kuota): buat `users` (role `anggota`, password acak) + `members` (kategori `siswa`, status `aktif`, fase `inkubasi`, batch, data disalin dari pendaftar, berkas dipindah ke folder anggota) → tampil tautan WA berisi username & tautan atur password (token reset password Laravel).
- Status publik di `/pendaftaran/status`: nomor + tanggal lahir → tahap saat ini dengan bahasa ramah (tanpa skor/catatan internal).
- Data pendaftar `ditolak`/`mengundurkan_diri` dihapus permanen beserta berkas 12 bulan setelah keputusan (scheduler) **[TBD Q8]**.

**AC 9.3**
- NIK yang sama di angkatan yang sama → ditolak "NIK sudah terdaftar".
- Cek status dengan tanggal lahir salah → "Data tidak ditemukan" (pesan sama untuk nomor salah).
- Berkas pendaftar tidak dapat diakses tanpa login + `admission-view`.
- Menerima pendaftar saat kuota penuh → ditolak "Kuota angkatan penuh"; dua pengurus menerima bersamaan tidak bisa melampaui kuota (test konkurensi lewat lock).
- Pendaftaran di luar jendela angkatan `open` → formulir tidak tampil.

### 9.4 Data Anggota

`members` (ditulis ulang):
| Kolom | Keterangan |
|---|---|
| user_id | 1–1 users |
| member_number | `MTB-{tahun masuk}{urut 4 digit}`, unik, otomatis |
| category | `siswa`,`alumni`,`pengurus`,`relawan`,`mitra`,`donatur` |
| status | `aktif`,`cuti`,`mengundurkan_diri`,`dikeluarkan`,`lulus` |
| phase | `inkubasi`,`magang`,`bekerja`,`ownership`,`alumni` (siswa/alumni saja) |
| batch_id, applicant_id | asal |
| nik, birth_place, birth_date, gender, address, village, district, regency, phone | privat (`member-private`) |
| father_name, mother_name, guardian_name, guardian_phone, family_status, economic_status | privat |
| room_id | kamar asrama |
| joined_at, left_at, left_reason | |
| integrity_pact_signed_at, integrity_pact_file | pakta integritas |
| show_on_site | izin tampil nama/foto di website (default false) |
| photo, notes, softDeletes | |

**Perjalanan** — `member_journeys`: id, member_id, type (`fase`,`penempatan`,`jabatan`,`pendidikan_formal`,`prestasi`,`lainnya`), title, business_unit_id null, role_level (`staf`,`supervisor`,`manajer`,`direktur`) null, started_at, ended_at null, meta (json: mis. semester & IPK UT, NIM), notes. Satu penempatan aktif per anggota (ended_at null) — penempatan baru menutup yang lama otomatis.

**Kesehatan** — `health_records`: id, member_id, date, complaint, action (`istirahat`,`obat`,`puskesmas`,`klinik`,`rumah_sakit`), facility, cost null, note, created_by. Biaya > 0 → tombol "Buat voucher" (pengeluaran Beban Kesehatan Siswa). Privat (`health-view`).

**Pakta integritas**: saat siswa baru login pertama kali, tampil teks pakta (dari Setting) + centang "Saya menyetujui" → `integrity_pact_signed_at`; pengurus mengunggah scan pakta bertanda tangan.

**Detail anggota (pengurus)**
```
┌ [foto] Rizal Maulana · MTB-20270004 · Siswa · Aktif · Fase: Inkubasi (bulan 3) ┐
│ Angkatan 2027-1 · Kamar B-2 · Penempatan: –             [Edit] [Kartu] [⋮]    │
│ Lencana skema: [FND ✓] [SIM ●] [SIA ○] [SIP ○] [IT ○]                         │
├ Profil │ Perjalanan │ Belajar │ Nilai & Sertifikat │ Pembinaan │ Kesehatan │ Piket │ Agenda │ Berkas ┤
│ Pembinaan (30 hari): 6 positif · 1 perhatian · 0 pelanggaran                   │
│ 12 Nov  Positif   Kontribusi tim  "Membantu Fajar menyelesaikan jurnal"  Ust. A │
│ 08 Nov  Perhatian Disiplin        Piket dapur terlewat (otomatis)        Sistem │
└────────────────────────────────────────────────────────────────────────────────┘
```

**Fitur**: filter kategori/status/fase/angkatan/penempatan, export CSV (kolom privat hanya dengan `member-private`), import CSV (`MemberImporter` diadaptasi, untuk data awal), kartu anggota PDF ber-QR (QR ke halaman verifikasi anggota internal), ubah status dengan alasan wajib.
- `mengundurkan_diri`/`dikeluarkan` → `left_at` terisi, akun nonaktif, keluar dari kamar & jadwal piket mendatang.
- `lulus` (selesai S1, Grand Design 6) → kategori `alumni`, fase `alumni`, akun tetap aktif.

**Profil anggota (sisi anggota)** `/dashboard/profil`: foto, telepon, alamat bisa diubah; data lain read-only; ganti password; perjalanan; sertifikat; catatan pembinaan ber-visibilitas `anggota`; riwayat kesehatan sendiri.

**AC 9.4**
- Anggota membuka detail anggota lain → 403.
- Pengurus tanpa `member-private` tidak menerima NIK/alamat/keluarga di response DataTables maupun export.
- Anggota `dikeluarkan` tidak bisa login.
- Website hanya menampilkan anggota dengan `show_on_site = true`.

### 9.5 Catatan Pembinaan
`coaching_notes`: id, member_id, author_id null (null = sistem), date, type (`positif`,`perhatian`,`pelanggaran_ringan`,`pelanggaran_sedang`,`pelanggaran_berat`), dimension (`disiplin`,`kejujuran`,`adab`,`tanggung_jawab`,`kontribusi_tim`,`inisiatif`), body, visibility (`privat`,`anggota`), follow_up null.
- Input cepat dari HP: pilih siswa (bisa banyak sekaligus untuk catatan kelompok), tipe, dimensi, teks.
- Ringkasan per anggota per periode skema dipakai di form penilaian (Character ← disiplin/kejujuran/adab/tanggung jawab; Social Cohesion ← kontribusi tim; Fastabiqul Khairat ← inisiatif).
- 3 pelanggaran sedang atau 1 pelanggaran berat dalam 30 hari → badge merah + notifikasi ke Ketua (keputusan DO tetap manual lewat ubah status).

### 9.6 Pembelajaran

```
skemas ──< modules ──< lessons
   └──< skema_enrollments (per anggota) ── skema_results (9.7)
```

| Tabel | Kolom |
|---|---|
| `skemas` | id, code (`FND`,`SIM`,`SIA`,`SIP`,`IT`), name, month_label, description, objectives (rich), character_matrix (json: 'Asabiyah & Fastabiqul Khairat per Grand Design 5), pass_score null (default dari Setting), certificate_template, certificate_signatories (json ≤2: nama, jabatan, gambar ttd), is_public, sort_order |
| `modules` | id, skema_id, code, title, summary, sort_order |
| `lessons` | id, module_id, title, type (`text`,`video`,`file`,`assignment`), body, video_url (YouTube), file, due_days null (tugas), is_published, sort_order |
| `skema_enrollments` | id, member_id, skema_id, opened_at null, status (`terkunci`,`berjalan`,`lulus`,`belum_lulus`), unik (member_id, skema_id) |
| `lesson_progress` | id, user_id, lesson_id, completed_at, submission_file, submission_text, submitted_at, feedback, reviewed_by, reviewed_at, score null; unik (user_id, lesson_id) |

**Sisi anggota** (`/dashboard/belajar`)
```
┌ Belajar ─────────────────────────────────────────────────────┐
│ Fundamental   ██████████ 100%  ✓ Lulus (86,5)  [Sertifikat]   │
│ SIM           ██████░░░░  60%  Berjalan        [Lanjutkan]    │
│ SIA           🔒 Dibuka mentor setelah SIM                     │
│ …                                                             │
└───────────────────────────────────────────────────────────────┘
SIM › Modul 3 SOP › Materi 2/4 "Menyusun SOP Dapur"
[video/teks/file] … [Tandai selesai]   |  Tugas: unggah SOP (pdf/docx) · tenggat 20 Nov
```
- Materi tampil berurutan; lesson `assignment` selesai saat tugas diunggah.
- Tugas yang sudah dinilai tidak bisa diganti (kecuali mentor membuka ulang).

**Sisi pengurus**: CRUD skema/modul/materi (drag urutan), **Buka skema** untuk satu angkatan sekaligus, antrean **Tugas Masuk** (filter skema/angkatan/belum dinilai) dengan umpan balik + nilai 0–100.

**AC 9.6**: anggota membuka materi skema `terkunci` → 403; file tugas hanya bisa diunduh pemiliknya & pengurus `learning-view`.

### 9.7 Penilaian 4 Dimensi & Sertifikat

`skema_results`: id, enrollment_id (unik), knowledge, skill, character, social_cohesion (0–100, 2 desimal), final_score, passed, predicate, notes, assessed_by, assessed_at, locked_at (terisi saat sertifikat terbit).

- Bobot dari Setting (default Grand Design 7: **Knowledge 20%, Skill 25%, Character 30%, Social Cohesion 25%**; total wajib 100).
- `final_score = Σ(nilai × bobot) / 100`, dibulatkan 2 desimal (perhitungan di service, ada unit test).
- Lulus: `final_score ≥ pass_score` **dan** `character ≥ min_character_score` **[TBD Q5]**.
- Predikat: ≥ 90 Istimewa, ≥ 80 Sangat Baik, ≥ 70 Baik.
- Nilai pendukung otomatis ditampilkan di samping input: rata-rata nilai tugas (saran Knowledge/Skill), ringkasan catatan pembinaan per dimensi, kehadiran agenda `kelas`, persentase piket selesai.

```
┌ Penilaian · SIM · Angkatan 2027-1 ─────────── Bobot K20 S25 C30 SC25 · Lulus ≥70, C≥70 ┐
│ Siswa          Tugas  Pembinaan(+/!)  K    S    C    SC   Akhir  Hasil                 │
│ Rizal Maulana  84     6 / 1           [82] [85] [88] [90]  86,6  Lulus · Sangat Baik    │
│ Ahmad Fauzi    71     2 / 3           [75] [72] [65] [80]  72,4  Belum lulus (C<70)     │
│                                                      [Simpan semua] [Terbitkan sertifikat] │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

**Sertifikat** (`certificates`: id, skema_result_id unik, number unik, verification_code unik 12 karakter, file, issued_at, revoked_at, revoke_reason)
- **Terbitkan** (`certificate-issue`) → job `IssueSkemaCertificates` → per siswa lulus tanpa sertifikat valid: nomor `{urut 3 digit}/{kode skema}/RMS/{bulan romawi}/{tahun}` **[TBD Q4]** (urut = max+1 per skema per tahun, T1) → job `GenerateCertificatePdf` (diadaptasi) → notifikasi.
- Isi PDF: nama, nomor anggota, skema, daftar modul, nilai akhir & predikat, angkatan, tanggal, penandatangan, QR ke `/sertifikat/{kode}`. Halaman 2 (opsional): rincian 4 dimensi.
- Nilai terkunci (`locked_at`) setelah terbit; ubah nilai → harus **cabut** sertifikat dulu (`certificate-revoke`, alasan wajib), lalu terbit ulang (nomor baru, nomor lama tetap tercatat dicabut).
- **Lencana & kelulusan inkubasi**: profil menampilkan 5 lencana; kelima skema lulus → banner "Lulus Inkubasi" + tombol pengurus **Kukuhkan** → fase `magang`, entri `member_journeys` (`fase`), dan sertifikat **Kelulusan Inkubasi** (template sendiri, kode `INK`).
- Siswa mengunduh sertifikat di `/dashboard/sertifikat`.

**AC 9.7**
- Sertifikat tidak terbit untuk `passed = false`.
- Total bobot ≠ 100 di Setting → ditolak.
- Mengubah nilai yang terkunci → 422 "Cabut sertifikat terlebih dahulu".
- `/sertifikat/{kode}` menampilkan nama, skema, tanggal, status valid/dicabut; kode salah → 404.
- Terbit ulang setelah ada sertifikat dicabut tidak menimbulkan nomor duplikat (test T1).

### 9.8 Agenda & Kehadiran
Pakai ulang `events`/`participants` (disederhanakan):
- `events`: hapus `organization_id`, `fee_member`, `fee_public`, `skp_hours`, `meeting_url` tetap; `type` = `kajian_tajug`,`sedulur_selawase`,`kelas`,`kegiatan_umum`,`open_house`,`lainnya`; `audience` = `publik`,`anggota`,`angkatan` (+ `batch_id`); `quota`, jendela pendaftaran, `status` (`draft`,`open`,`closed`,`done`) tetap.
- `participants`: `user_id` nullable; tambah `guest_name`, `guest_phone`, `guest_email` untuk pendaftar publik; hapus `amount`, `payment_proof`, `verified_by`; status `terdaftar`,`hadir`,`batal`; `ticket_code` + `attended_at` tetap.
- Agenda `angkatan`/`anggota` → peserta otomatis semua anggota sasaran (tanpa daftar) dan kehadiran dicentang massal atau via QR kartu anggota.
- Agenda `publik` → formulir di `/agenda/{slug}` (nama, WA, email) + tiket QR di halaman sukses & email.
- **Laporan Sedulur Selawase**: alumni/anggota Fase II yang tidak hadir di agenda `sedulur_selawase` dalam 3 bulan terakhir (Grand Design 3), export CSV + tautan WA massal satu per satu.
- Kalender agenda di dashboard (FullCalendar Metronic) dan `/agenda`.

**AC**: pendaftar publik ke-(kuota+1) → "Kuota penuh"; kehadiran agenda `kelas` masuk ringkasan penilaian.

### 9.9 Asrama & Jadwal Piket
- `rooms`: id, code, name, building, capacity, gender, notes. Penghuni = `members.room_id`. Halaman denah: kartu per kamar (penghuni, sisa kapasitas). **Hard-stop**: kamar penuh atau gender tidak cocok → ditolak.
- `duty_areas`: id, name (seed: Asrama, Dapur, Kebun, Kolam, Tajug, Kebersihan Umum), description (SOP singkat/ceklis tugas, json), is_active.
- `duties`: id, duty_area_id, date, shift (`pagi`,`siang`,`sore`,`malam`), member_id, status (`terjadwal`,`selesai`,`diverifikasi`,`terlewat`), done_at, verified_by, note, photo.
- **Generator rotasi**: area + rentang tanggal + shift + daftar anggota + jumlah orang per shift → round-robin, menghindari orang yang sama dua kali sehari; hasil bisa diedit, tukar jadwal antar anggota.
- Anggota: "Piket hari ini" di dashboard → ceklis tugas area → **Selesai** (opsional foto). Pengurus `duty-verify` memverifikasi.
- Job harian 23:00: `terjadwal` hari itu → `terlewat` + catatan pembinaan otomatis (`perhatian`, `tanggung_jawab`).
- Laporan: persentase piket selesai per anggota per bulan (masuk ringkasan penilaian).

### 9.10 Stok & Gudang
Satu buku stok untuk semua barang agar panen mengalir ke dapur tanpa pencatatan ganda dan nilainya tersambung ke akuntansi.

| Tabel | Kolom |
|---|---|
| `warehouses` | id, code, name (seed: Gudang Dapur, Gudang Kebun & Kolam, Gudang Umum), pic_user_id |
| `items` | id, code, name, category (`bahan_pangan`,`bibit_benih`,`pupuk`,`pakan`,`obat_pertanian`,`habis_pakai`,`hasil_panen`,`lainnya`), unit, min_stock, avg_cost (rata-rata tertimbang), inventory_account_id, expense_account_id (default dari kategori), is_active |
| `stock_moves` | id, date, item_id, warehouse_id, type (`masuk_beli`,`masuk_panen`,`masuk_donasi`,`keluar_pakai`,`keluar_jual`,`keluar_rusak`,`transfer_keluar`,`transfer_masuk`,`opname`), qty (+/−), unit_cost, total_cost, party_id null, source_type/source_id (morph), journal_id null, note, created_by |
| `stock_opnames` | id, warehouse_id, date, status (`draft`,`final`), lines (json: item, sistem, fisik, selisih), created_by, finalized_by |

Aturan:
- Saldo = `SUM(qty)` per item per gudang (query, tanpa tabel saldo).
- **Penilaian rata-rata tertimbang**: setiap `masuk_beli`/`masuk_donasi` memperbarui `items.avg_cost`; semua `keluar_*` memakai `avg_cost` saat itu. `masuk_panen` bernilai **Rp 0** (biaya produksi sudah dibebankan saat input kebun dipakai) — nilai pasar panen dicatat terpisah di `harvests` untuk laporan operasional.
- **Hard-stop**: stok tidak boleh minus per gudang; transaksi bertanggal di periode akuntansi tertutup ditolak.
- Opname final → `stock_moves opname` sebesar selisih + jurnal penyesuaian (9.16).
- Peringatan stok < `min_stock` di dashboard & ringkasan harian.
- Kartu stok per item (mutasi + saldo berjalan), export CSV.

### 9.11 Kebun & Kolam

**Konsep**: setiap petak/kolam adalah unit produksi kecil. Setiap **siklus** (tanam/tebar → panen) mencatat input, aktivitas, dan hasil sehingga siswa bisa menghitung HPP per kg, tingkat hidup, dan surplus (praktik Matematika Bisnis & SIA).

| Tabel | Kolom |
|---|---|
| `farm_plots` | id, type (`lahan`,`kolam`,`kandang`), code (`L-01`,`K-01`), name, size, size_unit (`m2`,`m3`), location_note, photo, status (`kosong`,`aktif`,`istirahat`), pic_member_id |
| `farm_cycles` | id, plot_id, commodity, variety, started_at, expected_harvest_at, initial_qty, initial_unit, target_yield, target_unit, status (`berjalan`,`panen`,`selesai`,`gagal`), closed_at, failure_reason, notes |
| `farm_logs` | id, cycle_id, date, activity (`olah_lahan`,`tanam`,`tebar`,`siram`,`pupuk`,`pakan`,`semprot`,`sortir`,`kematian`,`hama_penyakit`,`pengukuran`,`lainnya`), qty, unit, item_id null, warehouse_id null, stock_move_id null, description, photo, created_by |
| `harvests` | id, cycle_id, date, qty, unit, grade, item_id (barang `hasil_panen`), destination (`dapur`,`jual`,`simpan`,`sedekah`), warehouse_id, market_price (per unit, untuk nilai), sale_price null, party_id null (pembeli), payment (`tunai`,`transfer`,`kredit`) null, stock_move_id, journal_id null, created_by |

Alur otomatis:
- Log dengan `item_id` → `stock_moves keluar_pakai` dari gudang terpilih (hard-stop stok).
- Panen `dapur`/`simpan` → `stock_moves masuk_panen` (nilai 0) ke gudang tujuan.
- Panen `jual` → `stock_moves masuk_panen` + `keluar_jual` + voucher draf penjualan (9.16).
- Panen `sedekah` → `masuk_panen` + `keluar_pakai` (catatan sedekah) untuk laporan.
- Log `kematian` mengurangi populasi kolam.

**Ringkasan siklus (otomatis)**
| Angka | Rumus |
|---|---|
| Biaya input | Σ `total_cost` stok yang dipakai + Σ baris jurnal beban yang ditandai `farm_cycle_id` |
| Total panen | Σ `harvests.qty` |
| HPP per unit | biaya input ÷ total panen |
| Nilai hasil | Σ qty × (`sale_price` ?? `market_price`) |
| Surplus | nilai hasil − biaya input |
| Capaian target | total panen ÷ target_yield |
| Tingkat hidup (kolam) | (tebar − Σ kematian) ÷ tebar |

**Rancangan layar**
```
┌ Kebun & Kolam ─────────────────────────────────────────────── [+ Siklus baru] ┐
│ [Semua] [Lahan] [Kolam]          Panen bulan ini: 84 kg sayur · 120 kg lele    │
│ ┌ L-01 Bedengan Timur ─┐ ┌ L-02 Bedengan Barat ┐ ┌ K-01 Kolam Lele ─────────┐ │
│ │ Cabai rawit           │ │ (kosong)            │ │ Lele · 1.000 ekor         │ │
│ │ Hari 46/90 ████░░ 51% │ │ [Mulai siklus]      │ │ Hari 30/75 ███░░░ 40%     │ │
│ │ PIC Fajar             │ │                     │ │ Hidup 96% · PIC Rizal     │ │
│ │ [+ Catat] [Panen]     │ │                     │ │ [+ Catat] [Panen]         │ │
│ └───────────────────────┘ └─────────────────────┘ └───────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────────┘
┌ Siklus Cabai rawit · L-01 ── Mulai 1 Okt · Est. panen 30 Des · Berjalan ───────┐
│ Biaya input Rp 412.000 · Panen 6 kg · HPP Rp 68.667/kg · Target 40 kg (15%)    │
│ [Log] [Panen] [Biaya] [Foto]                                                   │
│ 14 Nov Pupuk  NPK 2 kg (Gudang Kebun, Rp 24.000)      Fajar  📷                 │
│ 10 Nov Panen  2 kg → Dapur (nilai pasar Rp 60.000)    Fajar                    │
└────────────────────────────────────────────────────────────────────────────────┘
```
Form log di HP: satu layar, maks. 4 field wajib (aktivitas, tanggal default hari ini, jumlah, foto dari kamera `capture`).

**AC 9.11**
- Log yang memakai stok mengurangi gudang; stok kurang → ditolak dengan sisa stok.
- Panen ke `dapur` menambah stok Gudang Dapur bernilai 0 dan tercatat nilai pasarnya.
- Siklus `selesai`/`gagal` terkunci; buka ulang hanya pengurus `farm-update` dan tercatat di log.

### 9.12 Dapur

**Konsep**: dapur adalah pusat biaya terbesar. Petugas piket merencanakan menu, memasak dari stok, dan mencatat porsi. Sistem menghitung **biaya makan per orang per hari** dibanding anggaran, serta kontribusi kebun.

| Tabel | Kolom |
|---|---|
| `kitchen_menus` | id, name, category (`pokok`,`lauk`,`sayur`,`pelengkap`,`minuman`,`buah`), ingredients (json: item_id, qty per porsi), notes, is_active |
| `meal_plans` | id, date, meal (`sarapan`,`makan_siang`,`makan_malam`,`snack`), menu_ids (json), planned_portions, status (`rencana`,`dimasak`), unik (date, meal) |
| `kitchen_logs` | id, meal_plan_id, cooked_at, actual_portions, leftover_portions, cost_total (dihitung), note, photo, created_by |

Alur:
- **Rencana mingguan**: grid 7 hari × waktu makan, isi dari daftar menu, porsi default = penghuni aktif. Tombol **Hitung kebutuhan** → daftar belanja (kebutuhan − stok Gudang Dapur), bisa dicetak/kirim WA.
- **Masak**: form terisi bahan dari resep × porsi (bisa diubah) → simpan = `stock_moves keluar_pakai` per bahan (hard-stop stok) + `cost_total` = Σ nilai rata-rata.
- **Belanja**: daftar belanja → form pembelian (item, qty, harga, toko, foto nota) → `stock_moves masuk_beli` + voucher draf (9.16).

```
┌ Dapur · Senin 16 Nov 2026 ─────────────────────── Piket: Ahmad, Salman ┐
│ Biaya hari ini Rp 96.500 · Rp 13.786/orang (anggaran Rp 15.000) ✓       │
│ Kontribusi kebun hari ini: Rp 18.000 (nilai pasar)                      │
│ Sarapan     Nasi goreng, telur        8 porsi ✓ Dimasak  Rp 28.000      │
│ Makan siang Nasi, bayam, lele goreng  8 porsi ✓ Dimasak  Rp 41.500      │
│ Makan malam Nasi, tempe orek, sambal  8 porsi ○ Rencana  [Masak]        │
│ Stok menipis: Minyak goreng 0,5 L · Beras 3 kg        [Buat belanja]    │
└─────────────────────────────────────────────────────────────────────────┘
```

| Angka | Rumus |
|---|---|
| Biaya per masak | Σ (qty × avg_cost) bahan keluar |
| Biaya per porsi | biaya per masak ÷ porsi aktual |
| Biaya per orang per hari | Σ biaya hari itu ÷ jumlah penghuni aktif hari itu |
| Sisa makanan | porsi sisa ÷ porsi aktual |
| Kontribusi kebun | Σ (qty bahan dari panen × market_price) — ditampilkan terpisah karena nilai akuntansinya 0 |

Laporan bulanan: tren biaya/orang/hari vs anggaran, menu terbanyak, bahan termahal, sisa makanan, kontribusi kebun. Pemakaian bahan dijurnal **sekali per bulan** saat tutup periode (9.16), bukan per masak.

**AC 9.12**: memasak dengan bahan melebihi stok → ditolak dengan nama bahan & kekurangannya; biaya/orang/hari di dashboard sama dengan hitungan manual data uji.

### 9.13 Akuntansi (Double-Entry, ISAK 35)

Mengikuti Whitepaper Pilar SIA: **tidak ada jurnal tanpa voucher, tidak ada voucher tanpa dokumen**, persetujuan berjenjang, dan jejak audit permanen.

#### 9.13.1 Master
| Tabel | Kolom |
|---|---|
| `coa_accounts` | id, code (`1-1101`), name, type (`aset`,`liabilitas`,`aset_neto`,`pendapatan`,`beban`), normal_balance (`debit`,`kredit`), parent_id, is_header (tidak bisa diposting), is_cash, is_bank, is_receivable, is_payable, is_contra, restriction (`tanpa_pembatasan`,`dengan_pembatasan`) null, cash_flow_group (`operasi`,`investasi`,`pendanaan`) null, statement_group (pos laporan), tax_mapping (json, v2), is_active, sort_order |
| `parties` (pihak) | id, code, name, type (`donatur`,`pemasok`,`pelanggan`,`anggota`,`lembaga`,`lainnya`), phone, email, address, npwp null, member_id null, is_active |
| `cost_centers` | id, code, name, type (`program`,`operasional`,`unit_bisnis`,`umum`), business_unit_id null, is_active. Seed: Yayasan/Umum, Pendidikan, Asrama, Dapur, Kebun, Kolam, Kendaraan, + per unit bisnis |
| `fiscal_periods` | id, year, month, status (`open`,`closed`), closed_by, closed_at, reopened_by, reopen_reason |
| `budgets` | id, year, coa_account_id, cost_center_id, amount (tahunan), monthly (json 12 nilai, opsional) |

- COA bisa diubah (tambah akun, nonaktifkan). Akun yang sudah punya jurnal tidak bisa dihapus atau diubah tipenya.
- Seed COA ISAK 35 di Bagian 13.3.

#### 9.13.2 Voucher & jurnal
`journals` (header voucher): id, number null, voucher_type, date, fiscal_period_id, description, party_id null, cost_center_id (default baris), reference_no (no. nota/faktur/invoice), attachments (json), status (`draft`,`diajukan`,`ditolak`,`terposting`,`dibalik`), is_system, source_type/source_id, reversal_of_id null, created_by, submitted_at, approved_by, posted_at, reject_reason.

`journal_lines`: id, journal_id, coa_account_id, debit, credit, cost_center_id, party_id null, farm_cycle_id null, asset_id null, memo.

**8 jenis voucher (Whitepaper 2.2.1) dan aturan validasinya (hard-stop):**
| Kode | Nama | Aturan |
|---|---|---|
| BPV | Bank Payment Voucher | Ada baris **kredit** ke akun `is_bank`; tidak ada baris kas |
| BRV | Bank Receipt Voucher | Ada baris **debit** ke akun `is_bank`; tidak ada baris kas |
| CPV | Cash Payment Voucher | Ada baris **kredit** ke akun `is_cash` (termasuk setor kas ke bank) |
| CRV | Cash Receipt Voucher | Ada baris **debit** ke akun `is_cash` |
| RJV | Receivable Journal Voucher | Ada baris akun `is_receivable` dengan `party_id`; tanpa kas/bank |
| PJV | Payable Journal Voucher | Ada baris akun `is_payable` dengan `party_id`; tanpa kas/bank |
| MJV | Memo Journal Voucher | Tanpa kas/bank (alokasi biaya, reklasifikasi aset neto, saldo awal) |
| AJV | Adjustment Journal Voucher | Tanpa kas/bank; akrual, penyusutan, koreksi, pemakaian persediaan, penutupan tahun; hanya `journal-approve` atau sistem |

Aturan umum (hard-stop, di service `JournalPoster`):
1. Minimal 2 baris; setiap baris hanya debit **atau** kredit, > 0.
2. Σ debit = Σ kredit (dibandingkan dalam sen).
3. Akun header atau nonaktif ditolak.
4. **Lampiran wajib ≥ 1** untuk voucher non-sistem saat diajukan (jpg/png/pdf, maks. 5 MB per file, disk `local`).
5. Periode `closed` → tidak bisa diajukan/diposting.
6. Pengaju ≠ penyetuju.
7. Pengeluaran kas/bank yang membuat saldo akun kas/bank minus pada tanggal itu → ditolak.
8. Baris akun piutang/utang wajib `party_id`.
9. Baris akun pendapatan/beban wajib `cost_center_id`.
10. Voucher `terposting` tidak bisa diubah/dihapus. Koreksi = **Balik** (membuat voucher baru tipe sama dengan debit/kredit ditukar, `reversal_of_id`, status asal `dibalik`) lalu buat voucher benar.
11. Nomor `{TYPE}/{YYYY}/{MM}/{0001}` diberikan saat **posting** (urut per tipe per bulan, lock), sehingga tidak ada nomor loncat.
12. Voucher sistem (`is_system`) tetap masuk antrean persetujuan kecuali saldo awal dan penutupan tahun yang diposting oleh pemegang `period-close`.

Alur status: `draft` → (ajukan, `journal-submit`) `diajukan` → (`journal-approve`) `terposting` | `ditolak` (alasan wajib, kembali bisa diedit & diajukan ulang).

**Form voucher**
```
┌ Voucher baru ───────────────────────────────────────────────────────────────┐
│ Jenis [CPV ▾]  Tanggal [16/11/2026]  Pihak [Toko Berkah ▾]  Ref [Nota 0921] │
│ Uraian [Belanja bahan dapur minggu 3]                                        │
│ Akun                         Pusat biaya   Memo            Debit     Kredit  │
│ 1-1401 Persediaan Bahan Pangan  Dapur      beras, minyak   412.000           │
│ 1-1102 Kas Asrama               –                                   412.000  │
│ [+ baris]                                       Total    412.000   412.000 ✓ │
│ Lampiran: [nota-0921.jpg ✕] [+ unggah]                                       │
│ [Simpan draf] [Ajukan]                                                       │
└──────────────────────────────────────────────────────────────────────────────┘
```
Template cepat (pengisian baris otomatis, tetap bisa diedit): Belanja tunai, Bayar tagihan (listrik/air), Terima donasi, Setor kas ke bank, Kasbon, Pelunasan utang, Penjualan panen.

Antrean persetujuan (Bendahara): daftar voucher `diajukan` dengan pratinjau lampiran berdampingan, tombol **Posting** / **Tolak**, pilih banyak untuk posting massal.

Cetak voucher PDF (kop, nomor, baris, dibuat/diajukan/disetujui + tanggal) untuk arsip fisik (Whitepaper: filing & bundling).

#### 9.13.3 Saldo awal
Wizard saat pertama kali: tanggal mulai pembukuan (`accounting_start_date`) → daftar semua akun non-header → isi saldo → sistem membuat **MJV Saldo Awal** (selisih wajib 0, tidak boleh dipaksa ke akun penampung). Piutang/utang saldo awal per pihak. Aset tetap saldo awal dari modul Aset.

#### 9.13.4 Tutup periode & tutup buku
- **Tutup bulan** (`period-close`) dengan ceklis:
  1. Tidak ada voucher `draft`/`diajukan` bertanggal di periode itu (peringatan, bisa dilanjutkan dengan konfirmasi; voucher tersebut tidak bisa diposting ke periode tertutup).
  2. Jurnal otomatis bulanan dibuat & diposting: pemakaian persediaan dapur & kebun, penyusutan aset, penyesuaian opname (9.16).
  3. Rekonsiliasi kas: input saldo fisik kas & saldo rekening koran → selisih harus 0 atau dijelaskan dengan voucher.
  4. Neraca saldo seimbang (selalu, ditampilkan sebagai konfirmasi).
- Periode tertutup dibuka ulang hanya oleh Super Admin dengan alasan (activity log).
- **Tutup buku tahunan**: AJV sistem memindahkan saldo pendapatan & beban ke Aset Neto (tanpa/dengan pembatasan sesuai akun), periode Januari tahun berikut dibuka.

#### 9.13.5 Anggaran
Anggaran tahunan per akun beban/pendapatan per pusat biaya (bisa diisi per bulan). Saat voucher beban diajukan dan realisasi + voucher > anggaran → **peringatan** (soft-stop) yang wajib dikonfirmasi dan tercatat di voucher; laporan realisasi vs anggaran.

**AC 9.13**
- Voucher tidak seimbang → 422, menampilkan selisih.
- Mengajukan tanpa lampiran → 422.
- Pengaju = penyetuju → 403 (termasuk Super Admin).
- Posting ke periode tertutup → 422.
- BPV tanpa baris kredit bank → 422 (tes untuk setiap 8 tipe).
- Voucher terposting diubah/dihapus → 403; **Balik** menghasilkan voucher pembalik dan saldo kembali.
- Nomor voucher urut tanpa loncatan walau ada voucher yang ditolak/dihapus saat draf.
- Saldo kas minus → 422.

### 9.14 Laporan Keuangan
Semua laporan: filter periode/tanggal, pusat biaya (bila relevan), perbandingan periode sebelumnya, export **PDF** (kop + penandatangan dari Setting) dan **CSV**, angka dapat diklik untuk drill-down ke buku besar → voucher → lampiran (Whitepaper: "setiap rupiah dapat dilacak").

| Laporan | Isi |
|---|---|
| Jurnal Umum | Daftar voucher terposting + baris |
| Buku Besar | Per akun: saldo awal, mutasi, saldo berjalan |
| Buku Pembantu Piutang/Utang | Per pihak: saldo, umur (0–30, 31–60, 61–90, >90 hari) |
| Buku Kas & Bank | Per akun kas/bank + rekonsiliasi |
| Neraca Saldo | Per tanggal; total debit = kredit |
| Laporan Posisi Keuangan | Aset (lancar, tidak lancar), Liabilitas, Aset Neto (tanpa/dengan pembatasan) — ISAK 35 |
| Laporan Penghasilan Komprehensif | Pendapatan & beban dengan kolom tanpa/dengan pembatasan, aset neto dibebaskan dari pembatasan, surplus/defisit |
| Laporan Perubahan Aset Neto | Saldo awal, surplus/defisit, reklasifikasi, saldo akhir per kelompok pembatasan |
| Laporan Arus Kas (metode langsung) | Untuk setiap voucher yang menyentuh kas/bank: perubahan kas = −Σ baris non-kas, dikelompokkan menurut `cash_flow_group` akun lawan |
| Laporan per Pusat Biaya | Pendapatan & beban per pusat biaya, termasuk unit bisnis |
| Realisasi vs Anggaran | Per akun & pusat biaya, % serapan |
| Rasio | Rasio beban program terhadap total beban, biaya per siswa per bulan, rasio lancar, sumber pendapatan (%) |
| **Laporan Transparansi Publik** | Ringkasan: donasi masuk, penggunaan per program, jumlah penerima manfaat (siswa aktif), tanpa nama donatur. Tombol **Terbitkan ke situs** → PDF masuk Dokumen kategori `Laporan Transparansi` |

**AC 9.14**
- Neraca saldo seimbang untuk data uji acak (test properti sederhana: N voucher acak seimbang → total seimbang).
- Posisi Keuangan: Aset = Liabilitas + Aset Neto (termasuk surplus berjalan) pada setiap tanggal.
- Arus kas: saldo awal kas + arus bersih = saldo akhir kas.
- Angka laporan sama dengan hitungan manual pada data seed uji.

### 9.15 Donasi
- `/donasi`: ajakan donasi, rekening & QRIS dari Setting, ringkasan transparansi tahun berjalan, formulir konfirmasi: nama (atau centang "Hamba Allah"), WA, email, nominal, tanggal transfer, rekening tujuan, peruntukan (`umum` → tanpa pembatasan; `pendidikan`,`pangan`,`pembangunan`,`wakaf` → dengan pembatasan **[TBD Q10]**), bukti transfer, pesan/doa. Rate limit + honeypot.
- `donations`: id, party_id null, donor_name, is_anonymous, phone, email, amount, transferred_at, coa_bank_account_id, purpose, proof, message, status (`menunggu`,`terverifikasi`,`ditolak`), verified_by, journal_id null, created_at.
- Verifikasi (`donation-verify`): cocokkan dengan mutasi bank → buat/kaitkan pihak donatur → voucher **BRV** (atau CRV untuk tunai) diajukan otomatis: Dr Bank, Cr Pendapatan Donasi (tanpa/dengan pembatasan sesuai peruntukan), lampiran = bukti transfer → masuk antrean persetujuan Bendahara (penyetuju ≠ pemverifikasi).
- Setelah terposting: email/tautan WA terima kasih + bukti terima donasi PDF bernomor.
- Donasi tunai/barang dicatat langsung dari dashboard (barang → `stock_moves masuk_donasi` dengan nilai wajar + MJV).
- Riwayat donasi per donatur (pihak) dan rekap per peruntukan; nama donatur tidak pernah tampil publik.

**AC**: donasi `menunggu` tidak memengaruhi laporan; verifikasi membuat tepat satu voucher; menolak donasi wajib alasan.

### 9.16 Jurnal Otomatis dari Operasional
Semua jurnal otomatis dibuat sebagai voucher `is_system` berstatus `diajukan` (masuk antrean Bendahara) dengan `source` menunjuk transaksi asal, sehingga Bendahara tetap memeriksa.

| Kejadian | Voucher | Jurnal |
|---|---|---|
| Pembelian stok tunai/transfer | CPV/BPV | Dr Persediaan (akun item) · Cr Kas/Bank |
| Pembelian stok kredit | PJV | Dr Persediaan · Cr Utang Usaha (pihak) |
| Pemakaian persediaan dapur (rekap bulanan) | AJV | Dr Beban Konsumsi (Dapur) · Cr Persediaan Bahan Pangan |
| Pemakaian sarana produksi kebun/kolam (rekap bulanan) | AJV | Dr Beban Produksi Kebun & Kolam (pusat biaya Kebun/Kolam, `farm_cycle_id`) · Cr Persediaan Sarana Produksi |
| Barang rusak / selisih opname | AJV | Dr Beban Lain-lain (atau Cr pendapatan lain-lain bila lebih) · Cr/Dr Persediaan |
| Panen dijual tunai/transfer | CRV/BRV | Dr Kas/Bank · Cr Penjualan Hasil Kebun & Kolam |
| Panen dijual kredit | RJV | Dr Piutang (pihak) · Cr Penjualan Hasil Kebun & Kolam |
| Donasi uang | BRV/CRV | Dr Bank/Kas · Cr Donasi (tanpa/dengan pembatasan) |
| Donasi barang | MJV | Dr Persediaan/Aset Tetap · Cr Donasi Natura |
| Pembelian aset | BPV/CPV/PJV | Dr Aset Tetap (kategori) · Cr Kas/Bank/Utang |
| Penyusutan (bulanan) | AJV | Dr Beban Penyusutan · Cr Akumulasi Penyusutan |
| BBM / servis / pajak kendaraan | CPV/BPV | Dr Beban Kendaraan · Cr Kas/Bank |
| Biaya kesehatan siswa | CPV/BPV | Dr Beban Kesehatan Siswa · Cr Kas/Bank |
| Penggunaan dana terikat sesuai peruntukan | MJV | Dr Aset Neto Dengan Pembatasan · Cr Aset Neto Dibebaskan dari Pembatasan |

Pemetaan akun default disimpan di `config/mantaaba.php` (`posting_rules`) dan bisa diubah Super Admin di Setting Akuntansi (pilih akun per aturan).

**AC**: membatalkan transaksi asal (mis. pembelian stok) yang vouchernya sudah terposting → ditolak; harus lewat voucher pembalik. Rekap bulanan tidak dobel saat tutup periode dijalankan ulang (idempoten per periode).

### 9.17 Aset & Kendaraan

| Tabel | Kolom |
|---|---|
| `assets` | id, code (`AST-{kat}-{urut}`), name, category (`tanah`,`bangunan`,`kendaraan`,`elektronik`,`peralatan_dapur`,`peralatan_kebun`,`mebel`,`lainnya`), location, condition (`baik`,`rusak_ringan`,`rusak_berat`,`hilang`,`dilepas`), pic_user_id, acquired_at, acquisition_value, source (`beli`,`hibah`,`wakaf`), useful_life_months (default per kategori; tanah tidak disusutkan), residual_value, depreciation_start, asset_account_id, accumulated_account_id, expense_account_id, photo, documents (json), notes, disposed_at, disposal_note |
| `asset_vehicles` | asset_id (1–1), plate_number, brand_type, year, color, stnk_due_at, tax_due_at, kir_due_at null, last_odometer, service_interval_km, service_interval_days, last_service_at, last_service_km |
| `asset_usages` | id, asset_id, user_id, purpose, destination, started_at, ended_at, odometer_start, odometer_end, fuel_cost null, fuel_liters null, note, status (`dipakai`,`kembali`), journal_id null |
| `asset_maintenances` | id, asset_id, date, type (`servis`,`perbaikan`,`pajak`,`stnk`,`lainnya`), description, cost, vendor_party_id null, odometer, next_due_at null, attachment, journal_id null |

Fitur:
- Register aset + foto + dokumen (BPKB/STNK disimpan privat) + **label QR** (PDF stiker 4×6 cm, banyak sekaligus) → pindai membuka detail aset (perlu login).
- **Pemakaian kendaraan**: mulai (km awal, tujuan) → selesai (km akhir, BBM, foto struk) → BBM membuat voucher draf.
- **Pengingat** (scheduler harian 07:00): pajak/STNK/KIR H-30 & H-7, servis berdasarkan km atau hari → notifikasi dashboard + email ke PIC & Koordinator.
- **Penyusutan garis lurus** bulanan: (nilai perolehan − residu) ÷ umur manfaat; jadwal penyusutan per aset; dibuat sebagai satu AJV rekap saat tutup periode.
- Pelepasan aset (jual/hapus) → jurnal pelepasan (keuntungan/kerugian) via voucher manual dengan template.
- Ringkasan kendaraan: total km, BBM (Rp & liter), km/liter, biaya per km, biaya perawatan tahun berjalan.

```
┌ AST-KND-001 · Toyota Avanza · B 1234 XYZ ─────────── Kondisi: Baik · PIC: Pak Ujang ┐
│ Odometer 84.210 km · Servis berikutnya 85.000 km (790 km lagi) ⚠                    │
│ Pajak 12 Jan 2027 (64 hari) · STNK 12 Jan 2030                                      │
│ Nilai buku Rp 142.500.000 (perolehan 180 jt, akum. penyusutan 37,5 jt)              │
│ [Mulai pakai] [Catat servis] [Label QR]                                             │
│ Pemakaian terakhir: 14 Nov · Ahmad · Pasar Cibeber · 32 km · BBM Rp 100.000         │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

**AC 9.17**: memakai kendaraan yang sedang `dipakai` → ditolak; `odometer_end < odometer_start` → ditolak; aset `tanah` tidak dihitung penyusutan; total akumulasi penyusutan tidak melebihi (nilai perolehan − residu).

### 9.18 Pengguna & Hak Akses
- Satu halaman **Pengguna** (menggantikan `AdministratorController`, `GeneralController`, bagian akun di `MemberController`): cari, filter role/status, aktif/nonaktif (`users.is_active`), kirim tautan atur ulang password (email/WA), ubah role.
- Layar **Hak Akses** per pengguna: matriks centang modul × aksi (5.3), tombol paket (5.4), tampilan "hak efektif" (role + langsung). Hanya Super Admin.
- Batas Super Admin: 1–3 orang; Super Admin terakhir tidak bisa dinonaktifkan/diturunkan.
- Login: throttle 5×/menit per email+IP (sudah ada), "ingat saya", pesan nonaktif tanpa merek AKP2I.

**AC**: pengurus tanpa `journal-view` membuka URL voucher → 403; non-SA mengubah role ke `super_admin` lewat request langsung → 403; non-SA membuka layar Hak Akses → 403.

### 9.19 Dashboard & Pengumuman
| Peran | Isi |
|---|---|
| Pengurus (sesuai permission) | Kartu: anggota aktif per kategori, pendaftar angkatan berjalan per tahap, progres skema angkatan, tugas belum dinilai, piket terlewat hari ini, panen bulan ini, biaya makan/orang/hari vs anggaran, stok menipis, saldo per akun kas/bank, voucher menunggu persetujuan, donasi menunggu verifikasi, aset jatuh tempo. Grafik (ApexCharts): biaya dapur 30 hari, penerimaan vs beban 12 bulan, realisasi anggaran |
| Anggota | Piket hari ini, pengumuman, progres belajar & lencana, materi berikutnya, tugas tenggat, agenda terdekat, sertifikat, tugas operasional (bila punya permission: mis. voucher draf ditolak) |

`announcements`: id, title, body, audience (`semua`,`pengurus`,`siswa`,`angkatan`,`alumni`), batch_id null, is_pinned, published_at, expires_at, created_by. Tampil di dashboard sasaran; opsi kirim email.

### 9.20 Audit Log & Notifikasi
- `LogsActivity` dipasang di: members, applicants, coaching_notes, skema_results, certificates, journals (setiap perubahan status), coa_accounts, fiscal_periods, donations, stock_moves (`opname`, `keluar_rusak`), stock_opnames, assets, farm_cycles, users (role & permission), settings.
- Activity log tidak bisa dihapus dari aplikasi (tidak ada route delete). Halaman log: filter subjek/user/tanggal/aksi.
- Notifikasi (database + email via queue; ikon lonceng di navbar dashboard):

| Kejadian | Penerima |
|---|---|
| Pendaftar baru | Pemegang `admission-view` |
| Status pendaftaran berubah | Pendaftar (email bila ada) + tautan WA untuk pengurus |
| Tugas dikumpulkan / dinilai | Mentor / siswa |
| Sertifikat terbit | Siswa |
| Voucher diajukan / ditolak / terposting | Pemegang `journal-approve` / pengaju |
| Donasi masuk | Pemegang `donation-verify` |
| Stok menipis (ringkasan harian) | PIC gudang |
| Pengingat aset | PIC aset & koordinator |
| Pelanggaran berat / akumulasi | Ketua |
| Pesan kontak masuk | Email Setting |

---

## 10. Kebutuhan Non-Fungsional

| Area | Kebutuhan |
|---|---|
| Keamanan | Policy di setiap aksi; Form Request; upload divalidasi mime & ukuran; file privat di disk `local` lewat route ber-policy; CSRF; rate limit login/pendaftaran/kontak/donasi/cek status; header keamanan dasar; tanpa PII di log; password reset lewat token |
| Privasi (UU PDP) | Data anak & keluarga (NIK, KK, status yatim, ekonomi, SKTM, kesehatan) hanya untuk `member-private`/`health-view`. Situs hanya menampilkan anggota `show_on_site`. Halaman Kebijakan Privasi. Retensi data pendaftar ditolak 12 bulan |
| Integritas data | Semua transaksi stok, voucher, nomor, kuota memakai `DB::transaction` + `lockForUpdate`; hard-stop di service layer; uang dalam `decimal`, dibandingkan dalam sen |
| Jejak audit | Voucher terposting immutable; koreksi hanya lewat pembalik; activity log tanpa delete |
| Performa | DataTables server-side, eager loading, index pada kolom filter (date, status, foreign key), laporan keuangan dengan agregasi SQL (bukan loop PHP), cache halaman publik 10 menit |
| Mobile | Dashboard anggota, piket, log kebun, dapur, pemakaian kendaraan, check-in, persetujuan voucher nyaman di 360px |
| Backup | Backup DB harian (cron `mysqldump` di hosting) + `storage/app` mingguan; prosedur restore di `docs/DEPLOY.md` |
| Kualitas | Feature test untuk setiap AC; unit test untuk perhitungan (nilai 4D, HPP, rata-rata tertimbang, laporan keuangan); `php artisan test` hijau sebelum merge |
| Aksesibilitas | Kontras ≥ 4.5:1, label form, fokus terlihat, alt text gambar (field wajib di galeri/slider) |

---

## 11. Model Data (ringkas)

```
users ─1:1─ members ──< member_journeys >── business_units ── cost_centers
  │           ├──< coaching_notes, health_records
  │           ├──< skema_enrollments ─1:1─ skema_results ─1:1─ certificates
  │           ├──> rooms              ├──< duties >── duty_areas
  │           └──> batches ──< applicants (─1:1─ members)
  ├──< lesson_progress >── lessons >── modules >── skemas
  └── roles / permissions (spatie)

events ──< participants (user_id | guest)

warehouses ──< stock_moves >── items          stock_opnames
farm_plots ──< farm_cycles ──< farm_logs, harvests ──> stock_moves
kitchen_menus · meal_plans ──< kitchen_logs ──> stock_moves

coa_accounts (tree) ──< journal_lines >── journals ──> fiscal_periods
journal_lines >── cost_centers, parties, farm_cycles, assets
journals ── source (morph: donations, stock_moves, harvests, asset_usages, asset_maintenances, health_records)
budgets >── coa_accounts, cost_centers
donations ──> parties, journals

assets ─1:1─ asset_vehicles ; assets ──< asset_usages, asset_maintenances

pages(blocks) · nav_menus · sliders · galleries ──< gallery_photos · documents · blogs >── categories/tags
contact_messages · announcements · settings · activity_logs (morph) · notifications · jobs
```

| Kelompok | Tabel |
|---|---|
| Dipertahankan | users, password_reset_tokens, permission tables, settings, pages, sliders, galleries, gallery_photos, documents, blogs, categories, tags, blog_tag, contact_messages, events, participants, certificates, members, activity_logs, jobs, failed_jobs |
| Baru | nav_menus, business_units, announcements, batches, applicants, member_journeys, coaching_notes, health_records, skemas, modules, lessons, skema_enrollments, lesson_progress, skema_results, rooms, duty_areas, duties, warehouses, items, stock_moves, stock_opnames, farm_plots, farm_cycles, farm_logs, harvests, kitchen_menus, meal_plans, kitchen_logs, coa_accounts, parties, cost_centers, fiscal_periods, budgets, journals, journal_lines, donations, assets, asset_vehicles, asset_usages, asset_maintenances, notifications |
| Dihapus | organizations, member_dues, board_periods, board_members, divisions, positions, threads, comments, forum_categories, reports, research, personal_access_tokens |

---

## 12. Roadmap & Milestone

Aturan: satu branch per milestone, checklist `docs/milestones/M{n}.md` (`[x]`/`[~]`/`[ ]` + catatan commit/test/keputusan) diperbarui tiap langkah, satu commit per langkah, jeda review di akhir milestone. Milestone selesai bila semua AC terkait punya test lulus, `php artisan test` hijau, dan dicek manual di 360px & desktop.

| M | Nama | Isi utama | Fokus test |
|---|---|---|---|
| **M0** | Fondasi & pembersihan | Bagian 4 (repo, DB, baseline test); hapus modul 3.3; squash migrasi; rebranding 3.5; `config/mantaaba.php`; 3 role + permission 5.3 + paket 5.4 + layar Hak Akses; sidebar dari config; `/register` & API mati; middleware `auth`; perbaikan T2–T7; logo turunan | Auth, otorisasi (403), grep rebranding kosong, smoke dashboard |
| **M1** | CMS & situs publik | Setting diperluas + token warna; Menu Manager; halaman berbasis blok + pratinjau; unit bisnis; berita/galeri/dokumen/slider/kontak disesuaikan; tampilan Bagian 7; semua halaman Bagian 8 (modul belum ada → empty state); auth pages bergaya situs; SEO | AC 9.1, halaman publik 200, cache flush |
| **M2** | Penerimaan & anggota | Angkatan; formulir wizard + cek status; seleksi & terima → akun; data anggota baru; perjalanan; pakta; kesehatan; catatan pembinaan; profil anggota; pengumuman; import/export | AC 9.2–9.5, konkurensi kuota, privasi |
| **M3** | Pembelajaran & sertifikat | Skema/modul/materi + seed 5 skema; progres & tugas; buka skema per angkatan; penilaian 4D; sertifikat per skema (T1) + kelulusan inkubasi; verifikasi publik; `/kurikulum` | AC 9.6–9.7, unit test nilai |
| **M4** | Asrama, piket & agenda | Kamar & penghuni; area & jadwal piket + generator + job terlewat; agenda & kehadiran disederhanakan + check-in; laporan Sedulur Selawase | AC 9.8–9.9 |
| **M5a** | Akuntansi inti | COA (seed ISAK 35), pihak, pusat biaya, periode; voucher 8 tipe + aturan hard-stop + lampiran + antrean persetujuan + balik + cetak; saldo awal; buku besar; neraca saldo | AC 9.13 (setiap aturan), test properti seimbang |
| **M5b** | Laporan, donasi & anggaran | Semua laporan 9.14 + PDF/CSV + drill-down; tutup bulan & tutup buku; anggaran; donasi publik & verifikasi; laporan transparansi ke Dokumen | AC 9.14–9.15, persamaan akuntansi |
| **M6** | Stok, kebun & dapur | Gudang, barang, mutasi, rata-rata tertimbang, opname; kebun/kolam; dapur; jurnal otomatis 9.16 untuk stok/panen/dapur | AC 9.10–9.12, 9.16, unit test HPP & rata-rata |
| **M7** | Aset & kendaraan | Register + QR label; kendaraan; pemakaian; perawatan; pengingat; penyusutan + AJV bulanan; pelepasan | AC 9.17 |
| **M8** | Dashboard & go-live | Dashboard per peran; notifikasi lengkap; `docs/DEPLOY.md` (cPanel, cron, queue, SMTP, backup); konten awal & alamat dari website lama; data anggota & saldo awal riil; UAT bersama pengurus; perbaikan | Smoke semua route, UAT checklist dari AC |

Urutan: M1 lebih dulu agar website lama segera tergantikan; M5a/M5b sebelum M6/M7 karena stok, panen, dan aset membuat voucher.

---

## 13. Seed Awal

### 13.1 Sistem
- Role, permission (5.3), paket (5.4).
- 1 Super Admin dari `MANTAABA_ADMIN_EMAIL`, password acak dicetak sekali di console (bukan `password`).
- Setting identitas (nama lembaga, yayasan, logo `logo-mantaaba.jpeg`, warna 7.1, bobot penilaian, nilai lulus).

### 13.2 Konten & akademik
- Halaman: `home`, `tentang`, `program`, `kebijakan-privasi` (sistem), berisi teks dari Grand Design (Pendahuluan, Landasan Filosofis, Roadmap, Profil Lulusan, Penilaian 4 Dimensi) yang disusun sebagai blok.
- Menu header: Beranda · Tentang · Program · Kurikulum · Ekosistem · Berita · Agenda · Galeri · Donasi · Kontak; tombol "Daftar" ke `/pendaftaran`.
- 5 skema + modul (Grand Design 3) + `character_matrix` (Grand Design 5):
  - **FND** (Bulan 1): Komunikasi Bisnis (mendengar empati, membaca, memahami, menganalisis, menyampaikan lisan & tulisan), Matematika Bisnis (operasi dasar, persentase, persamaan, HPP & margin).
  - **SIM** (Bulan 2–3): Visi-Misi, Struktur Organisasi, Tupoksi, Alur Bisnis, SOP, Workflow, Tertib Administrasi & Klasifikasi Dokumen, Legalitas.
  - **SIA** (Bulan 4): Chart of Accounts, Dokumen Transaksi & 8 Voucher, Jurnal & Buku Besar, Neraca Saldo & Penyesuaian, Laporan Keuangan & CALK.
  - **SIP** (Bulan 5): Mapping Akun ke SPT, Rekonsiliasi Fiskal, Ekualisasi PPh Badan & PPN, Uji Arus Uang/Piutang/Barang.
  - **IT** (Bulan 6): ERP & Sentralisasi Data, AI & Prompt Engineering.
- Area piket, kategori dokumen, kategori berita (Kabar Asrama, Kebun & Kolam, Kajian, Program, Pengumuman).

### 13.3 COA (ISAK 35, dapat diubah)
```
1 ASET
  1-1 Aset Lancar
    1-1100 Kas                       1-1101 Kas Yayasan · 1-1102 Kas Kecil Asrama/Dapur
    1-1200 Bank                      1-1201 Bank Operasional · 1-1202 Bank Donasi
    1-1300 Piutang                   1-1301 Piutang Usaha · 1-1302 Piutang Lain-lain/Kasbon
    1-1400 Persediaan                1-1401 Bahan Pangan · 1-1402 Sarana Produksi Kebun & Kolam · 1-1403 Hasil Panen · 1-1404 Habis Pakai
    1-1500 Uang Muka & Dibayar Dimuka
  1-2 Aset Tidak Lancar
    1-2100 Tanah · 1-2200 Bangunan · 1-2300 Kendaraan · 1-2400 Peralatan & Inventaris · 1-2500 Elektronik
    1-2900 Akumulasi Penyusutan (kontra)  1-2902 Bangunan · 1-2903 Kendaraan · 1-2904 Peralatan · 1-2905 Elektronik
2 LIABILITAS
  2-1100 Utang Usaha · 2-1200 Utang Lain-lain · 2-1300 Pendapatan Diterima Dimuka · 2-1400 Utang Pajak
3 ASET NETO
  3-1000 Aset Neto Tanpa Pembatasan   3-1100 Saldo Aset Neto · 3-1200 Surplus/Defisit Tahun Berjalan
  3-2000 Aset Neto Dengan Pembatasan  3-2100 Dana Pendidikan · 3-2200 Dana Pangan · 3-2300 Dana Pembangunan · 3-2400 Wakaf
4 PENDAPATAN
  4-1100 Donasi Tanpa Pembatasan · 4-1200 Donasi Dengan Pembatasan · 4-1300 Donasi Natura · 4-1400 Hibah & Wakaf
  4-2100 Penjualan Hasil Kebun & Kolam · 4-2200 Kontribusi Unit Bisnis · 4-3100 Pendapatan Lain (bagi hasil, dll.)
  4-9000 Aset Neto Dibebaskan dari Pembatasan
5 BEBAN
  5-1 Beban Program   5-1100 Pendidikan & Pelatihan · 5-1200 Konsumsi (Dapur) · 5-1300 Asrama (listrik, air, kebersihan)
                      5-1400 Produksi Kebun & Kolam · 5-1500 Kesehatan Siswa · 5-1600 Kegiatan Keagamaan & Sedulur Selawase
  5-2 Beban Operasional 5-2100 Kendaraan (BBM, servis, pajak) · 5-2200 Pemeliharaan Aset · 5-2300 Penyusutan
  5-3 Beban Umum      5-3100 Administrasi & ATK · 5-3200 Komunikasi & Internet · 5-3300 Beban Bank · 5-3900 Lain-lain
```
`cash_flow_group`: aset tidak lancar = investasi; aset neto dengan pembatasan jangka panjang (pembangunan, wakaf) & utang jangka panjang = pendanaan; lainnya = operasi.

### 13.4 Operasional
Gudang (Dapur, Kebun & Kolam, Umum), kategori barang & satuan, pusat biaya (13 baris termasuk unit bisnis yang di-seed), aturan posting default 9.16.

### 13.5 Demo (dev/presentasi saja)
`DemoSeeder` terpisah: 2 angkatan, 12 siswa, pendaftar di semua tahap, nilai & sertifikat, 3 siklus kebun/kolam, 1 bulan dapur, 40 voucher terposting, donasi, 1 mobil + motor. **Dilarang dijalankan di produksi** (dicek `app()->isProduction()`).

---

## 14. Definition of Done (per fitur)
- [ ] Migrasi + model + relasi + factory
- [ ] Policy & permission sesuai 5.3; data anggota di-scope ke pemiliknya; data privat hanya untuk yang berhak
- [ ] Form Request; upload aman; file privat lewat route ber-policy
- [ ] Hard-stop di service + test (termasuk 403/422)
- [ ] View dashboard pola `x-dashboard.page`/`datatable`/`field`; view publik `x-site.*`
- [ ] Feature test untuk setiap AC; unit test untuk setiap rumus
- [ ] Tampil benar di 360px & desktop, tanpa error console
- [ ] Teks Bahasa Indonesia; tanggal & Rupiah format Indonesia
- [ ] Sidebar (`config/mantaaba.php`) & seeder permission diperbarui
- [ ] Activity log terpasang bila modul tercantum di 9.20
- [ ] Checklist milestone diperbarui & commit

---

## 15. Pertanyaan Terbuka [TBD]

| # | Pertanyaan | Dampak | Default bila belum dijawab |
|---|---|---|---|
| Q1 | Alamat lengkap, titik peta, telepon/WA, email, media sosial, foto, konten website lama | M1, M8 | Placeholder bertanda jelas; diisi dari CMS di M8 |
| Q2 | File logo transparan (PNG/SVG) & versi sederhana untuk favicon | 7.4 | Diturunkan dari `logo-mantaaba.jpeg` |
| Q3 | Relawan/mitra/donatur perlu akun login? | 9.4, 9.15 | Cukup tercatat; akun opsional dibuat pengurus |
| Q4 | Format nomor sertifikat & penandatangan resmi | 9.7 | Format 9.7; penandatangan diisi per skema |
| Q5 | Batas usia pendaftar, nilai lulus, syarat Character minimal, predikat | 9.3, 9.7 | 16–25 (peringatan saja); ≥70 & C≥70; predikat 9.7 |
| Q6 | Makna nama "Man Taaba (من تاب)" & sejarah singkat untuk halaman Tentang | M1 | Diisi pengurus lewat CMS |
| Q7 | Daftar unit bisnis saat ini (nama, bidang, kontak) | 9.1.5, 13.4 | Diisi pengurus; pusat biaya dibuat otomatis |
| Q8 | Masa simpan data pendaftar yang ditolak | 9.3, 10 | 12 bulan |
| Q9 | Hosting & domain (tetap yayasan-mantaaba.com?), email pengirim SMTP | M8 | Shared hosting cPanel, domain sama |
| Q10 | Daftar peruntukan donasi terikat | 9.15, 13.3 | Pendidikan, Pangan, Pembangunan, Wakaf |
| Q11 | Tanggal mulai pembukuan & saldo awal (kas, bank, aset, utang) | 9.13.3 | 1 Januari tahun go-live |
| Q12 | Kebijakan penyusutan (umur manfaat per kategori, nilai minimum dikapitalisasi) | 9.17 | Bangunan 240 bln, kendaraan 96, peralatan/elektronik 48, mebel 48; kapitalisasi ≥ Rp 1.000.000 |
| Q13 | Penandatangan laporan keuangan (nama & jabatan) | 9.14 | Ketua & Bendahara dari Setting |
| Q14 | Anggaran makan per orang per hari & jumlah penghuni | 9.12 | Diisi di Setting |
| Q15 | Apakah laporan keuangan perlu diaudit eksternal (format paket audit)? | 9.14 | Laporan standar ISAK 35 + CALK manual |

---

## 16. Glosarium
| Istilah | Arti |
|---|---|
| Skema | Kelompok kompetensi SKKNI per bulan inkubasi: FND, SIM, SIA, SIP, IT |
| Penilaian 4 Dimensi | Knowledge 20%, Skill 25%, Character 30%, Social Cohesion 25% |
| 'Asabiyah | Solidaritas prososial, "no one left behind" |
| Fastabiqul Khairat | Berlomba dalam kebaikan: inisiatif, agilitas |
| Sedulur Selawase | Ikatan alumni; pertemuan minimal per 3 bulan |
| Voucher | Dokumen dasar jurnal (8 jenis Whitepaper) |
| Hard-stop | Aturan sistem yang mutlak menolak transaksi tidak sah |
| ISAK 35 | Standar penyajian laporan keuangan entitas berorientasi nonlaba |
| Pusat biaya | Pengelompokan pendapatan & beban per unit (Asrama, Dapur, Kebun, unit bisnis, dll.) |
| Siklus | Satu periode tanam/tebar sampai panen pada satu petak/kolam |
