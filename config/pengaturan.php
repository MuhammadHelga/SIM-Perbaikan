<?php
/**
 * Pengaturan aplikasi — teks/data yang sering berubah (kop surat, penandatangan,
 * dsb). Ubah di sini SAJA.
 *
 * Nilai di bawah dipakai oleh tiga tempat sekaligus:
 *   - Preview surat  : components/modals/SuratKerusakanModal.php
 *   - HTML cetak surat: public/assets/js/laporan.js (buildSuratHtml)
 *   - Halaman verifikasi: src/views/laporan/screens/VerifikasiSuratView.php
 */

// Penandatangan kedua (tetap) pada surat kerusakan.
define('SURAT_SIGNER2_LABEL', 'Menyetujui,');
define('SURAT_SIGNER2_JABATAN', 'Kabag. Fasilitas dan Keselamatan');
define('SURAT_SIGNER2_NAMA', 'Budi Hartono, S.Kep., Ners.');
