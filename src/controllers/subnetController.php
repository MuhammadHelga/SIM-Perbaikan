<?php

require_once __DIR__ . '/../models/Subnet.php';

class subnetController
{
    private $subnet;

    public function __construct($conn)
    {
        $this->subnet = new Subnet($conn);
    }

    public function index()
    {
        return $this->subnet->getAll();
    }

    public function byCidr($cidr)
    {
        return $this->subnet->getByCidr($cidr);
    }

    public function show($id)
    {
        return $this->subnet->getById($id);
    }

    public function store($cidr, $prefix, $gateway, $mask, $keterangan)
    {
        return $this->subnet->create($cidr, $prefix, $gateway, $mask, $keterangan);
    }

    public function update($id, $cidr, $prefix, $gateway, $mask, $keterangan)
    {
        return $this->subnet->update($id, $cidr, $prefix, $gateway, $mask, $keterangan);
    }

    public function destroy($id)
    {
        return $this->subnet->delete($id);
    }
}
