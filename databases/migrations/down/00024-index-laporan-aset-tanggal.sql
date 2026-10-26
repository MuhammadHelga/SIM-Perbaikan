-- @description: Hapus index indikator sering rusak
ALTER TABLE laporan_kerusakan
  DROP INDEX idx_lap_aset_tanggal;
