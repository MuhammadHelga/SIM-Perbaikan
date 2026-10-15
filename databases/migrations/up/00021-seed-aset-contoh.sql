-- @description: Seed aset barang contoh (unit fisik) agar alur laporan bisa dicoba
--
-- Sejak aturan "wajib pilih unit aset", instalasi baru tidak bisa membuat
-- laporan sampai ada aset terdaftar. Migrasi ini menambah beberapa aset contoh.
--
-- Ditautkan lewat NAMA ruangan & barang (bukan id) supaya aman walau id berbeda.
-- Baris yang ruangan/barangnya tidak ada otomatis dilewati (JOIN).
INSERT INTO aset_barang (id_ruangan, id_barang, serial_number, tipe)
SELECT r.id, b.id, v.serial, v.tipe
FROM (
    SELECT 'Radiologi' AS ruang, 'Komputer' AS barang, 'RAD-PC-01'  AS serial, 'Dell Optiplex 3080'  AS tipe
    UNION ALL SELECT 'Radiologi',   'Komputer', 'RAD-PC-02',  'Dell Optiplex 3090'
    UNION ALL SELECT 'Radiologi',   'Printer',  'RAD-PRN-01', 'Epson L3110'
    UNION ALL SELECT 'Admisi',      'Komputer', 'ADM-PC-01',  'HP ProDesk 400'
    UNION ALL SELECT 'UGD',         'Komputer', 'UGD-PC-01',  'Lenovo ThinkCentre'
    UNION ALL SELECT 'POLI',        'Komputer', 'POLI-PC-01', 'HP ProDesk 400'
    UNION ALL SELECT 'Laboratorium','Printer',  'LAB-PRN-01', 'Canon G2010'
) AS v
JOIN ruangan r ON r.nama_ruangan = v.ruang
JOIN barang  b ON b.nama_barang  = v.barang;
