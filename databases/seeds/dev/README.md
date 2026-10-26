# Seed development

Letakkan data contoh khusus development di folder ini, misalnya laporan dummy
baru atau aset percobaan. File di sini **tidak** dijalankan otomatis oleh
`php vendor/bin/migrate`.

Contoh pemakaian:

```bash
mysql -u root -p lkah < databases/seeds/dev/nama-file.sql
```

Untuk data demo lama (24 laporan dan 7 aset contoh), lihat catatan di
`databases/seeds/README.md`.
