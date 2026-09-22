// src/controllers/userController.php

class UserController {
    
    // Method untuk menampilkan dashboard
    public function index() {
        require_once __DIR__ . '/../views/dashboard/screens/DashboardView.php';
    }

    // Method untuk menampilkan laporan kegiatan
    public function laporan() {
        // Kamu bisa mengambil data laporan dari Service/Repository di sini nanti
        
        // Panggil file view laporan
        require_once __DIR__ . '/../views/laporan/screens/laporanView.php';
    }
}