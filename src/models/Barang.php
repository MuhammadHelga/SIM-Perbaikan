<?php

class Barang
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // READ
    public function getAll()
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM barang ORDER BY nama_barang ASC"
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    // READ BY ID
    public function getById($id)
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM barang WHERE id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    private function normalizeNama(string $nama): string
    {
        $nama = preg_replace('/\s+/u', ' ', trim($nama));
        if ($nama === null || $nama === '') {
            return '';
        }

        if (function_exists('mb_strtolower') && function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            $nama = mb_strtolower($nama, 'UTF-8');
            $nama = mb_strtoupper(mb_substr($nama, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_substr($nama, 1, null, 'UTF-8');
        } else {
            $nama = ucfirst(strtolower($nama));
        }

        $singkatan = [
            'AC' => 'AC',
            'CCTV' => 'CCTV',
            'CPU' => 'CPU',
            'IP' => 'IP',
            'LCD' => 'LCD',
            'LED' => 'LED',
            'SIMRS' => 'SIMRS',
            'UPS' => 'UPS',
        ];

        return preg_replace_callback(
            '/\b(' . implode('|', array_map('preg_quote', array_keys($singkatan))) . ')\b/iu',
            static function (array $matches) use ($singkatan): string {
                foreach ($singkatan as $key => $value) {
                    if (strcasecmp($matches[0], $key) === 0) {
                        return $value;
                    }
                }
                return $matches[0];
            },
            $nama
        ) ?? $nama;
    }

    // CREATE
    public function create($kode_barang, $nama_barang)
    {
        $kode = ($kode_barang === '' ? null : $kode_barang);
        $nama_barang = $this->normalizeNama((string) $nama_barang);

        $stmt = $this->conn->prepare(
            "INSERT INTO barang (kode_barang, nama_barang) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $kode, $nama_barang);

        return $stmt->execute();
    }

    // UPDATE
    public function update($id, $kode_barang, $nama_barang)
    {
        $kode = ($kode_barang === '' ? null : $kode_barang);
        $nama_barang = $this->normalizeNama((string) $nama_barang);

        $stmt = $this->conn->prepare(
            "UPDATE barang
             SET kode_barang = ?, nama_barang = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $kode, $nama_barang, $id);

        return $stmt->execute();
    }

    // DELETE
    public function delete($id)
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM barang WHERE id = ?"
        );

        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}
