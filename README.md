<p align="center"><img width="200px" src="public/media/logos/logo-mantaaba-512.jpg" alt="Rumah Mantaaba Sigaling"></p>

# Sistem Informasi & Website Rumah Mantaaba Sigaling

Website publik (CMS) dan sistem informasi Rumah Mantaaba Sigaling (Yayasan Mantaaba): penerimaan siswa per angkatan, data anggota, pembelajaran & sertifikat per skema (Fundamental, SIM, SIA, SIP, IT), asrama & piket, kebun, kolam, dapur, stok, aset & kendaraan, serta akuntansi nirlaba (ISAK 35) dan donasi.

- Kebutuhan lengkap: [docs/PRD.md](docs/PRD.md)
- Progres per milestone: [docs/milestones/](docs/milestones/)
- Acuan: [Grand Design](docs/Grand%20Design%20%26%20Konsep%20Pendidikan%20Rumah%20Mantaaba%20Sigaling%20-%20Master%20Document.md), [Whitepaper Framework 4 Pilar](docs/WHITEPAPER%20—%20FRAMEWORK%20TATA%20KELOLA%20MANAJEMEN%20PERUSAHAAN%20MAZHAB%20MANTAABA.md)

## Peran

Tiga role: **Super Admin**, **Pengurus**, **Anggota**. Akses modul diberikan per pengguna lewat layar *Pengguna & Hak Akses* (centang per modul atau paket: Pengurus Inti, Bendahara, Mentor, Koordinator Operasional, Redaksi, Siswa Petugas). Definisi role, modul, paket, dan menu ada di `config/mantaaba.php`.

## Teknologi

Laravel 12 (struktur folder Laravel 8), PHP ≥ 8.2 (ekstensi GD), MySQL 5.7+/8, Vite + Tailwind + Alpine (situs publik), Metronic 8 (dashboard), spatie/laravel-permission, yajra/laravel-datatables, barryvdh/laravel-dompdf, simplesoftwareio/simple-qrcode.

## Instalasi lokal (MAMP)

PHP MAMP: `PHP=/Applications/MAMP/bin/php/php8.2.0/bin/php`; MySQL MAMP di `127.0.0.1:8889` (root/root).

```bash
composer install
npm install && npm run build
cp .env.example .env && $PHP artisan key:generate   # DB_DATABASE=mantaaba, MANTAABA_ADMIN_EMAIL=...
$PHP artisan migrate --seed      # role, setting, halaman awal, super admin + akun & data contoh (non-produksi)
$PHP artisan storage:link
$PHP artisan serve
```

Akun contoh (hanya di luar produksi, password `password`): `superadmin@mail.com`, `pengurus@mail.com`, `anggota@mail.com`.

Test memakai database `mantaaba_test` (lihat `phpunit.xml`): `$PHP artisan test`.

## Deploy produksi

> `UserSeeder` dan data contoh hanya berjalan bila `APP_ENV` bukan `production`.

1. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` (https), `DB_*`, `QUEUE_CONNECTION=database`, `MAIL_*`, `MANTAABA_ADMIN_EMAIL`.
2. Pasang aplikasi:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   php artisan key:generate          # hanya pada instalasi pertama
   php artisan migrate --force
   php artisan db:seed --force       # mencetak password acak Super Admin sekali: simpan, lalu ganti setelah login
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
3. Cron: `* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1`, dan antrean `php artisan queue:work --tries=3` (Supervisor) atau `queue:work --stop-when-empty` lewat cron di shared hosting.
4. Masuk sebagai Super Admin → isi **Setting Situs**, buat akun pengurus, atur **Hak Akses**.

Panduan lengkap (cPanel, backup & restore) disusun di M8: `docs/DEPLOY.md`.

## Lisensi

Dikembangkan dari [Bara Asa Project](https://github.com/ahdiatahsan/baraasaproject) (MIT).
