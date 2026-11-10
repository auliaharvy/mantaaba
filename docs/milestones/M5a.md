# M5a — Akuntansi Inti

Branch: `m5a-akuntansi` · Acuan: [PRD 9.13, 13.3](../PRD.md) · Status: **selesai, menunggu review** (10 Nov 2026)

Legenda: `[x]` selesai · `[~]` sedang dikerjakan · `[ ]` belum · `→ Mn` dipindah ke milestone lain

| # | Langkah | Status | Catatan (file / test / keputusan) |
|---|---|---|---|
| 1 | `coa_accounts` (pohon, atribut kas/bank/piutang/utang/kontra/pembatasan/arus kas) + CRUD + seed ISAK 35 | [x] | `CoaController`, `CoaSeeder` (PRD 13.3, termasuk akumulasi penyusutan sebagai kontra). Saldo normal dihitung dari tipe & kontra. Akun berjurnal tidak bisa dihapus/diubah kode-tipenya |
| 2 | `parties`, `cost_centers` (seed + otomatis per unit bisnis), `fiscal_periods` | [x] | Kode pihak otomatis (`PEM-0001`, …). Pusat biaya: UMUM, PND, ASR, DPR, KBN, KLM, KND + `UB-xxx` otomatis saat unit bisnis disimpan. Periode dibuat otomatis per bulan. **Keputusan:** tutup/buka ulang sederhana sudah ada (untuk aturan periode tertutup); ceklis tutup bulan & tutup buku tahunan di M5b |
| 3 | `journals` + `journal_lines` + form voucher (baris dinamis, total seimbang, lampiran) | [x] | `JournalController`, `dashboard/journal/form` (+ `line`): tambah/hapus baris, total & selisih langsung, penanda baris yang wajib pihak/pusat biaya, unggah banyak lampiran (bisa dari kamera HP), disk `local` |
| 4 | Service `JournalPoster`: 12 aturan hard-stop (PRD 9.13.2) + aturan per 8 tipe voucher | [x] | `App\Services\JournalPoster`; uang dihitung dalam sen (`App\Support\Money`). Saldo awal (MJV sistem) satu-satunya pengecualian aturan "MJV tanpa kas/bank" |
| 5 | Alur status draft → diajukan → terposting/ditolak; pengaju ≠ penyetuju; nomor saat posting (lock, tanpa loncat) | [x] | Pembuat **dan** pengaju tidak boleh menyetujui (dicek eksplisit, berlaku untuk Super Admin). Nomor `{TIPE}/{YYYY}/{MM}/{0001}` diberikan saat posting di dalam lock |
| 6 | Antrean persetujuan (pratinjau lampiran, posting massal, tolak + alasan) | [x] | `/dashboard/voucher-antrean`: gambar lampiran tampil langsung; posting massal melaporkan voucher yang gagal tanpa membatalkan yang lain |
| 7 | **Balik** voucher (pembalik + status `dibalik`) | [x] | Pembalik langsung terposting (nomor baru, debit/kredit ditukar), asal berstatus `dibalik` |
| 8 | Template cepat voucher (belanja tunai, bayar tagihan, terima donasi, setor kas, kasbon, pelunasan utang, penjualan panen) | [x] | `config('mantaaba.voucher_templates')` |
| 9 | Cetak voucher PDF | [x] | `pdf/voucher`: kop, nomor, baris, dibuat/diajukan/disetujui + tanggal |
| 10 | Wizard saldo awal (MJV, selisih wajib 0, per pihak untuk piutang/utang) | [x] | `OpeningBalanceController` (`period-close`): saldo di sisi normal akun, hitung selisih langsung, piutang/utang per pihak; diposting langsung; tanggal tersimpan ke Setting. Saldo awal aset tetap dari modul Aset → M7 |
| 11 | Buku besar per akun + neraca saldo + buku kas/bank | [x] | `App\Services\Ledger` (agregasi SQL): buku besar dengan saldo awal & berjalan, neraca saldo per tanggal (tanda seimbang), kartu saldo kas/bank; tautan voucher ↔ buku besar |
| 12 | Tombol "Buat voucher" dari catatan kesehatan (M2) | [x] | Draf CPV dari `config('mantaaba.posting_rules.health')` (5-1500 / kas, pusat biaya PND); satu voucher per catatan |
| 13 | Activity log & notifikasi voucher | [x] | `LogsActivity` di `journals`, `coa_accounts`, `fiscal_periods`; buka ulang periode tercatat beserta alasan. Notifikasi lonceng: voucher diajukan → penyetuju; terposting/ditolak → pembuat & pengaju |
| 14 | Test & tutup M5a | [x] | `php artisan test`: **121 lulus** (`M5a/VoucherTest` 12, termasuk 25 voucher acak → neraca saldo seimbang). Chrome headless 386 halaman × role × 1280/360px OK (4 sisanya 403 yang benar: Pengurus Inti tanpa hak setujui/saldo awal) + uji JS form voucher. Temuan & perbaikan: Super Admin bisa menghapus voucher terposting (Gate::before melewati policy) → aturan imutabilitas kini dicek di controller |

## Acceptance criteria
| AC | Status | Test |
|---|---|---|
| Voucher tidak seimbang → 422 dengan selisih | [x] | `VoucherTest::test_unbalanced_voucher_shows_difference_and_attachment_is_required` |
| Ajukan tanpa lampiran → 422 | [x] | idem |
| Pengaju = penyetuju → 403 (termasuk Super Admin) | [x] | `VoucherTest::test_maker_and_approver_must_differ_even_for_super_admin` |
| Posting ke periode tertutup → 422 | [x] | `VoucherTest::test_closed_period_rejects_submit_and_post` |
| Aturan tiap 8 tipe voucher (mis. BPV tanpa kredit bank → 422) | [x] | `VoucherTest::test_rules_for_each_of_eight_voucher_types` |
| Voucher terposting diubah/dihapus → 403; Balik mengembalikan saldo | [x] | `VoucherTest::test_posted_voucher_is_immutable_and_reversal_restores_balance` |
| Nomor voucher urut tanpa loncatan | [x] | `VoucherTest::test_numbers_have_no_gaps_when_drafts_are_rejected_or_deleted` |
| Pengeluaran yang membuat saldo kas minus → 422 | [x] | `VoucherTest::test_cash_cannot_go_negative` |
| Akun header/nonaktif ditolak; piutang/utang wajib pihak; pendapatan/beban wajib pusat biaya | [x] | `VoucherTest::test_account_party_and_cost_center_rules` |
| N voucher acak seimbang → neraca saldo seimbang | [x] | `VoucherTest::test_random_balanced_vouchers_keep_trial_balance_balanced` |

## Catatan
- Migrasi baru `2026_11_10_000008_create_accounting_tables.php`. Kolom `journal_lines.farm_cycle_id` & `asset_id` belum ber-FK (tabelnya dibuat di M6/M7).
- Pemilihan pihak di form voucher memakai daftar biasa — shortcut bila pihak sudah ratusan: ganti select2 dengan pencarian (`party.search` sudah tersedia).
- Data contoh (non-produksi): saldo awal, 2 voucher terposting, 1 diajukan, 1 draf.
