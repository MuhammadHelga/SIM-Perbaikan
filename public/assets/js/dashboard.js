document.addEventListener('DOMContentLoaded', function () {
    // 1. Render Bar Chart (Tren Laporan)
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'],
            datasets: [
                {
                    label: 'Masuk',
                    data: [110, 50, 80, 115, 80, 115, 80, 110, 80, 110, 80, 0],
                    backgroundColor: '#818cf8',
                    borderRadius: 4
                },
                {
                    label: 'Selesai',
                    data: [80, 90, 60, 95, 60, 95, 60, 95, 60, 95, 60, 0],
                    backgroundColor: '#4ade80',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', align: 'end' }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Render Doughnut Chart (Distribusi Kategori)
    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: ['Komputer', 'SIMRS', 'Printer', 'Hardware', 'Monitor', 'Lainnya'],
            datasets: [{
                data: [25, 25, 25, 25, 0, 0],
                backgroundColor: ['#2563eb', '#38bdf8', '#f59e0b', '#ef4444', '#10b981', '#64748b']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            },
            cutout: '70%'
        }
    });
});