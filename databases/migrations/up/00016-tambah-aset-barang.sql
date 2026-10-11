-- @description: Aset barang per ruangan (unit fisik) + keterkaitan pada laporan
CREATE TABLE aset_barang (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_ruangan    INT UNSIGNED NOT NULL,
  id_barang     INT UNSIGNED NOT NULL,
  serial_number VARCHAR(50)  NOT NULL,
  tipe          VARCHAR(80)  NULL,
  keterangan    VARCHAR(200) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_aset_serial (serial_number),
  CONSTRAINT fk_aset_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id)
      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_aset_barang FOREIGN KEY (id_barang) REFERENCES barang(id)
      ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_aset_ruangan (id_ruangan),
  INDEX idx_aset_barang (id_barang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot aset & tipe pada laporan (aset boleh dihapus, laporan tetap utuh)
ALTER TABLE laporan_kerusakan
  ADD COLUMN id_aset      INT UNSIGNED NULL AFTER id_barang,
  ADD COLUMN tipe_barang  VARCHAR(80) NULL AFTER serial_number,
  ADD CONSTRAINT fk_lap_aset FOREIGN KEY (id_aset) REFERENCES aset_barang(id)
      ON UPDATE CASCADE ON DELETE SET NULL;