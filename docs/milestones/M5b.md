# M5b — Laporan Keuangan, Donasi & Anggaran

Branch: `m5b-laporan` · Acuan: [PRD 9.13.4–9.13.5, 9.14, 9.15](../PRD.md) · Status: **selesai** (10 Nov 2026)

Legenda: `[x]` selesai · `[~]` sedang dikerjakan · `[ ]` belum · `→ Mn` dipindah ke milestone lain

| # | Langkah | Status | Catatan (file / test / keputusan) |
|---|---|---|---|
| 1 | Laporan Posisi Keuangan (ISAK 35) | [x] | `App\Services\FinancialReports::position()` (semua angka dalam sen, agregasi SQL). Aset lancar = kode `1-1*`. Surplus yang belum ditutup tampil di kelompok aset neto sesuai pembatasannya. Ada kolom pembanding tahun lalu dan peringatan bila tidak seimbang |
| 2 | Laporan Penghasilan Komprehensif (kolom tanpa/dengan pembatasan) | [x] | `activities()`: akun 4-9000 (aset neto dibebaskan) tampil + di kolom tanpa pembatasan dan − di kolom dengan pembatasan. Jurnal penutupan tahun tidak dihitung, jadi laporan tahun yang sudah ditutup tidak berubah |
| 3 | Laporan Perubahan Aset Neto | [x] | `netAssetChanges()`: saldo awal, surplus, perubahan langsung (saldo awal pembukuan/koreksi), saldo akhir per kelompok |
| 4 | Laporan Arus Kas metode langsung (berdasar `cash_flow_group` akun lawan) | [x] | `cashFlow()`: baris non-kas pada voucher yang menyentuh kas/bank, dikelompokkan per `statement_group`. **Keputusan:** jurnal saldo awal pembukuan dihitung sebagai kas awal, bukan sebagai arus kas |
| 5 | Buku pembantu piutang/utang + umur | [x] | `subLedger()`: per pihak, umur 0–30/31–60/61–90/>90 hari secara FIFO (pelunasan mengurangi tagihan tertua) |
| 6 | Laporan per pusat biaya + rasio (beban program, biaya per siswa, rasio lancar, sumber pendapatan) | [x] | `byCostCenter()`, `ratios()` |
| 7 | Export PDF (kop, penandatangan) & CSV + drill-down laporan → buku besar → voucher → lampiran | [x] | `ReportController` + `dashboard/report/body` (satu partial dipakai halaman & PDF, jadi angkanya pasti sama). Penandatangan diambil dari Setting `report_signatories`. Nama akun di laporan menaut ke buku besar dengan rentang tanggal yang sama. Ada juga Jurnal Umum |
| 8 | Tutup bulan dengan ceklis (voucher tertunda, jurnal otomatis, rekonsiliasi kas/bank) + buka ulang SA | [x] | `App\Services\PeriodCloser`, halaman `/dashboard/periode/{id}`. Rekonsiliasi per akun kas/bank: selisih wajib diberi keterangan, dan rekonsiliasi harus diulang bila saldo buku berubah. Bila masih ada voucher tertunda, penutupan perlu dikonfirmasi. Generator jurnal otomatis didaftarkan di `config('mantaaba.monthly_journals')` dengan source `monthly:{key}` + id periode, sehingga idempoten. Generator stok/aset dipasang di M6/M7 |
| 9 | Tutup buku tahunan (AJV penutup ke aset neto) | [x] | `PeriodCloser::closeYear()`: syarat Januari–November sudah ditutup. AJV bertanggal 31 Desember: saldo tanpa pembatasan → 3-1200, dengan pembatasan → 3-2100 (`posting_rules.year_close`), pusat biaya UMUM. Langsung terposting oleh pemegang `period-close`, dan hanya bisa dibuat sekali per tahun |
| 10 | `budgets` + peringatan soft-stop saat voucher melebihi anggaran + laporan realisasi | [x] | `BudgetController` (`/dashboard/anggaran`), anggaran tahunan per akun & pusat biaya. Voucher yang melebihi anggaran ditolak dengan galat `budget` sampai pengaju mencentang "Lanjutkan walau melebihi anggaran"; peringatannya tersimpan di `journals.budget_warning` dan tampil ke penyetuju. Bila pengajuan gagal setelah draf tersimpan, pengguna diarahkan ke halaman voucher (tidak membuat draf ganda). Rincian anggaran bulanan → v2 |
| 11 | Donasi publik `/donasi` (rekening, QRIS, ringkasan transparansi, konfirmasi) | [x] | Formulir konfirmasi: honeypot, throttle 10/jam, bukti wajib (disimpan di disk `local`), opsi "Hamba Allah". Ringkasan transparansi tahun berjalan di-cache (`SiteCache`) |
| 12 | Verifikasi donasi → pihak + BRV/CRV otomatis diajukan; bukti terima PDF + terima kasih; donasi tunai/barang | [x] | `App\Services\DonationService` (lock): transfer → BRV, tunai → CRV, barang → MJV ke 4-1300. Nomor bukti `DON-{tahun}-{0001}`. Pusat biaya: DPR untuk pangan, UMUM untuk umum, selain itu PND. Saat voucher terposting, email terima kasih dikirim berisi tautan bukti terima bertanda tangan; tombol WA ada di halaman donasi. Pemverifikasi tidak boleh menyetujui voucher yang ia ajukan |
| 13 | Laporan Transparansi Publik + **Terbitkan ke situs** (Dokumen) | [x] | Isi: donasi per peruntukan, penggunaan dana per kelompok beban, jumlah penerima manfaat, tanpa nama donatur. Tombol "Terbitkan" (`cms-create`) membuat PDF di Dokumen kategori Laporan Transparansi (publik) |
| 14 | Test & tutup M5b | [x] | `php artisan test`: **131 lulus** (`M5b/FinanceReportTest` 6, `M5b/DonationTest` 4). Chrome headless untuk halaman baru × role × 1280/360px. Temuan & perbaikan: (1) jurnal penutupan tahun membuat laporan aktivitas, pusat biaya, dan anggaran tahun itu menjadi nol → dikecualikan; (2) saldo awal pembukuan muncul sebagai arus kas → dipindah ke kas awal; (3) test tutup periode M5a disesuaikan dengan syarat rekonsiliasi |

## Acceptance criteria
| AC | Status | Test |
|---|---|---|
| Aset = Liabilitas + Aset Neto pada setiap tanggal | [x] | `FinanceReportTest::test_position_balances_and_cash_flow_reconciles_on_every_date` (5 tanggal, termasuk sebelum saldo awal) |
| Saldo awal kas + arus bersih = saldo akhir kas | [x] | idem (3 rentang) |
| Angka laporan sama dengan hitungan manual data uji | [x] | `FinanceReportTest::test_report_figures_match_manual_calculation` (posisi, aktivitas, arus kas, pusat biaya, piutang/utang) |
| Donasi `menunggu` tidak memengaruhi laporan | [x] | `DonationTest::test_public_confirmation_waits_and_does_not_affect_reports` |
| Verifikasi donasi membuat tepat satu voucher; tolak wajib alasan | [x] | `DonationTest::test_verify_creates_exactly_one_voucher_and_receipt_after_posting`, `test_reject_requires_reason_and_anonymous_name_is_hidden` |
| Tutup bulan dijalankan ulang tidak membuat jurnal ganda | [x] | `FinanceReportTest::test_month_close_checklist_is_idempotent_and_requires_reconciliation` (generator uji) + `test_year_close_moves_surplus_to_net_assets_once` |
| Nama donatur tidak tampil di halaman publik | [x] | `DonationTest::test_cash_and_goods_donations_and_public_transparency_never_show_donor_names` |
| Anggaran = soft-stop (peringatan + konfirmasi) | [x] | `FinanceReportTest::test_budget_is_a_soft_stop` |

## Catatan
- Migrasi baru `2026_11_10_000009_create_finance_extras_tables.php` (`budgets`, `cash_reconciliations`, `donations`, `journals.budget_warning`).
- Data contoh (non-produksi): 2 anggaran tahun berjalan, 1 konfirmasi donasi publik menunggu verifikasi.
- Peruntukan "umum" = donasi tanpa pembatasan (TBD Q10).
