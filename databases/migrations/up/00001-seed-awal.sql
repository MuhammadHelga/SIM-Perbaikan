-- @description: Seed user admin + contoh ruangan & barang
-- Password user 'admin' = admin123 (hash bcrypt, PASSWORD_DEFAULT)

INSERT INTO users (public_id, nama, username, password_hash, role)
VALUES (UUID(), 'Administrator', 'admin',
        '$2y$12$qDKH2xWDRU7MMQau.pruLOxcuarjj3t/F96/9InOkqfSzxBDbcWRy',
        'admin');

INSERT INTO ruangan (kode_ruangan, nama_ruangan) VALUES
  ('POLI','POLI'),
  ('UGD','UGD'),
  ('MJKN','MJKN'),
  ('RADIOLOGI','Radiologi'),
  ('LAB','Laboratorium'),
  ('KASIR','Kasir');

INSERT INTO barang (kode_barang, nama_barang, kategori) VALUES
  ('BRG-01','Komputer','Hardware'),
  ('BRG-02','Printer','Hardware'),
  ('BRG-03','Monitor','Hardware'),
  ('BRG-04','Jaringan','Jaringan'),
  ('BRG-05','Aplikasi','Aplikasi'),
  ('BRG-06','Server','Server'),
  ('BRG-07','AC','Fasilitas');
