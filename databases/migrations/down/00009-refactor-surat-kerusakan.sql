-- @description: Rollback 00010 — kembalikan data surat ke kolom di laporan_kerusakan

ALTER TABLE laporan_kerusakan
  ADD COLUMN verify_token    CHAR(32)     NULL UNIQUE AFTER id,
  ADD COLUMN nama_pelapor    VARCHAR(100) NULL AFTER verify_token,
  ADD COLUMN jabatan_pelapor VARCHAR(100) NULL AFTER nama_pelapor,
  ADD COLUMN nomor_surat     VARCHAR(60)  NULL AFTER jabatan_pelapor,
  ADD COLUMN tgl_surat       DATE         NULL AFTER nomor_surat;

UPDATE laporan_kerusakan lk
  JOIN surat_kerusakan s ON s.id_laporan = lk.id
  SET lk.verify_token    = s.verify_token,
      lk.nama_pelapor    = s.nama_pelapor,
      lk.jabatan_pelapor = s.jabatan_pelapor,
      lk.nomor_surat     = s.nomor_surat,
      lk.tgl_surat       = s.tgl_surat;

DROP TABLE surat_kerusakan;
