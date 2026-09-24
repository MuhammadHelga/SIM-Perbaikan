<?php

require_once __DIR__ . '/../models/User.php';

class AuthService
{
    private $users;

    public function __construct($conn)
    {
        $this->users = new User($conn);
    }

    /**
     * Cek kredensial ke tabel users. Set session bila berhasil.
     */
    public function login(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['is_logged_in'] = true;
        $_SESSION['user_id']      = (int) $user['id'];
        $_SESSION['public_id']    = $user['public_id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama']         = $user['nama'];
        $_SESSION['role']         = $user['role'];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
