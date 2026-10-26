-- @description: Simpan transisi status penanganan secara terstruktur pada riwayat
--
-- avgServiceDurationDays() sebelumnya menebak status "Selesai" dari teks detail
-- (LIKE '%-> Selesai%'). Kolom status_lama/status_baru membuat metrik itu tidak
-- lagi bergantung pada wording, dan memudahkan audit perubahan status.

ALTER TABLE laporan_riwayat
  ADD COLUMN status_lama ENUM('Pending','Proses','Selesai') NULL AFTER aksi,
  ADD COLUMN status_baru ENUM('Pending','Proses','Selesai') NULL AFTER status_lama,
  ADD INDEX idx_riwayat_status (laporan_id, status_baru, created_at);

-- Backfill riwayat lama: status akhir ("baru") dari teks detail.
UPDATE laporan_riwayat
SET status_baru = CASE
  WHEN detail LIKE '%Status penanganan:%→ Pending%'  THEN 'Pending'
  WHEN detail LIKE '%Status penanganan:%→ Proses%'   THEN 'Proses'
  WHEN detail LIKE '%Status penanganan:%→ Selesai%'  THEN 'Selesai'
  WHEN detail LIKE 'Laporan dibuat dengan status Pending%'  THEN 'Pending'
  WHEN detail LIKE 'Laporan dibuat dengan status Proses%'   THEN 'Proses'
  WHEN detail LIKE 'Laporan dibuat dengan status Selesai%'  THEN 'Selesai'
END
WHERE status_baru IS NULL
  AND (
    detail LIKE '%Status penanganan:%→ %'
    OR detail LIKE 'Laporan dibuat dengan status %'
  );

-- Backfill status awal ("lama") untuk baris perubahan status.
UPDATE laporan_riwayat
SET status_lama = CASE
  WHEN detail LIKE '%Status penanganan: Pending →%' THEN 'Pending'
  WHEN detail LIKE '%Status penanganan: Proses →%'  THEN 'Proses'
  WHEN detail LIKE '%Status penanganan: Selesai →%' THEN 'Selesai'
END
WHERE status_lama IS NULL
  AND aksi = 'Laporan diperbarui'
  AND detail LIKE '%Status penanganan:%→ %';

-- status_baru yang tetap NULL berarti baris riwayat itu bukan transisi status.
