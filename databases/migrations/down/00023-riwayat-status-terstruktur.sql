-- @description: Hapus kolom status terstruktur pada riwayat laporan
ALTER TABLE laporan_riwayat
  DROP INDEX idx_riwayat_status,
  DROP COLUMN status_baru,
  DROP COLUMN status_lama;
