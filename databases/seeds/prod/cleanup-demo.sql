-- ============================================================
-- Cleanup data demo untuk instalasi produksi baru
-- ============================================================
-- Jalankan SETELAH semua migrasi selesai, dan hanya pada database yang
-- benar-benar baru (belum berisi laporan nyata).
--
-- Skrip ini menghapus:
--   1. 24 laporan dummy dari migrasi lama 00002
--   2. 7 aset contoh dari migrasi lama 00022
--
-- BACKUP DATABASE TERLEBIH DAHULU.
-- Baca hasil SELECT di bagian review sebelum menjalankan DELETE.
-- ============================================================

-- ---------- REVIEW: lihat dulu baris yang akan dihapus ----------
SELECT id, tanggal, serial_number, rincian_kerusakan
FROM laporan_kerusakan
WHERE rincian_kerusakan IN (
  'PC hang saat login SIMRS',
  'Printer macet / tidak mau cetak label',
  'Monitor redup dan berkedip',
  'Jaringan VGA tidak terhubung di ruang MJKN',
  'Server kasir sering restart sendiri',
  'AC ruang radiologi tidak dingin',
  'Aplikasi antrian poli tidak sinkron',
  'Komputer IGD triase tidak bisa boot',
  'Print hasil lab bergaris-garis',
  'Komputer kasir 2 lambat dan sering not responding',
  'AC rontgen bocor air',
  'Server PACS storage penuh',
  'Monitor poli jantung resolusi menurun',
  'Wifi IGD tidak stabil sering putus',
  'Komputer analis lab mati mendadak',
  'Printer struk kasir tinta habis terus',
  'Aplikasi rekam medis error saat simpan',
  'PC USG tidak terdeteksi pasien baru',
  'AC poli anak bocor dan tidak dingin',
  'Server backup IGD down',
  'Koneksi jaringan lab kimia terputus',
  'Monitor kasir 1 tidak menyala',
  'Printer rekam medis tidak menarik kertas',
  'PACS gagal menampilkan citra CT scan'
)
ORDER BY tanggal, id;

SELECT id, serial_number, tipe
FROM aset_barang
WHERE serial_number IN (
  'RAD-PC-01', 'RAD-PC-02', 'RAD-PRN-01',
  'ADM-PC-01', 'UGD-PC-01', 'POLI-PC-01', 'LAB-PRN-01'
)
ORDER BY serial_number;

-- ---------- HAPUS ----------
START TRANSACTION;

DELETE FROM laporan_kerusakan
WHERE rincian_kerusakan IN (
  'PC hang saat login SIMRS',
  'Printer macet / tidak mau cetak label',
  'Monitor redup dan berkedip',
  'Jaringan VGA tidak terhubung di ruang MJKN',
  'Server kasir sering restart sendiri',
  'AC ruang radiologi tidak dingin',
  'Aplikasi antrian poli tidak sinkron',
  'Komputer IGD triase tidak bisa boot',
  'Print hasil lab bergaris-garis',
  'Komputer kasir 2 lambat dan sering not responding',
  'AC rontgen bocor air',
  'Server PACS storage penuh',
  'Monitor poli jantung resolusi menurun',
  'Wifi IGD tidak stabil sering putus',
  'Komputer analis lab mati mendadak',
  'Printer struk kasir tinta habis terus',
  'Aplikasi rekam medis error saat simpan',
  'PC USG tidak terdeteksi pasien baru',
  'AC poli anak bocor dan tidak dingin',
  'Server backup IGD down',
  'Koneksi jaringan lab kimia terputus',
  'Monitor kasir 1 tidak menyala',
  'Printer rekam medis tidak menarik kertas',
  'PACS gagal menampilkan citra CT scan'
);

-- Hapus aset contoh hanya bila tidak dipakai laporan mana pun.
DELETE a
FROM aset_barang a
LEFT JOIN laporan_kerusakan lk ON lk.id_aset = a.id
WHERE a.serial_number IN (
  'RAD-PC-01', 'RAD-PC-02', 'RAD-PRN-01',
  'ADM-PC-01', 'UGD-PC-01', 'POLI-PC-01', 'LAB-PRN-01'
)
AND lk.id IS NULL;

COMMIT;
