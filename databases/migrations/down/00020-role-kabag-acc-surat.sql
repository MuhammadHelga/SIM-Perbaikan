-- @description: Rollback role kabag + kolom ACC surat
ALTER TABLE surat_kerusakan
  DROP FOREIGN KEY fk_surat_acc_user,
  DROP COLUMN acc_status,
  DROP COLUMN acc_oleh,
  DROP COLUMN tgl_acc;

ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','teknisi') NOT NULL DEFAULT 'admin';
