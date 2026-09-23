<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/LaporanKerusakan.php';

class LaporanKerusakanController
{
    private $laporan;

    public function __construct($conn)
    {
        $this->laporan = new Laporan($conn);
    }

    public function index()
    {
        return $this->laporan->getAll();
    }
}