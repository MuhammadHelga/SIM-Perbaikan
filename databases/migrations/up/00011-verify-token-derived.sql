-- @description: Token verifikasi jadi turunan HMAC; DB hanya menyimpan verify_uid (non-rahasia)
--
-- Token yang dipakai di QR = "<uid>.<hmac_sha256(uid, APP_SECRET)>".
-- Uid BUKAN rahasia (seperti username); tanpa APP_SECRET token tidak bisa dipalsukan
-- maupun dibuat ulang, sehingga dump database saja tidak cukup untuk memakai link.
-- Karena token bisa dihitung ulang, QR tetap bisa ditampilkan saat cetak ulang.
ALTER TABLE surat_kerusakan
  DROP INDEX uk_surat_token,
  CHANGE COLUMN verify_token verify_uid CHAR(32) NOT NULL,
  ADD UNIQUE KEY uk_surat_uid (verify_uid);
