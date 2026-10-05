-- @description: Tambah kolom verify_token untuk verifikasi surat via QR (acak, bukan ID)
ALTER TABLE laporan_kerusakan
  ADD COLUMN verify_token CHAR(32) NULL UNIQUE AFTER id;

-- Isi token acak unik untuk baris yang sudah ada.
UPDATE laporan_kerusakan
   SET verify_token = LEFT(SHA2(CONCAT(UUID(), '-', id, '-', RAND()), 256), 32)
 WHERE verify_token IS NULL OR verify_token = '';
