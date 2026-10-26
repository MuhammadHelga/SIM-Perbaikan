-- @description: Lepas FK cascade riwayat laporan (baris yatim tidak dikembalikan)
ALTER TABLE laporan_riwayat
  DROP FOREIGN KEY fk_riwayat_laporan;
