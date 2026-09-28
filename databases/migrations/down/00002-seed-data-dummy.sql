-- @description: Hapus 24 data dummy laporan kerusakan (kebalikan dari up/00002)
-- Hanya menghapus baris yang rincian_kerusakannya persis sama dengan seed dummy.

DELETE FROM laporankerusakan WHERE rincian_kerusakan IN (
  'PC hang saat login SIMRS',
  'Printer macet / tidak mau cetak label',
  'Monitor redup dan berkedip',
  'Jaringan VGA tidak terhubung di ruang MJKN',
  'Server kasir sering restart sendiri',
  'AC ruang radiologi tidak dingin',
  'Aplikasi antrian poli tidak sinkron',
  'Komputer IGD triase tidak bisa boot',
  'Print hasil lab bergaris-garis',
  'Komputer kasir 2 lambat dan sering not responding',
  'AC rontgen bocor air',
  'Server PACS storage penuh',
  'Monitor poli jantung resolusi menurun',
  'Wifi IGD tidak stabil sering putus',
  'Komputer analis lab mati mendadak',
  'Printer struk kasir tinta habis terus',
  'Aplikasi rekam medis error saat simpan',
  'PC USG tidak terdeteksi pasien baru',
  'AC poli anak bocor dan tidak dingin',
  'Server backup IGD down',
  'Koneksi jaringan lab kimia terputus',
  'Monitor kasir 1 tidak menyala',
  'Printer rekam medis tidak menarik kertas',
  'PACS gagal menampilkan citra CT scan'
);