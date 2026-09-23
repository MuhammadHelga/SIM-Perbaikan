<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/LaporanKerusakan.php';

class LaporanKerusakanController
{
    private $laporankerusakan;

    public function __construct($conn)
    {
        $this->laporankerusakan = new Laporan($conn);
    }

    public function index()
    {
        return $this->laporankerusakan->getAll();
    }
}