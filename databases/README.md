# Database & Migrasi — SIM-Perbaikan (`lkah`)

Folder ini mengatur skema database `lkah` (laporan kerusakan Al-Huda) memakai
[`byjg/migration`](https://github.com/byjg/migration). Semua perubahan skema
**wajib** lewat file migrasi di sini, jangan ganti tabel manual di phpMyAdmin.

---

## Struktur folder

```
databases/
├── base.sql                          # Skema awal (versi 0) — dibaca hanya saat "reset"
└── migrations/
    ├── up/
    │   ├── 00001-seed-awal.sql       # Naik dari versi 0 -> 1
    │   └── 00002-....sql             # Naik dari versi 1 -> 2 (contoh berikutnya)
    └── down/
        └── 00001-seed-awal.sql       # Kebalikan dari up/00001 (untuk rollback)
```

- **`base.sql`** = isi database saat versi 0. Hanya dijalankan oleh perintah `reset`.
- **`migrations/up/NNNNN-*.sql`** = perubahan menuju versi `NNNNN`.
- **`migrations/down/NNNNN-*.sql`** = cara membatalkan `up/NNNNN` (opsional, tapi disarankan).
- Tabel `migration_version` dibuat otomatis oleh tool, **jangan diedit manual**.

Aturan nama file: `NNNNN[-deskripsi].sql` (angka = versi tujuan). Contoh: `00002-tambah-kolom-prioritas.sql`.

---

## Kredensial

Ada di `config/conf.php` (`DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`).
Sesuaikan dengan lokal masing-masing. Format URI migrasi:

```
mysql://USER:PASSWORD@HOST:PORT/lkah
```

> Kalau password punya karakter spesial (`@`, `:`, `/`, `#`), URL-encode dulu — mis. `@` → `%40`.

---

## Cara pakai

### Setup pertama kali (generate database)

> ⚠️ `reset` akan **menghapus** database `lkah` lebih dulu, lalu membangun ulang dari `base.sql` + semua `up`. Jalankan hanya saat database masih kosong/baru.

```bash
cd /path/ke/SIM-Perbaikan
URI="mysql://root:PASSWORD@127.0.0.1/lkah"

php vendor/bin/migrate reset -c "$URI" -p databases -vv
```

Hasil: database `lkah` + semua tabel + data seed, versi **1**.

### Cek status versi

```bash
php vendor/bin/migrate version -c "$URI" -p databases
```

### Menjalankan migrasi baru

```bash
php vendor/bin/migrate up -c "$URI" -p databases -vv
```

### Rollback

```bash
# Turun ke versi tertentu
php vendor/bin/migrate down -c "$URI" -p databases --version 1

# Kosongkan skema (turun ke versi 0)
php vendor/bin/migrate down -c "$URI" -p databases --version 0
```

### Alternatif: environment variable

```bash
export MIGRATION_CONNECTION="mysql://root:PASSWORD@127.0.0.1/lkah"
export MIGRATION_PATH="databases"

php vendor/bin/migrate up -vv
php vendor/bin/migrate version
```

---

## Menambah perubahan skema (alur kerja)

1. Buat file `databases/migrations/up/00002-deskripsi-singkat.sql`.
2. (Disarankan) buat pasangannya `databases/migrations/down/00002-deskripsi-singkat.sql`.
3. Jalankan `php vendor/bin/migrate up -c "$URI" -p databases -vv`.
4. Commit kedua file tersebut.

Contoh isi `up/00002-add-prioritas.sql`:

```sql
-- @description: Tambah kolom prioritas pada laporan
ALTER TABLE laporankerusakan
  ADD COLUMN prioritas ENUM('Rendah','Sedang','Tinggi') NOT NULL DEFAULT 'Sedang'
  AFTER status_penanganan;
```

Contoh `down/00002-add-prioritas.sql`:

```sql
ALTER TABLE laporankerusakan DROP COLUMN prioritas;
```

### Kalau kerja berbarengan (multi-developer)

Pakai suffix `-dev` supaya nomor versi tidak bentrok antar cabang:

```
43-dev.sql   # developer A di branch-nya
43-dev.sql   # developer B di branch lain (aman, beda branch)
```

Saat merge, yang pertama merge mengganti nama jadi `00043.sql`; yang kedua menaikkan
nomornya jadi `00044-dev.sql`. Detail: `vendor/byjg/migration/docs/migration-scripts.md`.

---

## Aturan penting

- **Jangan mengedit `base.sql`** setelah dipakai. Perubahan skema berikutnya lewat `migrations/up/`.
- **`up` tidak menjalankan `base.sql`.** Generate awal harus pakai `reset`.
- **`reset` menghapus database.** Jangan dipakai kalau sudah ada data yang mau dipertahankan.
- Perubahan bersifat maju (append-only): selalu tambah file baru, jangan ubah migrasi yang sudah dijalankan/di-commit.
- Setiap migrasi dijalankan **satu kali**. Kalau salah, buat migrasi baru untuk memperbaikinya.

---

## Ringkasan tabel

| Tabel | Isi |
|---|---|
| `users` | Akun login (`username`, `password_hash`, `role`) |
| `ruangan` | Master unit/ruangan (mis. POLI, UGD, Radiologi) |
| `barang` | Master jenis barang/perangkat (mis. Komputer, Printer) |
| `laporankerusakan` | Data laporan kerusakan/perbaikan |
| `migration_version` | Versi migrasi (dikelola tool, jangan diubah manual) |

Kolom penting di `laporankerusakan`:
`tanggal`, `id_ruangan`, `id_barang`, `serial_number`, `rincian_kerusakan`,
`uraian_kegiatan`, `status_penanganan` (`Pending`/`Proses`/`Selesai`),
`prioritas` (`Rendah`/`Sedang`/`Tinggi`), `kirim_status` (`belum`/`dikirim`/`diterima`),
`tgl_kirim`, `tgl_terima`, `id_user`.

---

## Akun seed

File `migrations/up/00001-seed-awal.sql` membuat user awal:

- **username:** `admin`
- **password:** `admin123`

> Segera ganti password ini (buat user baru dengan `password_hash()` dari PHP), dan
> jangan dipakai di lingkungan produksi.
