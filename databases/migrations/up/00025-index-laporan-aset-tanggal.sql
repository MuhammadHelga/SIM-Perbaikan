-- @description: Index untuk indikator "sering rusak" (aset + rentang tanggal)
--
-- Query indikator menghitung laporan per id_aset dalam N hari terakhir.
-- Index komposit ini menjaga query tetap ringan saat data laporan bertambah.
ALTER TABLE laporan_kerusakan
  ADD INDEX idx_lap_aset_tanggal (id_aset, tanggal);
