-- @description: Kebalikan dari up/00004
-- Catatan: AC & Server beserta laporan dummy-nya sudah dihapus di up/00004
-- sehingga tidak dikembalikan oleh down ini.

-- Hapus barang baru dari seed 00004
DELETE FROM barang WHERE kode_barang IN (
  'ALMED','CCTV','CPU','FINGER','HARDWARE','LAPTOP',
  'LAIN-LAIN','MESIN PARKIR','SCANNER','SIMRS'
);

-- Kembalikan kode barang lama
UPDATE barang SET kode_barang = 'BRG-01' WHERE kode_barang = 'KOMPUTER';
UPDATE barang SET kode_barang = 'BRG-02' WHERE kode_barang = 'PRINTER';
UPDATE barang SET kode_barang = 'BRG-03' WHERE kode_barang = 'MONITOR';
UPDATE barang SET kode_barang = 'BRG-04' WHERE kode_barang = 'JARINGAN';
UPDATE barang SET kode_barang = 'BRG-05' WHERE kode_barang = 'APLIKASI';