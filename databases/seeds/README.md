# Seed data

Folder ini memisahkan **data contoh/development** dari migrasi skema di
`databases/migrations/`.

## Aturan

- `migrations/up/` hanya untuk perubahan skema dan data referensi yang memang
  wajib ada di semua lingkungan.
- Data dummy (laporan contoh, aset contoh) untuk development diletakkan di
  `seeds/dev/` dan dijalankan manual.
- Jangan menambahkan data demo baru ke `migrations/up/` lagi.
- Migrasi yang sudah pernah dijalankan **tidak boleh** diedit atau dihapus,
  karena `migration_version` di database sudah mencatat nomornya.

## Warisan lama

Dua seed demo masih berada di jalur migrasi karena sudah menjadi bagian sejarah
instalasi:

- `migrations/up/00002-seed-data-dummy.sql` — 24 laporan contoh.
- `migrations/up/00022-seed-aset-contoh.sql` — 7 aset contoh.

Keduanya tetap dipertahankan agar database yang sudah ada tidak rusak. Untuk
instalasi produksi baru, jalankan `seeds/prod/cleanup-demo.sql` setelah migrasi
selesai (baca bagian review di dalam file tersebut terlebih dahulu).

## Struktur

```
seeds/
├── dev/                  # data contoh untuk development (jalankan manual)
├── prod/                 # skrip satu kali untuk produksi (mis. cleanup demo)
└── README.md
```

Untuk menambah data contoh baru, buat file di `seeds/dev/` dan jalankan dengan:

```bash
mysql -u USER -p NAMA_DATABASE < databases/seeds/dev/nama-file.sql
```

Jangan memakai `php vendor/bin/migrate` untuk file di folder ini.
