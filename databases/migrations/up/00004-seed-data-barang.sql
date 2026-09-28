-- @description: Seed 10 barang baru, samakan kode barang dengan nama, hapus AC & Server
-- Hasil akhir master barang = 15: ALMED, APLIKASI, CCTV, CPU, FINGER, HARDWARE,
-- JARINGAN, KOMPUTER, LAPTOP, LAIN-LAIN, MESIN PARKIR, MONITOR, PRINTER, SCANNER, SIMRS

-- Laporan dummy yang memakai AC & Server dihapus dulu (FK RESTRICT)
DELETE FROM laporankerusakan WHERE id_barang IN (
  SELECT id FROM barang WHERE kode_barang IN ('BRG-06','BRG-07')
);

-- Hapus barang AC & Server (tidak ada di daftar master)
DELETE FROM barang WHERE kode_barang IN ('BRG-06','BRG-07');

-- Samakan kode barang lama agar sesuai nama
UPDATE barang SET kode_barang = 'KOMPUTER' WHERE kode_barang = 'BRG-01';
UPDATE barang SET kode_barang = 'PRINTER'  WHERE kode_barang = 'BRG-02';
UPDATE barang SET kode_barang = 'MONITOR'  WHERE kode_barang = 'BRG-03';
UPDATE barang SET kode_barang = 'JARINGAN' WHERE kode_barang = 'BRG-04';
UPDATE barang SET kode_barang = 'APLIKASI' WHERE kode_barang = 'BRG-05';

-- Tambah barang baru (kode = nama)
INSERT INTO barang (kode_barang, nama_barang) VALUES
  ('ALMED','ALMED'),
  ('CCTV','CCTV'),
  ('CPU','CPU'),
  ('FINGER','FINGER'),
  ('HARDWARE','HARDWARE'),
  ('LAPTOP','LAPTOP'),
  ('LAIN-LAIN','LAIN-LAIN'),
  ('MESIN PARKIR','MESIN PARKIR'),
  ('SCANNER','SCANNER'),
  ('SIMRS','SIMRS');