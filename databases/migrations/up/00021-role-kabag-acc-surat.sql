-- @description: Role kabag + kolom ACC penandatangan kedua surat
ALTER TABLE users
  MODIFY COLUMN role ENUM('admin','teknisi','kabag') NOT NULL DEFAULT 'admin';

ALTER TABLE surat_kerusakan
  ADD COLUMN acc_status ENUM('menunggu','diacc') NOT NULL DEFAULT 'menunggu' AFTER jabatan_pelapor,
  ADD COLUMN acc_oleh   INT UNSIGNED NULL AFTER acc_status,
  ADD COLUMN tgl_acc    DATETIME NULL AFTER acc_oleh,
  ADD CONSTRAINT fk_surat_acc_user FOREIGN KEY (acc_oleh) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL;
