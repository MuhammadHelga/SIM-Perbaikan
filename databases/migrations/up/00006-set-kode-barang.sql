-- @description: Ganti kode barang menjadi kode singkatan (unik, bukan nama)

UPDATE barang SET kode_barang = 'ALM' WHERE kode_barang = 'ALMED';
UPDATE barang SET kode_barang = 'APL' WHERE kode_barang = 'APLIKASI';
UPDATE barang SET kode_barang = 'FGP' WHERE kode_barang = 'FINGER';
UPDATE barang SET kode_barang = 'HDW' WHERE kode_barang = 'HARDWARE';
UPDATE barang SET kode_barang = 'JRG' WHERE kode_barang = 'JARINGAN';
UPDATE barang SET kode_barang = 'KMP' WHERE kode_barang = 'KOMPUTER';
UPDATE barang SET kode_barang = 'LTP' WHERE kode_barang = 'LAPTOP';
UPDATE barang SET kode_barang = 'LNL' WHERE kode_barang = 'LAIN-LAIN';
UPDATE barang SET kode_barang = 'MPK' WHERE kode_barang = 'MESIN PARKIR';
UPDATE barang SET kode_barang = 'MTR' WHERE kode_barang = 'MONITOR';
UPDATE barang SET kode_barang = 'PRT' WHERE kode_barang = 'PRINTER';
UPDATE barang SET kode_barang = 'SCN' WHERE kode_barang = 'SCANNER';