-- @description: Rollback 00011 — kembalikan verify_uid menjadi kolom verify_token
ALTER TABLE surat_kerusakan
  DROP INDEX uk_surat_uid,
  CHANGE COLUMN verify_uid verify_token CHAR(32) NOT NULL,
  ADD UNIQUE KEY uk_surat_token (verify_token);
