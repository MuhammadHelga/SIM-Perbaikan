-- @description: Rollback soft-delete master data (buang kolom aktif)
DROP INDEX idx_ruangan_aktif ON ruangan;
ALTER TABLE ruangan DROP COLUMN aktif;

DROP INDEX idx_barang_aktif ON barang;
ALTER TABLE barang DROP COLUMN aktif;
