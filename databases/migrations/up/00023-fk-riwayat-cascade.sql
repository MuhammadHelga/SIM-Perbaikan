-- @description: Riwayat laporan ikut terhapus saat laporan dihapus (FK cascade)
--
-- Sebelumnya laporan_riwayat.laporan_id tidak punya foreign key, sehingga
-- menghapus laporan meninggalkan baris riwayat tanpa induk. Bersihkan dulu
-- baris yatim yang sudah ada, lalu pasang FK ON DELETE CASCADE.

DELETE r
FROM laporan_riwayat r
LEFT JOIN laporan_kerusakan lk ON lk.id = r.laporan_id
WHERE lk.id IS NULL;

ALTER TABLE laporan_riwayat
  ADD CONSTRAINT fk_riwayat_laporan
    FOREIGN KEY (laporan_id) REFERENCES laporan_kerusakan(id)
    ON UPDATE CASCADE ON DELETE CASCADE;
