-- @description: Rollback 00008 — hapus kolom verify_token
ALTER TABLE laporan_kerusakan
  DROP COLUMN verify_token;
