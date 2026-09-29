-- @description: Kembalikan kode ruangan lama & hapus 69 ruangan baru (kebalikan dari up/00003)

-- Kembalikan kode ruangan lama
UPDATE ruangan SET kode_ruangan = 'RADIOLOGI' WHERE kode_ruangan = 'Radiologi';
UPDATE ruangan SET kode_ruangan = 'LAB'        WHERE kode_ruangan = 'Laboratorium';
UPDATE ruangan SET kode_ruangan = 'KASIR'      WHERE kode_ruangan = 'Kasir';

-- Hapus ruangan baru dari seed 00003
DELETE FROM ruangan WHERE kode_ruangan IN (
  'Admisi','Akuntansi','Apotek RJ','BPJS Center','Iman','Keuangan',
  'Kabid. Admed','Kainst. Farmasi','Kamar Operasi','Kerakyatan','MPP',
  'Parkir','Perinatologi','Personalia','POSKO','PPK1','Satpam',
  'Sekretariat','Server','Syukur','TPPRJ','Apotek RI','Bid. Keperawatan',
  'Hemodialisis','Ihsan','Kabag. Akuntansi','Kabag. Faskes','Kabag. Keuangan',
  'Kabid. Pemkes','Logmed','Penagihan','RM','Fisioterapi','Gizi',
  'Gotong Royong','Isolasi','Kabid. Jangmed','Kebangsaan','Koperasi',
  'Loundry','Pengadaan','Persatuan','Rekening','Sabar','Apotek Cahaya',
  'Diagnostik','Driver','ICU','Kemanusiaan','Pelaporan','PPI','R. Anak',
  'Rujukan','Aulia','K3','Kabag. Umum','PKRS','Bank Darah',
  'Kabag. Administrasi','IGD','CSSD','PSIKOLOG','Playgroup','Keshling',
  'IT','Logpen','Tekbang','Rehabmedik','TB Dot','Baksos','Pendaftaran'
);