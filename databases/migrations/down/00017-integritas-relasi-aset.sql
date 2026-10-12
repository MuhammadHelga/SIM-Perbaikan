-- @description: Rollback integritas relasi (RESTRICT -> CASCADE, buang composite FK)

-- 1) Kembalikan FK aset -> ruangan/barang ke CASCADE.
ALTER TABLE aset_barang
  DROP FOREIGN KEY fk_aset_barang,
  ADD CONSTRAINT fk_aset_barang FOREIGN KEY (id_barang) REFERENCES barang(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE aset_barang
  DROP FOREIGN KEY fk_aset_ruangan,
  ADD CONSTRAINT fk_aset_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id)
    ON UPDATE CASCADE ON DELETE CASCADE;

-- 2) Kembalikan FK laporan -> aset ke satu kolom (SET NULL).
ALTER TABLE laporan_kerusakan
  DROP FOREIGN KEY fk_lap_aset_identity,
  ADD CONSTRAINT fk_lap_aset FOREIGN KEY (id_aset) REFERENCES aset_barang(id)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- 3) Buang index induk komposit.
ALTER TABLE aset_barang
  DROP INDEX uk_aset_identity;
