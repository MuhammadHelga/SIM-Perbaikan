-- @description: Rollback soft-delete aset (hapus kolom aktif, serial kembali NOT NULL)
--
-- Serial NULL diberi nilai unik "TANPA-SN-<id>" sebelum kolom dikembalikan ke
-- NOT NULL, supaya tidak melanggar UNIQUE (beberapa NULL tidak bisa jadi nilai
-- yang sama saat NOT NULL).
UPDATE aset_barang SET serial_number = CONCAT('TANPA-SN-', id) WHERE serial_number IS NULL;

DROP INDEX idx_aset_aktif ON aset_barang;

ALTER TABLE aset_barang
  DROP COLUMN aktif,
  MODIFY COLUMN serial_number VARCHAR(50) NOT NULL;
