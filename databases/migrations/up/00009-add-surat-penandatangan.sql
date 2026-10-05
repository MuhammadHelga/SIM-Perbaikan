-- @description: Simpan data penandatangan surat (nama, jabatan, nomor, tanggal surat)
ALTER TABLE laporan_kerusakan
  ADD COLUMN nama_pelapor    VARCHAR(100) NULL AFTER verify_token,
  ADD COLUMN jabatan_pelapor VARCHAR(100) NULL AFTER nama_pelapor,
  ADD COLUMN nomor_surat     VARCHAR(60)  NULL AFTER jabatan_pelapor,
  ADD COLUMN tgl_surat       DATE         NULL AFTER nomor_surat;
