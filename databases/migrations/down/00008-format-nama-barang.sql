-- @description: Kembalikan kapitalisasi nama barang seperti sebelum migrasi 00008.

UPDATE barang
SET nama_barang = CASE LOWER(TRIM(nama_barang))
  WHEN 'ac' THEN 'AC'
  WHEN 'almed' THEN 'ALMED'
  WHEN 'aplikasi' THEN 'Aplikasi'
  WHEN 'cctv' THEN 'CCTV'
  WHEN 'cpu' THEN 'CPU'
  WHEN 'finger' THEN 'FINGER'
  WHEN 'hardware' THEN 'HARDWARE'
  WHEN 'jaringan' THEN 'JARINGAN'
  WHEN 'komputer' THEN 'KOMPUTER'
  WHEN 'lain-lain' THEN 'LAIN-LAIN'
  WHEN 'laptop' THEN 'LAPTOP'
  WHEN 'mesin parkir' THEN 'MESIN PARKIR'
  WHEN 'monitor' THEN 'MONITOR'
  WHEN 'printer' THEN 'PRINTER'
  WHEN 'scanner' THEN 'SCANNER'
  WHEN 'server' THEN 'Server'
  WHEN 'simrs' THEN 'SIMRS'
END
WHERE LOWER(TRIM(nama_barang)) IN (
  'ac', 'almed', 'aplikasi', 'cctv', 'cpu', 'finger', 'hardware',
  'jaringan', 'komputer', 'lain-lain', 'laptop', 'mesin parkir',
  'monitor', 'printer', 'scanner', 'server', 'simrs'
);
