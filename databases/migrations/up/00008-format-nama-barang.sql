-- @description: Gunakan kapital hanya di huruf pertama nama barang dan pertahankan singkatan.

UPDATE barang
SET nama_barang = CASE LOWER(TRIM(nama_barang))
  WHEN 'ac' THEN 'AC'
  WHEN 'almed' THEN 'Almed'
  WHEN 'aplikasi' THEN 'Aplikasi'
  WHEN 'cctv' THEN 'CCTV'
  WHEN 'cpu' THEN 'CPU'
  WHEN 'finger' THEN 'Finger'
  WHEN 'hardware' THEN 'Hardware'
  WHEN 'ip' THEN 'IP'
  WHEN 'jaringan' THEN 'Jaringan'
  WHEN 'komputer' THEN 'Komputer'
  WHEN 'lain-lain' THEN 'Lain-lain'
  WHEN 'laptop' THEN 'Laptop'
  WHEN 'lcd' THEN 'LCD'
  WHEN 'led' THEN 'LED'
  WHEN 'mesin parkir' THEN 'Mesin parkir'
  WHEN 'monitor' THEN 'Monitor'
  WHEN 'printer' THEN 'Printer'
  WHEN 'scanner' THEN 'Scanner'
  WHEN 'server' THEN 'Server'
  WHEN 'simrs' THEN 'SIMRS'
  WHEN 'ups' THEN 'UPS'
END
WHERE LOWER(TRIM(nama_barang)) IN (
  'ac', 'almed', 'aplikasi', 'cctv', 'cpu', 'finger', 'hardware', 'ip',
  'jaringan', 'komputer', 'lain-lain', 'laptop', 'lcd', 'led',
  'mesin parkir', 'monitor', 'printer', 'scanner', 'server', 'simrs', 'ups'
);
