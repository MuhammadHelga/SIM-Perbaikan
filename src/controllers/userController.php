<?php

require_once __DIR__ . '/../models/User.php';

class userController
{
    private $user;

    public function __construct($conn)
    {
        $this->user = new User($conn);
    }

    public function index()
    {
        return $this->user->getAll();
    }

    public function show($id)
    {
        return $this->user->getById($id);
    }

    public function store($nama, $jabatan, $username, $password, $role)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return $this->user->create($nama, $jabatan, $username, $passwordHash, $role);
    }

    public function update($id, $nama, $jabatan, $username, $role)
    {
        return $this->user->update($id, $nama, $jabatan, $username, $role);
    }

    public function updatePassword($id, $password)
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        return $this->user->updatePassword($id, $passwordHash);
    }

    public function destroy($id)
    {
        return $this->user->delete($id);
    }

    public function usernameExists($username, $exceptId = 0)
    {
        return $this->user->usernameExists($username, $exceptId);
    }

    public function countAdmins()
    {
        return $this->user->countAdmins();
    }
}
