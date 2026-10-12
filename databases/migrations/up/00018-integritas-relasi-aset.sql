-- @description: Integritas relasi laporan -> aset (composite FK) + CASCADE jadi RESTRICT
--
-- 1. Laporan hanya boleh menunjuk aset yang benar-benar berada di ruangan & jenis
--    barang yang tercatat. Saat id_aset NULL (laporan serial manual) FK dilewati
--    (MATCH SIMPLE), jadi tetap aman.
-- 2. Menghapus ruangan/jenis barang yang masih punya aset diblokir (RESTRICT),
--    supaya tidak ada lagi penghapusan inventaris diam-diam lewat CASCADE.

-- 1) Defensif: netralkan id_aset lama yang tidak konsisten (bila ada).
UPDATE laporan_kerusakan lk
  JOIN aset_barang a ON a.id = lk.id_aset
  SET lk.id_aset = NULL
  WHERE lk.id_ruangan <> a.id_ruangan OR lk.id_barang <> a.id_barang;

-- 2) Index induk untuk FK komposit.
ALTER TABLE aset_barang
  ADD UNIQUE KEY uk_aset_identity (id, id_ruangan, id_barang);

-- 3) Ganti FK laporan -> aset: (id_aset, SET NULL) menjadi komposit RESTRICT.
ALTER TABLE laporan_kerusakan
  DROP FOREIGN KEY fk_lap_aset,
  ADD CONSTRAINT fk_lap_aset_identity
    FOREIGN KEY (id_aset, id_ruangan, id_barang)
    REFERENCES aset_barang (id, id_ruangan, id_barang)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- 4) Cegah hapus ruangan/jenis barang yang masih punya aset.
ALTER TABLE aset_barang
  DROP FOREIGN KEY fk_aset_ruangan,
  ADD CONSTRAINT fk_aset_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE aset_barang
  DROP FOREIGN KEY fk_aset_barang,
  ADD CONSTRAINT fk_aset_barang FOREIGN KEY (id_barang) REFERENCES barang(id)
    ON UPDATE CASCADE ON DELETE RESTRICT;
