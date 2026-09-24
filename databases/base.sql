-- ============================================================
-- lkah — base schema (version 0)
-- Dijalankan otomatis oleh: php vendor/bin/migrate reset -p databases
-- Catatan: tanpa CREATE DATABASE / USE (byjg mengurus dari URI).
-- ============================================================

CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id      CHAR(36)     NOT NULL UNIQUE,
  nama           VARCHAR(100) NULL,
  username       VARCHAR(50)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','teknisi') NOT NULL DEFAULT 'admin',
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ruangan (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_ruangan  VARCHAR(20)  NULL UNIQUE,
  nama_ruangan  VARCHAR(100) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE barang (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_barang  VARCHAR(30)  NULL UNIQUE,
  nama_barang  VARCHAR(100) NOT NULL,
  kategori     VARCHAR(50)  NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE laporankerusakan (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tanggal            DATE         NOT NULL,
  id_ruangan         INT UNSIGNED NOT NULL,
  id_barang          INT UNSIGNED NOT NULL,
  serial_number      VARCHAR(50)  NULL,
  rincian_kerusakan  VARCHAR(300) NOT NULL,
  uraian_kegiatan    VARCHAR(500) NULL,
  status_penanganan  ENUM('Pending','Proses','Selesai') NOT NULL DEFAULT 'Pending',
  prioritas          ENUM('Rendah','Sedang','Tinggi')   NOT NULL DEFAULT 'Sedang',
  kirim_status       ENUM('belum','dikirim','diterima') NOT NULL DEFAULT 'belum',
  tgl_kirim          DATE NULL,
  tgl_terima         DATE NULL,
  id_user            INT UNSIGNED NULL,
  created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_lap_ruangan FOREIGN KEY (id_ruangan) REFERENCES ruangan(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_lap_barang  FOREIGN KEY (id_barang)  REFERENCES barang(id)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_lap_user    FOREIGN KEY (id_user)    REFERENCES users(id)
      ON DELETE SET NULL,
  INDEX idx_lap_tanggal (tanggal),
  INDEX idx_lap_status  (status_penanganan),
  INDEX idx_lap_ruangan (id_ruangan),
  INDEX idx_lap_barang  (id_barang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
