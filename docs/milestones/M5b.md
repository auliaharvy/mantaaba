# M5b — Laporan Keuangan, Donasi & Anggaran

Branch: `m5b-laporan` · Acuan: [PRD 9.13.4–9.13.5, 9.14, 9.15](../PRD.md) · Status: **belum mulai**

Legenda: `[x]` selesai · `[~]` sedang dikerjakan · `[ ]` belum · `→ Mn` dipindah ke milestone lain

| # | Langkah | Status | Catatan (file / test / keputusan) |
|---|---|---|---|
| 1 | Laporan Posisi Keuangan (ISAK 35) | [ ] | |
| 2 | Laporan Penghasilan Komprehensif (kolom tanpa/dengan pembatasan) | [ ] | |
| 3 | Laporan Perubahan Aset Neto | [ ] | |
| 4 | Laporan Arus Kas metode langsung (berdasar `cash_flow_group` akun lawan) | [ ] | |
| 5 | Buku pembantu piutang/utang + umur | [ ] | |
| 6 | Laporan per pusat biaya + rasio (beban program, biaya per siswa, rasio lancar, sumber pendapatan) | [ ] | |
| 7 | Export PDF (kop, penandatangan) & CSV + drill-down laporan → buku besar → voucher → lampiran | [ ] | |
| 8 | Tutup bulan dengan ceklis (voucher tertunda, jurnal otomatis, rekonsiliasi kas/bank) + buka ulang SA | [ ] | Jurnal otomatis stok/aset terpasang di M6/M7 |
| 9 | Tutup buku tahunan (AJV penutup ke aset neto) | [ ] | |
| 10 | `budgets` + peringatan soft-stop saat voucher melebihi anggaran + laporan realisasi | [ ] | |
| 11 | Donasi publik `/donasi` (rekening, QRIS, ringkasan transparansi, konfirmasi) | [ ] | |
| 12 | Verifikasi donasi → pihak + BRV/CRV otomatis diajukan; bukti terima PDF + terima kasih; donasi tunai/barang | [ ] | |
| 13 | Laporan Transparansi Publik + **Terbitkan ke situs** (Dokumen) | [ ] | |
| 14 | Test & tutup M5b | [ ] | |

## Acceptance criteria
| AC | Status | Test |
|---|---|---|
| Aset = Liabilitas + Aset Neto pada setiap tanggal | [ ] | |
| Saldo awal kas + arus bersih = saldo akhir kas | [ ] | |
| Angka laporan sama dengan hitungan manual data uji | [ ] | |
| Donasi `menunggu` tidak memengaruhi laporan | [ ] | |
| Verifikasi donasi membuat tepat satu voucher; tolak wajib alasan | [ ] | |
| Tutup bulan dijalankan ulang tidak membuat jurnal ganda | [ ] | |
| Nama donatur tidak tampil di halaman publik | [ ] | |
