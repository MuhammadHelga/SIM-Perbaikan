-- @description: Samakan kapitalisasi nama barang tanpa mengubah relasi atau ID.

UPDATE barang
SET nama_barang = CASE LOWER(TRIM(nama_barang))
  WHEN 'ac' THEN 'AC'
  WHEN 'almed' THEN 'ALMED'
  WHEN 'aplikasi' THEN 'Aplikasi'
  WHEN 'cctv' THEN 'CCTV'
  WHEN 'cpu' THEN 'CPU'
  WHEN 'finger' THEN 'Finger'
  WHEN 'hardware' THEN 'Hardware'
  WHEN 'jaringan' THEN 'Jaringan'
  WHEN 'komputer' THEN 'Komputer'
  WHEN 'laptop' THEN 'Laptop'
  WHEN 'lain-lain' THEN 'Lain-Lain'
  WHEN 'mesin parkir' THEN 'Mesin Parkir'
  WHEN 'monitor' THEN 'Monitor'
  WHEN 'printer' THEN 'Printer'
  WHEN 'scanner' THEN 'Scanner'
  WHEN 'server' THEN 'Server'
  WHEN 'simrs' THEN 'SIMRS'
END
WHERE LOWER(TRIM(nama_barang)) IN (
  'ac', 'almed', 'aplikasi', 'cctv', 'cpu', 'finger', 'hardware',
  'jaringan', 'komputer', 'laptop', 'lain-lain', 'mesin parkir',
  'monitor', 'printer', 'scanner', 'server', 'simrs'
);
