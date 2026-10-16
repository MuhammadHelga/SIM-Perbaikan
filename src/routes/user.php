<?php

/**
 * Rute kelola user (khusus admin).
 */

return function (Router $router, mysqli $conn, string $basePath, AuthService $authService): void {
    // Aksi dinamis (wajib POST + CSRF): /user/hapus/{id}
    $router->any('/user/hapus/{id:\d+}', function (array $params) use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) {
            flash('error', 'Permintaan tidak valid. Silakan coba lagi.');
            redirect($basePath . '/user');
        }

        $id = (int) $params['id'];

        require_once __DIR__ . '/../controllers/userController.php';
        $userController = new userController($conn);
        $target = $userController->show($id);

        if (!$target) {
            flash('error', 'User tidak ditemukan.');
        } elseif ($id === (int) ($_SESSION['user_id'] ?? 0)) {
            flash('error', 'Tidak bisa menghapus akun sendiri.');
        } elseif ($target['role'] === 'admin' && $userController->countAdmins() <= 1) {
            flash('error', 'Tidak bisa menghapus: harus ada minimal satu IT.');
        } else {
            $userController->destroy($id);
            flash('success', 'User "' . $target['username'] . '" berhasil dihapus.');
        }

        redirect($basePath . '/user');
    });

    $router->any('/user/simpan', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrfValid()) {
                flash('error', 'Sesi tidak valid. Silakan coba lagi.');
                redirect($basePath . '/user');
            }

            require_once __DIR__ . '/../controllers/userController.php';
            $userController = new userController($conn);

            $id       = (int) ($_POST['id'] ?? 0);
            $nama     = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 100);
            $jabatan  = mb_substr(trim((string) ($_POST['jabatan'] ?? '')), 0, 100);
            $username = mb_substr(trim((string) ($_POST['username'] ?? '')), 0, 50);
            $password = (string) ($_POST['password'] ?? '');
            $role     = (string) ($_POST['role'] ?? '');

            if ($nama === '' || $username === '' || !in_array($role, ['admin', 'teknisi', 'kabag'], true)) {
                flash('error', 'Nama, username, dan role wajib diisi dengan benar.');
            } elseif ($userController->usernameExists($username, $id)) {
                flash('error', 'Username "' . $username . '" sudah dipakai user lain.');
            } elseif ($id === 0 && $password === '') {
                flash('error', 'Password wajib diisi untuk user baru.');
            } elseif ($password !== '' && mb_strlen($password) < 6) {
                flash('error', 'Password minimal 6 karakter.');
            } else {
                try {
                    if ($id > 0) {
                        $existing = $userController->show($id);
                        if (!$existing) {
                            flash('error', 'User tidak ditemukan.');
                            redirect($basePath . '/user');
                        }

                        $userController->update($id, $nama, $jabatan, $username, $role);

                        if ($password !== '') {
                            $userController->updatePassword($id, $password);
                        }

                        flash('success', 'User "' . $username . '" berhasil diperbarui.');
                    } else {
                        $userController->store($nama, $jabatan, $username, $password, $role);
                        flash('success', 'User "' . $username . '" berhasil ditambahkan.');
                    }
                } catch (mysqli_sql_exception $e) {
                    flash('error', 'Gagal menyimpan: username mungkin sudah dipakai.');
                }
            }
        }

        redirect($basePath . '/user');
    });

    $router->any('/user', function () use ($conn, $basePath) {
        requireRole($basePath, ['admin']);

        require_once __DIR__ . '/../controllers/userController.php';
        $userList = (new userController($conn))->index();

        require __DIR__ . '/../views/user/screens/UserView.php';
    });
};
