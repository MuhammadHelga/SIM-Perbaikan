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
            "SELECT id, public_id, nama, username, password_hash, role
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
            "SELECT id, public_id, nama, username, password_hash, role
             FROM users
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }
}
