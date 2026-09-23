<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Ruangan.php';

class RuanganController
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
}