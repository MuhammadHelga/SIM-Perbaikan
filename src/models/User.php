<?php

class User
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function findByUsername($username)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, public_id, nama, jabatan, username, password_hash, role
             FROM users
             WHERE username = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, public_id, nama, jabatan, username, password_hash, role
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll()
    {
        $stmt = $this->conn->prepare(
            "SELECT id, public_id, nama, jabatan, username, role, created_at
             FROM users
             ORDER BY nama ASC, username ASC"
        );

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($nama, $jabatan, $username, $passwordHash, $role)
    {
        $publicId = $this->generatePublicId();

        $stmt = $this->conn->prepare(
            "INSERT INTO users (public_id, nama, jabatan, username, password_hash, role)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param("ssssss", $publicId, $nama, $jabatan, $username, $passwordHash, $role);

        return $stmt->execute();
    }

    public function update($id, $nama, $jabatan, $username, $role)
    {
        $stmt = $this->conn->prepare(
            "UPDATE users
             SET nama = ?, jabatan = ?, username = ?, role = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssssi", $nama, $jabatan, $username, $role, $id);

        return $stmt->execute();
    }

    public function updatePassword($id, $passwordHash)
    {
        $stmt = $this->conn->prepare(
            "UPDATE users
             SET password_hash = ?
             WHERE id = ?"
        );

        $stmt->bind_param("si", $passwordHash, $id);

        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }

    public function usernameExists($username, $exceptId = 0)
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row) {
            return false;
        }

        return (int) $row['id'] !== (int) $exceptId;
    }

    public function countAdmins()
    {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS j FROM users WHERE role = 'admin'"
        );

        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['j'];
    }

    private function generatePublicId()
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
