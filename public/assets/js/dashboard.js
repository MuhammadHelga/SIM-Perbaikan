let barChart = null;
let doughnutChart = null;

function themeColor(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

function renderCharts() {
    const data = (function () {
        const el = document.getElementById('dashboardDataJson');
        if (!el) return null;
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    })() || { monthly: { masuk: [], selesai: [] }, byBarang: [] };

    const gridColor = themeColor('--chart-grid', '#f1f5f9');
    const textColor = themeColor('--chart-text', '#64748b');

    const labels  = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const masuk   = labels.map(function (_, i) { return Number((data.monthly.masuk  || {})[i + 1] || 0); });
    const selesai = labels.map(function (_, i) { return Number((data.monthly.selesai || {})[i + 1] || 0); });

    // Bar chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    if (barChart) barChart.destroy();
    barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Masuk',   data: masuk,   backgroundColor: '#818cf8', borderRadius: 4 },
                { label: 'Selesai', data: selesai, backgroundColor: '#4ade80', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', align: 'end', labels: { color: textColor } } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                x: { grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });

    // Doughnut chart
    const byBarang = Array.isArray(data.byBarang) ? data.byBarang : [];
    const palette = ['#2563eb', '#38bdf8', '#f59e0b', '#ef4444', '#10b981', '#64748b', '#8b5cf6', '#14b8a6', '#f97316', '#0ea5e9'];

    const doughnutLabels = byBarang.length ? byBarang.map(function (b) { return b.nama; }) : ['Belum ada data'];
    const doughnutData   = byBarang.length ? byBarang.map(function (b) { return Number(b.jumlah) || 0; }) : [1];
    const doughnutColors = byBarang.length
        ? doughnutLabels.map(function (_, i) { return palette[i % palette.length]; })
        : ['#e2e8f0'];

    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    if (doughnutChart) doughnutChart.destroy();
    doughnutChart = new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: doughnutLabels,
            datasets: [{ data: doughnutData, backgroundColor: doughnutColors }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { color: textColor } } },
            cutout: '70%'
        }
    });
}

/* Dipanggil lagi saat tema diganti (dari app.js) */
window.onThemeChange = function () {
    renderCharts();
};

document.addEventListener('DOMContentLoaded', renderCharts);
