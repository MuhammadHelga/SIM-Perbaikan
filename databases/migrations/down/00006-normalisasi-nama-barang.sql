-- @description: (Best-effort) Kembalikan kapitalisasi nama barang sebelum migrasi 00007.
-- Catatan: normalisasi data bersifat lossy; nilai ini mengembalikan gaya kapitalisasi
-- sebelumnya yang paling mendekati (huruf besar) untuk nama yang dikenali.

UPDATE barang
SET nama_barang = CASE LOWER(TRIM(nama_barang))
  WHEN 'ac' THEN 'AC'
  WHEN 'almed' THEN 'ALMED'
  WHEN 'aplikasi' THEN 'Aplikasi'
  WHEN 'cctv' THEN 'CCTV'
  WHEN 'cpu' THEN 'CPU'
  WHEN 'finger' THEN 'FINGER'
  WHEN 'hardware' THEN 'HARDWARE'
  WHEN 'ip' THEN 'IP'
  WHEN 'jaringan' THEN 'JARINGAN'
  WHEN 'komputer' THEN 'KOMPUTER'
  WHEN 'lain-lain' THEN 'LAIN-LAIN'
  WHEN 'laptop' THEN 'LAPTOP'
  WHEN 'lcd' THEN 'LCD'
  WHEN 'led' THEN 'LED'
  WHEN 'mesin parkir' THEN 'MESIN PARKIR'
  WHEN 'monitor' THEN 'MONITOR'
  WHEN 'printer' THEN 'PRINTER'
  WHEN 'scanner' THEN 'SCANNER'
  WHEN 'server' THEN 'Server'
  WHEN 'simrs' THEN 'SIMRS'
  WHEN 'ups' THEN 'UPS'
END
WHERE LOWER(TRIM(nama_barang)) IN (
  'ac', 'almed', 'aplikasi', 'cctv', 'cpu', 'finger', 'hardware', 'ip',
  'jaringan', 'komputer', 'lain-lain', 'laptop', 'lcd', 'led',
  'mesin parkir', 'monitor', 'printer', 'scanner', 'server', 'simrs', 'ups'
);
