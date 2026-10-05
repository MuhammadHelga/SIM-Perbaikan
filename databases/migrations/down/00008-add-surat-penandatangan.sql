-- @description: Rollback 00009 — hapus data penandatangan surat
ALTER TABLE laporan_kerusakan
  DROP COLUMN nama_pelapor,
  DROP COLUMN jabatan_pelapor,
  DROP COLUMN nomor_surat,
  DROP COLUMN tgl_surat;
