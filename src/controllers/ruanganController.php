<?php

require_once __DIR__ . '/../models/Ruangan.php';

class ruanganController
{
    private $ruangan;

    public function __construct($conn)
    {
        $this->ruangan = new Ruangan($conn);
    }

    public function index()
    {
        return $this->ruangan->getAll();
    }

    public function show($id)
    {
        return $this->ruangan->getById($id);
    }

    public function store($nama_ruangan)
    {
        return $this->ruangan->create($nama_ruangan);
    }

    public function update($id, $nama_ruangan)
    {
        return $this->ruangan->update($id, $nama_ruangan);
    }

    public function destroy($id)
    {
        return $this->ruangan->delete($id);
    }
}
