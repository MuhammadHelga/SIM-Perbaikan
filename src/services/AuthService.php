<?php

require_once __DIR__ . '/../models/User.php';

class AuthService
{
    private const REMEMBER_COOKIE = 'remember';
    private const REMEMBER_DAYS   = 30;

    private $users;

    public function __construct($conn)
    {
        $this->users = new User($conn);
    }

    /**
     * Cek kredensial ke tabel users. Set session bila berhasil.
     * Kalau $remember true, set cookie "Ingat akun saya".
     */
    public function login(string $username, string $password, bool $remember = false): bool
    {
        $user = $this->users->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->establishSession($user);

        if ($remember) {
            $this->setRememberCookie($user);
        }

        return true;
    }

    /**
     * Auto-login dari cookie "Ingat akun saya" (dipanggil di awal request).
     */
    public function loginByCookie(): bool
    {
        if (!empty($_SESSION['is_logged_in'])) {
            return true;
        }

        $raw = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if ($raw === '') {
            return false;
        }

        $parts = explode(':', $raw);
        if (count($parts) !== 3) {
            $this->clearRememberCookie();
            return false;
        }

        [$userId, $expiry, $signature] = $parts;
        $userId = (int) $userId;
        $expiry = (int) $expiry;

        if ($userId <= 0 || $expiry < time()) {
            $this->clearRememberCookie();
            return false;
        }

        $user = $this->users->getById($userId);
        if (!$user) {
            $this->clearRememberCookie();
            return false;
        }

        if (!hash_equals($this->rememberSignature($user, $expiry), $signature)) {
            $this->clearRememberCookie();
            return false;
        }

        $this->establishSession($user);

        return true;
    }

    public function logout(): void
    {
        $this->clearRememberCookie();

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function establishSession(array $user): void
    {
        $_SESSION['is_logged_in'] = true;
        $_SESSION['user_id']      = (int) $user['id'];
        $_SESSION['public_id']    = $user['public_id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama']         = $user['nama'];
        $_SESSION['role']         = $user['role'];
    }

    private function rememberSignature(array $user, int $expiry): string
    {
        return hash_hmac(
            'sha256',
            $user['id'] . ':' . $expiry . ':' . $user['password_hash'],
            APP_SECRET
        );
    }

    private function setRememberCookie(array $user): void
    {
        $expiry = time() + self::REMEMBER_DAYS * 86400;
        $value  = $user['id'] . ':' . $expiry . ':' . $this->rememberSignature($user, $expiry);

        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires'  => $expiry,
            'path'     => $this->cookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true, // aktifkan bila situs sudah HTTPS
        ]);
    }

    private function clearRememberCookie(): void
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }

        setcookie(self::REMEMBER_COOKIE, '', [
            'expires'  => time() - 3600,
            'path'     => $this->cookiePath(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }

    private function cookiePath(): string
    {
        return (defined('BASE_URL') && BASE_URL !== '') ? BASE_URL : '/';
    }
}
