-- @description: Tambah kolom jabatan pada tabel users
ALTER TABLE users
  ADD COLUMN jabatan VARCHAR(100) NULL AFTER nama;
