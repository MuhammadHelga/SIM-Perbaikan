-- @description: Tambah waktu kejadian laporan yang terpisah dari waktu pencatatan sistem
ALTER TABLE laporan_kerusakan
  ADD COLUMN waktu_kejadian DATETIME NULL AFTER tanggal;
