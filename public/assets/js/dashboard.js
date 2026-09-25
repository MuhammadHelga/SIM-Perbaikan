document.addEventListener('DOMContentLoaded', function () {
    const fallback = { monthly: { masuk: [], selesai: [] }, byBarang: [] };
    const data = (function () {
        const el = document.getElementById('dashboardDataJson');
        if (!el) return fallback;
        try { return JSON.parse(el.textContent) || fallback; } catch (e) { return fallback; }
    })();

    const labels  = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const masuk   = labels.map(function (_, i) { return Number((data.monthly.masuk  || {})[i + 1] || 0); });
    const selesai = labels.map(function (_, i) { return Number((data.monthly.selesai || {})[i + 1] || 0); });

    // 1. Render Bar Chart (Tren Laporan)
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Masuk',
                    data: masuk,
                    backgroundColor: '#818cf8',
                    borderRadius: 4
                },
                {
                    label: 'Selesai',
                    data: selesai,
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
    const byBarang = Array.isArray(data.byBarang) ? data.byBarang : [];
    const palette = ['#2563eb', '#38bdf8', '#f59e0b', '#ef4444', '#10b981', '#64748b', '#8b5cf6', '#14b8a6', '#f97316', '#0ea5e9'];

    const doughnutLabels = byBarang.length ? byBarang.map(function (b) { return b.nama; }) : ['Belum ada data'];
    const doughnutData   = byBarang.length ? byBarang.map(function (b) { return Number(b.jumlah) || 0; }) : [1];
    const doughnutColors = byBarang.length
        ? doughnutLabels.map(function (_, i) { return palette[i % palette.length]; })
        : ['#e2e8f0'];

    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: doughnutLabels,
            datasets: [{
                data: doughnutData,
                backgroundColor: doughnutColors
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
