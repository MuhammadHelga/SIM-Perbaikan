-- @description: Soft-delete aset barang (kolom aktif) + serial_number boleh NULL
--
-- Aset fisik tidak lagi dihapus permanen: "hapus" = set aktif = 0 agar laporan
-- yang pernah memakainya tetap bisa ditelusuri. Serial tanpa SN asli disimpan
-- sebagai NULL (MySQL mengizinkan banyak NULL pada kolom UNIQUE).
ALTER TABLE aset_barang
  ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER keterangan,
  MODIFY COLUMN serial_number VARCHAR(50) NULL;

CREATE INDEX idx_aset_aktif ON aset_barang (aktif);
