-- @description: Soft-delete master data ruangan & barang (kolom aktif)
--
-- Master data tidak lagi dihapus permanen: "hapus" = aktif = 0 (tetap reversibel).
-- Hapus permanen tetap tersedia lewat aksi terpisah dan hanya bila tidak dipakai
-- oleh aset / laporan. Penghapusan yang masih dipakai tetap diblokir (RESTRICT).
ALTER TABLE ruangan
  ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER nama_ruangan;

CREATE INDEX idx_ruangan_aktif ON ruangan (aktif);

ALTER TABLE barang
  ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER nama_barang;

CREATE INDEX idx_barang_aktif ON barang (aktif);
