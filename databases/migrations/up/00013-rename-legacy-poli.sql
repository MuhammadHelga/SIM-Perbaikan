-- @description: Ganti label jenis poli lama menjadi Lainnya
UPDATE laporan_kerusakan lk
INNER JOIN ruangan r ON r.id = lk.id_ruangan
SET lk.jenis_poli = 'Lainnya'
WHERE UPPER(TRIM(r.nama_ruangan)) = 'POLI'
  AND lk.jenis_poli = 'Belum ditentukan';
