<?php

require_once __DIR__ . '/../models/AlokasiIp.php';

class alokasiIpController
{
    private $alokasi;

    public function __construct($conn)
    {
        $this->alokasi = new AlokasiIp($conn);
    }

    public function index()
    {
        return $this->alokasi->getAll();
    }

    public function show($id)
    {
        return $this->alokasi->getById($id);
    }

    public function store($subnet, $unit, $lokasi, $hostname, $host_octet, $status = 'online')
    {
        return $this->alokasi->create($subnet, $unit, $lokasi, $hostname, $host_octet, $status);
    }

    public function update($id, $subnet, $unit, $lokasi, $hostname, $host_octet, $status = 'online')
    {
        return $this->alokasi->update($id, $subnet, $unit, $lokasi, $hostname, $host_octet, $status);
    }

    public function destroy($id)
    {
        return $this->alokasi->delete($id);
    }
}
