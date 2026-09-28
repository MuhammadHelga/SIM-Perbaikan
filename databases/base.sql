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

CREATE TABLE laporan_kerusakan (
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

CREATE TABLE subnet (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cidr        VARCHAR(18)  NOT NULL UNIQUE,        -- mis. '192.100.99.0/24'
  prefix      VARCHAR(15)  NOT NULL,               -- mis. '192.100.99'
  gateway     VARCHAR(15)  NULL,
  mask        VARCHAR(15)  NULL,
  keterangan  VARCHAR(120) NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE alokasi_ip (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subnet      VARCHAR(18)  NOT NULL,               -- CIDR, mis. '192.100.99.0/24'
  unit        VARCHAR(120) NOT NULL,               -- Nama Unit / Ruangan (teks bebas)
  lokasi      VARCHAR(120) NULL,                   -- Gedung/Lantai
  hostname    VARCHAR(80)  NOT NULL,               -- Hostname komputer
  host_octet  TINYINT UNSIGNED NOT NULL,           -- oktet ke-4 (10..254)
  status      ENUM('online','offline') NOT NULL DEFAULT 'online',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_alokasi_subnet FOREIGN KEY (subnet) REFERENCES subnet(cidr)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uk_alokasi_ip (subnet, host_octet),
  UNIQUE KEY uk_alokasi_hostname (hostname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
