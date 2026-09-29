/**
 * Grafik untuk halaman Cetak Dashboard (/dashboard/cetak).
 * Chart.js dimuat via CDN di view; palet warna dibuat tetap agar hasil cetak
 * selalu terang dan tidak bergantung tema aplikasi.
 */
(function () {
    'use strict';

    const GRID = '#eef2f7';
    const TEXT = '#64748b';
    const MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];

    const PALETTE = ['#2563eb', '#38bdf8', '#f59e0b', '#ef4444', '#10b981', '#64748b', '#8b5cf6', '#14b8a6', '#f97316', '#0ea5e9'];

    function readData() {
        const el = document.getElementById('dashboardCetakDataJson');
        if (!el) {
            return { monthly: {}, byBarang: [], monthlyByBarang: [] };
        }
        try {
            return JSON.parse(el.textContent) || { monthly: {}, byBarang: [], monthlyByBarang: [] };
        } catch (e) {
            return { monthly: {}, byBarang: [], monthlyByBarang: [] };
        }
    }

    function series(monthly, key) {
        return MONTHS.map(function (_, i) {
            return Number((monthly[key] || {})[i + 1] || 0);
        });
    }

    function renderBarChart(data) {
        const canvas = document.getElementById('barChart');
        if (!canvas) return null;

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: MONTHS,
                datasets: [
                    { label: 'Masuk', data: series(data.monthly, 'masuk'), backgroundColor: '#818cf8', borderRadius: 3 },
                    { label: 'Pending', data: series(data.monthly, 'pending'), backgroundColor: '#ef4444', borderRadius: 3 },
                    { label: 'Proses', data: series(data.monthly, 'proses'), backgroundColor: '#f59e0b', borderRadius: 3 },
                    { label: 'Selesai', data: series(data.monthly, 'selesai'), backgroundColor: '#4ade80', borderRadius: 3 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { color: TEXT, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8 }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: GRID }, ticks: { color: TEXT, precision: 0 } },
                    x: { grid: { display: false }, ticks: { color: TEXT } }
                }
            }
        });
    }

    function renderDoughnutChart(data) {
        const canvas = document.getElementById('doughnutChart');
        if (!canvas) return null;

        const byBarang = Array.isArray(data.byBarang) ? data.byBarang : [];
        const labels = byBarang.length ? byBarang.map(function (b) { return b.nama; }) : ['Belum ada data'];
        const values = byBarang.length ? byBarang.map(function (b) { return Number(b.jumlah) || 0; }) : [1];
        const colors = byBarang.length
            ? labels.map(function (_, i) { return PALETTE[i % PALETTE.length]; })
            : ['#e2e8f0'];
        const total = byBarang.length
            ? values.reduce(function (sum, n) { return sum + n; }, 0)
            : 0;

        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            plugins: [{
                id: 'cetakCenterText',
                afterDraw: function (chart) {
                    const area = chart.chartArea;
                    if (!area) return;

                    const ctx = chart.ctx;
                    const cx = (area.left + area.right) / 2;
                    const cy = (area.top + area.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = TEXT;
                    ctx.font = '500 10px system-ui, sans-serif';
                    ctx.fillText('Total Kerusakan', cx, cy - 10);
                    ctx.font = '700 18px system-ui, sans-serif';
                    ctx.fillText(total.toLocaleString('id-ID'), cx, cy + 11);
                    ctx.restore();
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { color: TEXT, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8 }
                    }
                }
            }
        });
    }

    function render() {
        const data = readData();
        renderBarChart(data);
        renderDoughnutChart(data);
    }

    function autoPrint() {
        if (typeof Chart === 'undefined') {
            window.print();
            return;
        }

        render();

        // Beri jeda singkat supaya canvas benar-benar selesai digambar
        // sebelum dialog print dibuka.
        window.requestAnimationFrame(function () {
            window.setTimeout(function () { window.print(); }, 150);
        });
    }

    if (document.readyState === 'complete') {
        autoPrint();
    } else {
        window.addEventListener('load', autoPrint);
    }
})();
