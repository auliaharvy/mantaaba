# Deploy & Operasional — Rumah Mantaaba Sigaling

Panduan memasang aplikasi di hosting cPanel (shared hosting) dan menjalankannya sehari-hari. Semua perintah `php` di bawah memakai PHP 8.2+ milik hosting (di cPanel biasanya `/usr/local/bin/php` atau `ea-php82`).

## 1. Kebutuhan server
- PHP ≥ 8.2 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `zip`, `bcmath`, `intl`.
- MySQL 5.7+/MariaDB 10.3+, database `utf8mb4_unicode_ci`.
- Akses SSH (Terminal cPanel) dan Cron Jobs.
- Composer 2 (di server atau jalankan di lokal lalu unggah `vendor/`).
- Node tidak dibutuhkan di server: aset Tailwind dibangun di lokal (`npm run build`) dan ikut diunggah (`public/build`).

## 2. Struktur folder di cPanel
```
/home/USER/mantaaba/          ← seluruh isi repo (di luar public_html)
/home/USER/public_html/       ← symlink ke /home/USER/mantaaba/public
```
Bila symlink tidak diizinkan: ubah *document root* domain ke `/home/USER/mantaaba/public` lewat menu **Domains**.

## 3. Pemasangan pertama
```bash
cd ~ && git clone https://github.com/auliaharvy/mantaaba.git mantaaba && cd mantaaba
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# isi .env (bagian 4), lalu:
php artisan migrate --force
php artisan db:seed --force          # produksi: hanya data inti (COA, skema, gudang, menu, Super Admin)
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R ug+rwx storage bootstrap/cache
```
Seeder demo (`DemoSeeder`, akun berpassword `password`) **tidak berjalan** bila `APP_ENV=production` — sengaja.

Setelah itu login sebagai Super Admin (email dari `SuperAdminSeeder`, segera ganti password), lalu:
1. **Setting Situs**: identitas, kontak, rekening & QRIS donasi, penandatangan laporan, anggaran makan.
2. **Setting Akuntansi**: periksa akun aturan posting.
3. **Saldo awal**: catat aset lama di menu Aset (pencatatan "aset lama"), lalu jalankan wizard **Periode → Saldo awal** (akun aset tetap terisi otomatis).
4. **Pengguna & Hak Akses**: buat akun pengurus dan beri paket (Bendahara, Koordinator, Mentor, Redaksi, Siswa petugas).

## 4. `.env` produksi
```dotenv
APP_NAME="Rumah Mantaaba Sigaling"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN-ANDA
APP_TIMEZONE=Asia/Jakarta        # bila dipakai config/app.php

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USER_mantaaba
DB_USERNAME=USER_mantaaba
DB_PASSWORD=********

QUEUE_CONNECTION=database        # email & notifikasi diproses worker (bagian 5)
CACHE_DRIVER=file
SESSION_DRIVER=file
FILESYSTEM_DRIVER=local

MAIL_MAILER=smtp
MAIL_HOST=mail.DOMAIN-ANDA       # atau SMTP penyedia (Gmail Workspace, Zoho, dll.)
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=noreply@DOMAIN-ANDA
MAIL_PASSWORD=********
MAIL_FROM_ADDRESS=noreply@DOMAIN-ANDA
MAIL_FROM_NAME="${APP_NAME}"
```
`.env` tidak pernah di-commit. Simpan salinannya di tempat aman (bukan di folder web).

Uji email: `php artisan tinker --execute="Mail::raw('Tes', fn(\$m) => \$m->to('ANDA@email.com')->subject('Tes SMTP'));"`.

## 5. Cron (cPanel → Cron Jobs)
| Jadwal | Perintah | Fungsi |
|---|---|---|
| `* * * * *` | `cd ~/mantaaba && php artisan schedule:run >> /dev/null 2>&1` | Penjadwal Laravel: hapus pendaftar ditolak > 12 bulan (02.00), ringkasan stok menipis (06.00), pengingat aset (07.00), piket terlewat (23.00) |
| `* * * * *` | `cd ~/mantaaba && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1` | Mengirim email & notifikasi antrean (shared hosting tidak mengizinkan worker permanen) |
| `30 1 * * *` | lihat bagian 6 | Backup database harian |
| `0 2 * * 0` | lihat bagian 6 | Backup berkas mingguan |

Cek antrean gagal: `php artisan queue:failed`, ulangi: `php artisan queue:retry all`.

## 6. Backup
Buat folder `~/backups` (di luar `public_html`). Simpan kredensial DB di `~/.my.cnf` (chmod 600) agar password tidak tampil di cron:
```ini
[client]
user=USER_mantaaba
password=********
```
Cron harian (database, simpan 14 hari):
```bash
mysqldump --single-transaction --routines USER_mantaaba | gzip > ~/backups/db-$(date +\%F).sql.gz && find ~/backups -name 'db-*.sql.gz' -mtime +14 -delete
```
Cron mingguan (berkas unggahan: foto, bukti, lampiran voucher, dokumen privat; simpan 8 minggu):
```bash
tar -czf ~/backups/files-$(date +\%F).tar.gz -C ~/mantaaba storage/app && find ~/backups -name 'files-*.tar.gz' -mtime +56 -delete
```
Unduh salinan ke luar server (Google Drive/komputer kantor) minimal sebulan sekali; backup yang hanya ada di server yang sama tidak melindungi dari kerusakan hosting.

## 7. Restore (uji di staging dulu)
```bash
cd ~/mantaaba && php artisan down
gunzip < ~/backups/db-YYYY-MM-DD.sql.gz | mysql USER_mantaaba
tar -xzf ~/backups/files-YYYY-MM-DD.tar.gz -C ~/mantaaba
php artisan cache:clear && php artisan config:cache && php artisan up
```
Cek setelah restore: login, buka Neraca Saldo (harus seimbang), buka satu lampiran voucher dan satu foto anggota.

**Uji restore di staging** (subdomain `staging.DOMAIN`, database terpisah) sebelum go-live dan setiap 6 bulan; catat tanggal & hasilnya di bawah.

| Tanggal | Backup yang diuji | Hasil | Oleh |
|---|---|---|---|
| | | | |

## 8. Pembaruan aplikasi
```bash
cd ~/mantaaba && php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```
Selalu backup database sebelum `migrate`. Jangan pernah menjalankan `migrate:fresh` di produksi (menghapus semua data).

## 9. Keamanan & privasi (ringkas)
- `APP_DEBUG=false` di produksi; HTTPS aktif (AutoSSL cPanel).
- Berkas privat (berkas pendaftar, kesehatan, bukti donasi, lampiran voucher, BPKB/STNK) ada di `storage/app` (disk `local`) dan hanya dibuka lewat route berizin — jangan pindahkan ke `public`.
- Super Admin dibatasi 1–3 orang. Nonaktifkan akun pengurus yang berhenti (menu Pengguna).
- Log aktivitas tidak bisa dihapus dari aplikasi; jangan menghapus tabel `activity_logs` lewat phpMyAdmin.

## 10. Ceklis go-live
- [ ] `.env` produksi terisi, `APP_DEBUG=false`, HTTPS aktif
- [ ] `migrate --force` + `db:seed --force` (data inti)
- [ ] Cron scheduler + queue + backup aktif; email uji terkirim
- [ ] Setting situs & akuntansi terisi; saldo awal terposting; neraca saldo seimbang
- [ ] Data anggota diimpor (Anggota → Import CSV); aset terdaftar
- [ ] Restore diuji di staging (tabel bagian 7)
- [ ] UAT ditandatangani pengurus (`docs/milestones/M8.md`)
