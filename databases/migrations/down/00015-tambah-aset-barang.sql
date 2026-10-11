ALTER TABLE laporan_kerusakan
  DROP FOREIGN KEY fk_lap_aset,
  DROP COLUMN tipe_barang,
  DROP COLUMN id_aset;

DROP TABLE aset_barang;