-- @description: Tambah riwayat perubahan laporan kerusakan
CREATE TABLE laporan_riwayat (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  laporan_id  INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NULL,
  actor_name  VARCHAR(100) NOT NULL,
  aksi        VARCHAR(40) NOT NULL,
  detail      TEXT NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_riwayat_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL,
  INDEX idx_riwayat_laporan_waktu (laporan_id, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
