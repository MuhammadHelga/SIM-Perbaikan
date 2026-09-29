-- @description: Hapus seed awal (kebalikan dari up/00001)

DELETE FROM barang  WHERE kode_barang IN ('BRG-01','BRG-02','BRG-03','BRG-04','BRG-05','BRG-06','BRG-07');
DELETE FROM ruangan WHERE kode_ruangan IN ('POLI','UGD','MJKN','RADIOLOGI','LAB','KASIR');
DELETE FROM users   WHERE username = 'admin';
