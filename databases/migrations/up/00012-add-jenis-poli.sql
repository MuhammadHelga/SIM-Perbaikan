-- @description: Tambah pilihan jenis poli pada laporan kerusakan
ALTER TABLE laporan_kerusakan
  ADD COLUMN jenis_poli VARCHAR(80) NULL AFTER id_ruangan;

UPDATE laporan_kerusakan lk
INNER JOIN ruangan r ON r.id = lk.id_ruangan
SET lk.jenis_poli = 'Belum ditentukan'
WHERE UPPER(TRIM(r.nama_ruangan)) = 'POLI';
