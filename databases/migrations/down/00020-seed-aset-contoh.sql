-- @description: Rollback seed aset contoh (hapus aset yang di-seed)
DELETE FROM aset_barang WHERE serial_number IN (
  'RAD-PC-01', 'RAD-PC-02', 'RAD-PRN-01',
  'ADM-PC-01', 'UGD-PC-01', 'POLI-PC-01', 'LAB-PRN-01'
);
