CREATE DATABASE IF NOT EXISTS lkah
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE lkah;

CREATE TABLE IF NOT EXISTS laporankerusakan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_barang INT NOT NULL,
    id_ruangan INT NOT NULL,
    tanggal DATE NOT NULL,
    rincian_kerusakan VARCHAR(300) NOT NULL,
    uraian_kegiatan VARCHAR(500) NOT NULL,
    status_penanganan ENUM('Belum Ditangani', 'Proses', 'Selesai') DEFAULT 'Belum Ditangani',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_barang) REFERENCES barang(id),
    FOREIGN KEY (id_ruangan) REFERENCES ruangan(id)
);