-- @description: Rollback kolom jabatan pada tabel users
ALTER TABLE users
  DROP COLUMN jabatan;
