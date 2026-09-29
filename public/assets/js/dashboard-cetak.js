/**
 * Grafik untuk halaman Cetak Dashboard (/dashboard/cetak).
 *
 * Konfigurasi, warna, dan plugin di file ini sengaja SALINAN dari
 * public/assets/js/dashboard.js agar hasil cetak grafiknya identik dengan
 * dashboard. Bedanya hanya: palet warna ditulis mati (tidak membaca tema)
 * supaya hasil cetak selalu terang, dan animasi dimatikan supaya canvas
 * selesai digambar sebelum dialog print dibuka.
 */
(function () {
    'use strict';

    const GRID = '#f1f5f9';
    const TEXT = '#64748b';
    const MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const FONT = 'Poppins, system-ui, sans-serif';

    const PALETTE = ['#2563eb', '#38bdf8', '#f59e0b', '#ef4444', '#10b981', '#64748b', '#8b5cf6', '#14b8a6', '#f97316', '#0ea5e9'];
    const COMPARISON_PALETTE = ['#2563eb', '#ea580c', '#64748b', '#ca8a04', '#0ea5e9', '#65a30d', '#1e3a8a', '#92400e', '#6b21a8', '#db2777'];
    const PIE_COLORS = ['#4472c4', '#ed7d31', '#a5a5a5', '#ffc000', '#5b9bd5', '#70ad47', '#264478', '#9e480e', '#997300', '#255e91', '#43682b', '#8064a2'];
    const PIE_SIDE_COLORS = ['#31548f', '#b85b24', '#777777', '#c18f00', '#3c729f', '#4e7a32', '#1a3053', '#6e320a', '#735600', '#194267', '#304a1e', '#594674'];

    function readData() {
        const fallback = { monthly: {}, byBarang: [], monthlyByBarang: [] };
        const el = document.getElementById('dashboardCetakDataJson');
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent) || fallback;
        } catch (e) {
            return fallback;
        }
    }

    function series(monthly, key) {
        return MONTHS.map(function (_, i) {
            return Number((monthly[key] || {})[i + 1] || 0);
        });
    }

    /* ===== Tren Laporan dan Penyelesaian (bar) ===== */
    function renderBarChart(data) {
        const canvas = document.getElementById('barChart');
        if (!canvas) return null;

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: MONTHS,
                datasets: [
                    { label: 'Masuk',   data: series(data.monthly, 'masuk'),   backgroundColor: '#818cf8', borderRadius: 4 },
                    { label: 'Pending', data: series(data.monthly, 'pending'), backgroundColor: '#ef4444', borderRadius: 4 },
                    { label: 'Proses',  data: series(data.monthly, 'proses'),  backgroundColor: '#f59e0b', borderRadius: 4 },
                    { label: 'Selesai', data: series(data.monthly, 'selesai'), backgroundColor: '#4ade80', borderRadius: 4 }
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
                        labels: {
                            color: TEXT,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: GRID }, ticks: { color: TEXT } },
                    x: { grid: { display: false }, ticks: { color: TEXT } }
                }
            }
        });
    }

    /* ===== Distribusi Perangkat (doughnut) ===== */
    function renderDoughnutChart(data) {
        const canvas = document.getElementById('doughnutChart');
        if (!canvas) return null;

        const byBarang = Array.isArray(data.byBarang) ? data.byBarang : [];
        const labels = byBarang.length ? byBarang.map(function (b) { return b.nama; }) : ['Belum ada data'];
        const values = byBarang.length ? byBarang.map(function (b) { return Number(b.jumlah) || 0; }) : [1];
        const colors = byBarang.length
            ? labels.map(function (_, i) { return PALETTE[i % PALETTE.length]; })
            : ['#e2e8f0'];
        const totalKerusakan = byBarang.length
            ? values.reduce(function (total, jumlah) { return total + jumlah; }, 0)
            : 0;

        return new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: colors,
                    borderWidth: 0,
                    hoverOffset: 12
                }]
            },
            plugins: [{
                id: 'doughnutCenterText',
                afterDraw: function (chart) {
                    const chartArea = chart.chartArea;
                    if (!chartArea) return;

                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;
                    const context = chart.ctx;

                    context.save();
                    context.textAlign = 'center';
                    context.textBaseline = 'middle';
                    context.fillStyle = TEXT;
                    context.font = '500 12px ' + FONT;
                    context.fillText('Total Kerusakan', centerX, centerY - 12);
                    context.font = '700 24px ' + FONT;
                    context.fillText(totalKerusakan.toLocaleString('id-ID'), centerX, centerY + 14);
                    context.restore();
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: TEXT,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    /* ===== Perbandingan Kerusakan per Bulan per Barang (bar) ===== */
    function renderMonthlyItemsChart(data) {
        const canvas = document.getElementById('monthlyItemsChart');
        if (!canvas) return null;

        const monthlyItems = Array.isArray(data.monthlyByBarang) ? data.monthlyByBarang : [];
        const datasets = monthlyItems.map(function (item, index) {
            return {
                label: item.nama,
                data: series(item, 'bulanan'),
                backgroundColor: COMPARISON_PALETTE[index % COMPARISON_PALETTE.length],
                borderRadius: 2
            };
        });

        return new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: MONTHS,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: TEXT, usePointStyle: true, boxWidth: 10, padding: 16 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: TEXT, precision: 0 },
                        grid: { color: GRID }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: TEXT }
                    }
                }
            }
        });
    }

    /* ===== Persentase Perbaikan per Bulan (pie 3D) ===== */
    function renderMonthlyPieChart(data) {
        const canvas = document.getElementById('monthlyPieChart');
        if (!canvas) return null;

        const monthlyRepairCounts = series(data.monthly, 'selesai');
        const totalRepairs = monthlyRepairCounts.reduce(function (total, count) { return total + count; }, 0);
        const pieLabels = totalRepairs > 0 ? MONTHS : ['Belum ada data'];
        const pieData = totalRepairs > 0 ? monthlyRepairCounts : [1];
        const activePieColors = totalRepairs > 0 ? PIE_COLORS : ['#cbd5e1'];

        const monthlyPiePlugin = {
            id: 'monthlyPie3d',
            beforeDatasetsDraw: function (chart) {
                const arcs = chart.getDatasetMeta(0).data;
                if (!arcs.length) return;

                const context = chart.ctx;
                const depth = 14;
                context.save();

                for (let offset = depth; offset > 0; offset--) {
                    arcs.forEach(function (arc, index) {
                        if (!arc.circumference) return;

                        const { x, y, startAngle, endAngle, outerRadius } = arc;
                        context.beginPath();
                        context.moveTo(x, y + offset);
                        context.ellipse(x, y + offset, outerRadius, outerRadius * 0.72, 0, startAngle, endAngle);
                        context.closePath();
                        context.fillStyle = pieLabels.length > 1 ? PIE_SIDE_COLORS[index] : '#94a3b8';
                        context.fill();
                    });
                }

                arcs.forEach(function (arc, index) {
                    if (!arc.circumference) return;

                    const { x, y, startAngle, endAngle, outerRadius } = arc;
                    context.beginPath();
                    context.moveTo(x, y);
                    context.ellipse(x, y, outerRadius, outerRadius * 0.72, 0, startAngle, endAngle);
                    context.closePath();
                    context.fillStyle = activePieColors[index];
                    context.fill();
                });
                context.restore();
            },
            afterDatasetsDraw: function (chart) {
                if (!totalRepairs) return;

                const context = chart.ctx;
                const arcs = chart.getDatasetMeta(0).data;
                context.save();
                context.fillStyle = '#ffffff';
                context.font = '600 11px ' + FONT;
                context.textAlign = 'center';
                context.textBaseline = 'middle';

                arcs.forEach(function (arc, index) {
                    const percentage = monthlyRepairCounts[index] / totalRepairs * 100;
                    if (!arc.circumference || percentage < 3) return;

                    const angle = (arc.startAngle + arc.endAngle) / 2;
                    const radius = arc.outerRadius * 0.63;
                    const x = arc.x + Math.cos(angle) * radius;
                    const y = arc.y + Math.sin(angle) * radius * 0.72;
                    context.fillText(percentage.toFixed(1) + '%', x, y);
                });
                context.restore();
            }
        };

        return new Chart(canvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: 'rgba(0, 0, 0, 0)',
                    borderColor: 'rgba(0, 0, 0, 0)',
                    borderWidth: 0,
                    hoverOffset: 0
                }]
            },
            plugins: [monthlyPiePlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: TEXT,
                            usePointStyle: true,
                            boxWidth: 10,
                            padding: 10,
                            generateLabels: function (chart) {
                                return chart.data.labels.map(function (label, index) {
                                    return {
                                        text: label,
                                        fontColor: TEXT,
                                        fillStyle: activePieColors[index],
                                        strokeStyle: activePieColors[index],
                                        lineWidth: 0,
                                        index: index
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (!totalRepairs) return 'Belum ada data';
                                const percentage = context.parsed / totalRepairs * 100;
                                return context.label + ': ' + percentage.toFixed(1) + '% (' + context.parsed + ')';
                            }
                        }
                    }
                }
            }
        });
    }

    function render() {
        const data = readData();
        renderBarChart(data);
        renderDoughnutChart(data);
        renderMonthlyItemsChart(data);
        renderMonthlyPieChart(data);
    }

    function autoPrint() {
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
