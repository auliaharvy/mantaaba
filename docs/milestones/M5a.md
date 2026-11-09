# M5a — Akuntansi Inti

Branch: `m5a-akuntansi` · Acuan: [PRD 9.13, 13.3](../PRD.md) · Status: **belum mulai**

Legenda: `[x]` selesai · `[~]` sedang dikerjakan · `[ ]` belum · `→ Mn` dipindah ke milestone lain

| # | Langkah | Status | Catatan (file / test / keputusan) |
|---|---|---|---|
| 1 | `coa_accounts` (pohon, atribut kas/bank/piutang/utang/kontra/pembatasan/arus kas) + CRUD + seed ISAK 35 | [ ] | |
| 2 | `parties`, `cost_centers` (seed + otomatis per unit bisnis), `fiscal_periods` | [ ] | |
| 3 | `journals` + `journal_lines` + form voucher (baris dinamis, total seimbang, lampiran) | [ ] | |
| 4 | Service `JournalPoster`: 12 aturan hard-stop (PRD 9.13.2) + aturan per 8 tipe voucher | [ ] | |
| 5 | Alur status draft → diajukan → terposting/ditolak; pengaju ≠ penyetuju; nomor saat posting (lock, tanpa loncat) | [ ] | |
| 6 | Antrean persetujuan (pratinjau lampiran, posting massal, tolak + alasan) | [ ] | |
| 7 | **Balik** voucher (pembalik + status `dibalik`) | [ ] | |
| 8 | Template cepat voucher (belanja tunai, bayar tagihan, terima donasi, setor kas, kasbon, pelunasan utang, penjualan panen) | [ ] | |
| 9 | Cetak voucher PDF | [ ] | |
| 10 | Wizard saldo awal (MJV, selisih wajib 0, per pihak untuk piutang/utang) | [ ] | |
| 11 | Buku besar per akun + neraca saldo + buku kas/bank | [ ] | Laporan lengkap di M5b |
| 12 | Tombol "Buat voucher" dari catatan kesehatan (M2) | [ ] | |
| 13 | Activity log & notifikasi voucher | [ ] | |
| 14 | Test & tutup M5a | [ ] | |

## Acceptance criteria
| AC | Status | Test |
|---|---|---|
| Voucher tidak seimbang → 422 dengan selisih | [ ] | |
| Ajukan tanpa lampiran → 422 | [ ] | |
| Pengaju = penyetuju → 403 (termasuk Super Admin) | [ ] | |
| Posting ke periode tertutup → 422 | [ ] | |
| Aturan tiap 8 tipe voucher (mis. BPV tanpa kredit bank → 422) | [ ] | |
| Voucher terposting diubah/dihapus → 403; Balik mengembalikan saldo | [ ] | |
| Nomor voucher urut tanpa loncatan | [ ] | |
| Pengeluaran yang membuat saldo kas minus → 422 | [ ] | |
| Akun header/nonaktif ditolak; piutang/utang wajib pihak; pendapatan/beban wajib pusat biaya | [ ] | |
| N voucher acak seimbang → neraca saldo seimbang | [ ] | |
