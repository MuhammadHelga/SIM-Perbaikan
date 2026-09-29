let barChart = null;
let doughnutChart = null;
let monthlyItemsChart = null;
let monthlyPieChart = null;

// Token render: membatalkan "forced animation" dari render sebelumnya.
let chartsRenderToken = 0;

function themeColor(name, fallback) {
    const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
}

function renderCharts(animateFirst) {
    chartsRenderToken++;
    const data = (function () {
        const el = document.getElementById('dashboardDataJson');
        if (!el) return null;
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    })() || { monthly: { masuk: [], selesai: [], pending: [], proses: [] }, byBarang: [], monthlyByBarang: [] };

    const gridColor = themeColor('--chart-grid', '#f1f5f9');
    const textColor = themeColor('--chart-text', '#64748b');

    const labels  = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const masuk   = labels.map(function (_, i) { return Number((data.monthly.masuk   || {})[i + 1] || 0); });
    const pending = labels.map(function (_, i) { return Number((data.monthly.pending || {})[i + 1] || 0); });
    const proses  = labels.map(function (_, i) { return Number((data.monthly.proses  || {})[i + 1] || 0); });
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
                { label: 'Pending', data: pending, backgroundColor: '#ef4444', borderRadius: 4 },
                { label: 'Proses',  data: proses,  backgroundColor: '#f59e0b', borderRadius: 4 },
                { label: 'Selesai', data: selesai, backgroundColor: '#4ade80', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        boxHeight: 8
                    }
                }
            },
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
    const totalKerusakan = byBarang.length
        ? doughnutData.reduce(function (total, jumlah) { return total + jumlah; }, 0)
        : 0;

    const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
    if (doughnutChart) doughnutChart.destroy();
    doughnutChart = new Chart(ctxDoughnut, {
        type: 'doughnut',
        data: {
            labels: doughnutLabels,
            datasets: [{
                data: doughnutData,
                backgroundColor: doughnutColors,
                borderColor: doughnutColors,
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
                context.fillStyle = textColor;
                context.font = '500 12px Poppins, sans-serif';
                context.fillText('Total Kerusakan', centerX, centerY - 12);
                context.font = '700 24px Poppins, sans-serif';
                context.fillText(totalKerusakan.toLocaleString('id-ID'), centerX, centerY + 14);
                context.restore();
            }
        }],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: textColor,
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

    const monthLabels = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const monthlyItems = Array.isArray(data.monthlyByBarang) ? data.monthlyByBarang : [];
    const comparisonPalette = ['#2563eb', '#ea580c', '#64748b', '#ca8a04', '#0ea5e9', '#65a30d', '#1e3a8a', '#92400e', '#6b21a8', '#db2777'];
    const monthlyItemDatasets = monthlyItems.map(function (item, index) {
        return {
            label: item.nama,
            data: monthLabels.map(function (_, monthIndex) {
                return Number((item.bulanan || {})[monthIndex + 1] || 0);
            }),
            backgroundColor: comparisonPalette[index % comparisonPalette.length],
            borderRadius: 2
        };
    });

    const ctxMonthlyItems = document.getElementById('monthlyItemsChart').getContext('2d');
    if (monthlyItemsChart) monthlyItemsChart.destroy();
    monthlyItemsChart = new Chart(ctxMonthlyItems, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: monthlyItemDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: textColor, usePointStyle: true, boxWidth: 10, padding: 16 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor, precision: 0 },
                    grid: { color: gridColor }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor }
                }
            }
        }
    });

    const pieMonthLabels = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGS', 'SEP', 'OKT', 'NOV', 'DES'];
    const monthlyRepairCounts = pieMonthLabels.map(function (_, monthIndex) {
        return Number((data.monthly.selesai || {})[monthIndex + 1] || 0);
    });
    const pieColors = ['#4472c4', '#ed7d31', '#a5a5a5', '#ffc000', '#5b9bd5', '#70ad47', '#264478', '#9e480e', '#997300', '#255e91', '#43682b', '#8064a2'];
    const pieSideColors = ['#31548f', '#b85b24', '#777777', '#c18f00', '#3c729f', '#4e7a32', '#1a3053', '#6e320a', '#735600', '#194267', '#304a1e', '#594674'];
    const totalRepairs = monthlyRepairCounts.reduce(function (total, count) { return total + count; }, 0);
    const pieLabels = totalRepairs > 0 ? pieMonthLabels : ['Belum ada data'];
    const pieData = totalRepairs > 0 ? monthlyRepairCounts : [1];
    const activePieColors = totalRepairs > 0 ? pieColors : ['#cbd5e1'];

    // Animasi "angkat" slice pie saat hover (per-slice, ease-out).
    const pieLift = { current: [], target: [], running: false };
    const PIE_LIFT_PX = 14;

    function pieLiftEnsure(n) {
        while (pieLift.current.length < n) {
            pieLift.current.push(0);
            pieLift.target.push(0);
        }
    }

    function pieSliceShift(arc, index) {
        const off = pieLift.current[index] || 0;
        if (!off) return { dx: 0, dy: 0 };
        const mid = (arc.startAngle + arc.endAngle) / 2;
        return { dx: Math.cos(mid) * off, dy: Math.sin(mid) * off };
    }

    function pieLiftTick(chart) {
        if (chart !== monthlyPieChart) { pieLift.running = false; return; }

        let moving = false;
        for (let i = 0; i < pieLift.current.length; i++) {
            const current = pieLift.current[i];
            const target  = pieLift.target[i];
            if (Math.abs(target - current) > 0.2) {
                pieLift.current[i] = current + (target - current) * 0.22;
                moving = true;
            } else {
                pieLift.current[i] = target;
            }
        }

        chart.draw();
        if (moving) {
            requestAnimationFrame(function () { pieLiftTick(chart); });
        } else {
            pieLift.running = false;
        }
    }

    // Selaraskan target angkat dengan slice yang sedang aktif (hover), lalu jalankan animasi.
    function pieLiftSync(chart) {
        const arcs = chart.getDatasetMeta(0).data;
        pieLiftEnsure(arcs.length);

        const active = (typeof chart.getActiveElements === 'function') ? chart.getActiveElements() : [];
        const hovered = active.length ? active[0].index : -1;

        let changed = false;
        for (let i = 0; i < arcs.length; i++) {
            const target = (i === hovered) ? PIE_LIFT_PX : 0;
            if (pieLift.target[i] !== target) {
                pieLift.target[i] = target;
                changed = true;
            }
        }

        if (changed && !pieLift.running) {
            pieLift.running = true;
            requestAnimationFrame(function () { pieLiftTick(chart); });
        }
    }

    const monthlyPiePlugin = {
        id: 'monthlyPie3d',
        beforeDatasetsDraw: function (chart) {
            const arcs = chart.getDatasetMeta(0).data;
            if (!arcs.length) return;

            pieLiftSync(chart);

            const context = chart.ctx;
            const depth = 14;
            context.save();

            for (let offset = depth; offset > 0; offset--) {
                arcs.forEach(function (arc, index) {
                    if (!arc.circumference) return;

                    const { x, y, startAngle, endAngle, outerRadius } = arc;
                    const shift = pieSliceShift(arc, index);
                    context.beginPath();
                    context.moveTo(x + shift.dx, y + offset + shift.dy);
                    context.ellipse(x + shift.dx, y + offset + shift.dy, outerRadius, outerRadius * 0.72, 0, startAngle, endAngle);
                    context.closePath();
                    context.fillStyle = pieLabels.length > 1 ? pieSideColors[index] : '#94a3b8';
                    context.fill();
                });
            }

            arcs.forEach(function (arc, index) {
                if (!arc.circumference) return;

                const { x, y, startAngle, endAngle, outerRadius } = arc;
                const shift = pieSliceShift(arc, index);
                context.beginPath();
                context.moveTo(x + shift.dx, y + shift.dy);
                context.ellipse(x + shift.dx, y + shift.dy, outerRadius, outerRadius * 0.72, 0, startAngle, endAngle);
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
            context.font = '600 11px Poppins, sans-serif';
            context.textAlign = 'center';
            context.textBaseline = 'middle';

            arcs.forEach(function (arc, index) {
                const percentage = monthlyRepairCounts[index] / totalRepairs * 100;
                if (!arc.circumference || percentage < 3) return;

                const angle = (arc.startAngle + arc.endAngle) / 2;
                const radius = arc.outerRadius * 0.63;
                const shift = pieSliceShift(arc, index);
                const x = arc.x + shift.dx + Math.cos(angle) * radius;
                const y = arc.y + shift.dy + Math.sin(angle) * radius * 0.72;
                context.fillText(percentage.toFixed(1) + '%', x, y);
            });
            context.restore();
        }
    };

    const ctxMonthlyPie = document.getElementById('monthlyPieChart').getContext('2d');
    if (monthlyPieChart) monthlyPieChart.destroy();
    monthlyPieChart = new Chart(ctxMonthlyPie, {
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
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        usePointStyle: true,
                        boxWidth: 10,
                        padding: 10,
                        generateLabels: function (chart) {
                            return chart.data.labels.map(function (label, index) {
                                return {
                                    text: label,
                                    fontColor: textColor,
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

    // Kembalikan posisi slice saat kursor keluar dari area pie.
    ctxMonthlyPie.canvas.addEventListener('mouseleave', function () {
        for (let i = 0; i < pieLift.target.length; i++) {
            pieLift.target[i] = 0;
        }
        if (!pieLift.running && pieLift.current.length) {
            pieLift.running = true;
            requestAnimationFrame(function () { pieLiftTick(monthlyPieChart); });
        }
    });

    // Chart.js (responsive) bisa memotong animasi pertama karena auto-resize.
    // Paksa animasi tumbuh dari nol hanya pada render pertama (bukan saat ganti tema),
    // dan hanya bila tab terlihat — agar data tidak "terjebak nol" di tab latar.
    if (animateFirst) {
        animateChartsFromZero([barChart, doughnutChart, monthlyItemsChart, monthlyPieChart]);
    }
}

/*
 * Paksa animasi tumbuh dari nol untuk sekumpulan chart.
 * Dilewati bila tab tidak terlihat atau pengguna minta minim animasi.
 */
function animateChartsFromZero(charts) {
    if (document.visibilityState !== 'visible') return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const token = chartsRenderToken;
    const reals = charts.map(function (ch) {
        return ch.data.datasets.map(function (d) { return d.data.slice(); });
    });

    // 1) Gambar di posisi nol, instan (tanpa animasi).
    charts.forEach(function (ch) {
        ch.data.datasets.forEach(function (d) { d.data = d.data.map(function () { return 0; }); });
        ch.update('none');
    });

    // 2) Kembalikan data asli dan biarkan Chart.js menganimasikan.
    setTimeout(function () {
        if (token !== chartsRenderToken) return; // sudah di-render ulang

        charts.forEach(function (ch, i) {
            if (!ch.canvas || !ch.canvas.isConnected) return;
            ch.data.datasets.forEach(function (d, j) { d.data = reals[i][j]; });
            ch.update();
        });
    }, 0);
}

/* Render ulang chart saat tema diganti (event dari app.js) — tanpa animasi paksa. */
document.addEventListener('themechange', function () {
    renderCharts(false);
});

/*
 * Inisialisasi setelah layout stabil.
 * Chart.js (responsive) memakai ResizeObserver; bila container berubah ukuran
 * tepat setelah chart dibuat (grid fr belum final, font/scrollbar), auto-resize
 * itu memotong animasi pertama. Jadi tunggu window 'load' + satu frame rAF.
 */
function whenLayoutReady(fn) {
    if (document.readyState === 'complete') {
        requestAnimationFrame(fn);
    } else {
        window.addEventListener('load', function () {
            requestAnimationFrame(fn);
        }, { once: true });
    }
}

whenLayoutReady(function () {
    renderCharts(true);
});
