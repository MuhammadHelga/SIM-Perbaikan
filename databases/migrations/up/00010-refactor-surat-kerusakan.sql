-- @description: Pindahkan data surat ke tabel terpisah surat_kerusakan (FK ke laporan)

CREATE TABLE surat_kerusakan (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_laporan      INT UNSIGNED NOT NULL,
  verify_token    CHAR(32)     NOT NULL,
  nomor_surat     VARCHAR(60)  NULL,
  tgl_surat       DATE         NULL,
  nama_pelapor    VARCHAR(100) NULL,
  jabatan_pelapor VARCHAR(100) NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_surat_token   (verify_token),
  UNIQUE KEY uk_surat_laporan (id_laporan),
  CONSTRAINT fk_surat_laporan FOREIGN KEY (id_laporan) REFERENCES laporan_kerusakan(id)
      ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Satu baris surat per laporan. Token dibuatkan bila belum ada.
INSERT INTO surat_kerusakan (id_laporan, verify_token, nomor_surat, tgl_surat, nama_pelapor, jabatan_pelapor)
SELECT id,
       COALESCE(NULLIF(verify_token, ''), LEFT(SHA2(CONCAT(UUID(), '-', id), 256), 32)),
       nomor_surat,
       tgl_surat,
       nama_pelapor,
       jabatan_pelapor
FROM laporan_kerusakan;

ALTER TABLE laporan_kerusakan
  DROP COLUMN verify_token,
  DROP COLUMN nama_pelapor,
  DROP COLUMN jabatan_pelapor,
  DROP COLUMN nomor_surat,
  DROP COLUMN tgl_surat;
