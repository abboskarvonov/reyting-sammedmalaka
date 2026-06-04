/* ── Chart.js global defaults ──────────────────────────────────────── */
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.font.weight = 'normal';
}

/* ── Read PHP-injected data ─────────────────────────────────────────── */
const { TD, DD, TK, ROWS, TEACHER_FULL, DIR_FULL, ATTEND, DONE_N, PEND_N, ATTEND_ALL, DEFAULT_MONTH, TK_ROWS } = window.STATS_DATA ?? {};

/* ── Alpine: sApp (global function, called via x-data="sApp()") ─────── */
window.sApp = () => ({
    fs: '',
    fd: '',
    modal: false,
    modalTab: 'tasks',
    modalTeacher: '',
    modalTasks: [],
    modalDirs: [],
    openModal(id, tab = 'tasks') {
        const d = TEACHER_FULL[id];
        if (!d) return;
        this.modalTeacher = d.name;
        this.modalTasks = d.tasks || [];
        this.modalDirs = d.dirs || [];
        this.modalTab = tab;
        this.modal = true;
    },
    dirModal: false,
    dirModalName: '',
    dirModalScore: 0,
    dirModalTeachers: [],
    openDirModal(id) {
        const d = DIR_FULL[id];
        if (!d) return;
        this.dirModalName = d.name;
        this.dirModalScore = d.score;
        this.dirModalTeachers = d.teachers || [];
        this.dirModal = true;
    },
    get filtered() {
        const q = this.fs.trim().toLowerCase(),
            d = this.fd.toLowerCase();
        return ROWS.filter(r => {
            const mq = !q || r.name.toLowerCase().includes(q) || r.dept.toLowerCase().includes(q);
            const md = !d || r.dirs.toLowerCase().includes(d);
            return mq && md;
        });
    }
});

/* ── Score gradient helper ───────────────────────────────────────────── */
function scoreGrad(ctx, chartArea, value, horiz = true) {
    const g = horiz
        ? ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0)
        : ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    const [s, e] = value >= 4.5
        ? ['#10B981', '#6EE7B7']
        : value >= 3.5
            ? ['#F59E0B', '#FCD34D']
            : ['#EF4444', '#FCA5A5'];
    g.addColorStop(0, s);
    g.addColorStop(1, e);
    return g;
}

/* ── 1. Teacher horizontal bar ──────────────────────────────────────── */
function bTeacher() {
    const el = document.getElementById('tc');
    if (!el || !TD || !TD.labels.length) return;
    const n = TD.labels.length;
    const wrap = document.getElementById('twrap');
    if (wrap) wrap.style.height = Math.max(200, n * 46) + 'px';
    new Chart(el, {
        type: 'bar',
        data: {
            labels: TD.labels,
            datasets: [{
                label: 'Ball',
                data: TD.scores,
                backgroundColor(ctx) {
                    const { ctx: c, chartArea: ca } = ctx.chart;
                    if (!ca) return '#10B981';
                    return scoreGrad(c, ca, TD.scores[ctx.dataIndex], true);
                },
                borderRadius: 5,
                borderSkipped: false,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        title: c => c[0].label,
                        label: c => [
                            ` Ball: ${c.raw.toFixed(2)} / 5.00`,
                            ` Baholashlar: ${TD.counts[c.dataIndex]} ta`
                        ]
                    }
                }
            },
            scales: {
                x: {
                    min: 0,
                    max: 5,
                    grid: { color: '#F9FAFB', drawBorder: false },
                    border: { display: false },
                    ticks: { font: { size: 10, family: "'Inter', system-ui, sans-serif" }, color: '#9CA3AF', stepSize: 1 }
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 13, family: "'Inter', system-ui, sans-serif" }, color: '#374151' }
                }
            },
            barPercentage: 0.55,
            animation: { duration: 800, easing: 'easeOutQuart' }
        }
    });
}

/* ── 2. Direction horizontal bar ─────────────────────────────────────── */
function bDir() {
    const el = document.getElementById('dc');
    if (!el || !DD || !DD.labels.length) return;
    const n = DD.labels.length;
    const wrap = document.getElementById('dwrap');
    if (wrap) wrap.style.height = Math.max(160, n * 56) + 'px';
    new Chart(el, {
        type: 'bar',
        data: {
            labels: DD.labels,
            datasets: [{
                label: 'Ball',
                data: DD.scores,
                backgroundColor(ctx) {
                    const { ctx: c, chartArea: ca } = ctx.chart;
                    if (!ca) return '#3B82F6';
                    return scoreGrad(c, ca, DD.scores[ctx.dataIndex], true);
                },
                borderRadius: 5,
                borderSkipped: false,
                borderWidth: 0
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        title: c => c[0].label,
                        label: c => [
                            ` Ball: ${c.raw.toFixed(2)} / 5.00`,
                            ` Baholashlar: ${DD.counts[c.dataIndex]} ta`
                        ]
                    }
                }
            },
            scales: {
                x: {
                    min: 0,
                    max: 5,
                    grid: { color: '#F9FAFB', drawBorder: false },
                    border: { display: false },
                    ticks: { font: { size: 10, family: "'Inter', system-ui, sans-serif" }, color: '#9CA3AF', stepSize: 1 }
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 13, family: "'Inter', system-ui, sans-serif" }, color: '#374151' }
                }
            },
            barPercentage: 0.5,
            animation: { duration: 900, easing: 'easeOutQuart' }
        }
    });
}

/* ── 3. Task donut ────────────────────────────────────────────────────── */
function bDonut() {
    const el = document.getElementById('dn');
    if (!el) return;
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: ['Bajarilgan', 'Bajarilmagan'],
            datasets: [{
                data: [DONE_N || 0.01, PEND_N || 0.01],
                backgroundColor: ['#10B981', '#EF4444'],
                hoverBackgroundColor: ['#059669', '#DC2626'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '72%',
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: c => ` ${c.label}: ${c.raw} ta`
                    }
                }
            },
            animation: { animateRotate: true, duration: 900 }
        }
    });
}

/* ── 4. Attendance stacked horizontal bar ────────────────────────────── */

/* ── Attendance chart instance (shared for updates) ─────────────────── */
let attChart = null;

function bAttendData(d) {
    const el = document.getElementById('ac');
    if (!el || !d || !d.labels.length) return;
    const n    = d.labels.length;
    const wrap = document.getElementById('awrap');
    if (wrap) wrap.style.height = Math.max(180, n * 44) + 'px';

    const datasets = [
        { label: "O'z vaqtida", data: d.ot, backgroundColor: 'rgba(16,185,129,.85)',  stack: 'a', borderWidth: 0, borderRadius: 5, borderSkipped: false },
        { label: 'Kech',        data: d.la, backgroundColor: 'rgba(245,158,11,.85)',  stack: 'a', borderWidth: 0, borderRadius: 5, borderSkipped: false },
        { label: 'Uzrli',       data: d.ex, backgroundColor: 'rgba(59,130,246,.85)',  stack: 'a', borderWidth: 0, borderRadius: 5, borderSkipped: false },
        { label: 'Kelmagan',    data: d.ab, backgroundColor: 'rgba(239,68,68,.80)',   stack: 'a', borderWidth: 0, borderRadius: 5, borderSkipped: false },
    ];

    const tooltipCounts = [d.don, d.lan, d.exn, d.abn];

    if (attChart) {
        attChart.data.labels          = d.labels;
        attChart.data.datasets.forEach((ds, idx) => {
            ds.data = datasets[idx].data;
        });
        attChart.options.plugins.tooltip.callbacks.label = function(c) {
            const cnt = tooltipCounts[c.datasetIndex];
            return ` ${c.dataset.label}: ${cnt[c.dataIndex]}/${d.tot[c.dataIndex]} kun (${c.raw}%)`;
        };
        if (wrap) wrap.style.height = Math.max(180, d.labels.length * 44) + 'px';
        attChart.update('active');
        return;
    }

    attChart = new Chart(el, {
        type: 'bar',
        data: { labels: d.labels, datasets },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    padding: 12,
                    cornerRadius: 10,
                    callbacks: {
                        title: c => c[0].label,
                        label(c) {
                            const cnt = tooltipCounts[c.datasetIndex];
                            return ` ${c.dataset.label}: ${cnt[c.dataIndex]}/${d.tot[c.dataIndex]} kun (${c.raw}%)`;
                        }
                    }
                }
            },
            scales: {
                x: { stacked: true, min: 0, max: 100, grid: { color: '#F9FAFB', drawBorder: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#9CA3AF', callback: v => v + '%' } },
                y: { stacked: true, grid: { display: false }, border: { display: false }, ticks: { font: { size: 13 }, color: '#374151' } }
            },
            barPercentage: 0.55,
            animation: { duration: 700, easing: 'easeOutQuart' }
        }
    });
}

/* ── Alpine: attApp (davomat card) ──────────────────────────────────── */
window.attApp = () => ({
    allData:       ATTEND_ALL ?? {},
    selectedMonth: DEFAULT_MONTH ?? '',
    get monthKeys()     { return Object.keys(this.allData).sort().reverse(); },
    get currentData()   { return this.allData[this.selectedMonth] ?? null; },
    get currentLabel()  { return this.currentData?.label ?? ''; },
    get summary()       { return this.currentData?.summary ?? { on_time:0, late:0, excused:0, absent:0, total:0 }; },
    get hasData()       { return (this.currentData?.labels?.length ?? 0) > 0; },
    setMonth(mk) {
        this.selectedMonth = mk;
        this.$nextTick(() => bAttendData(this.currentData));
    },
    init() {
        this.$nextTick(() => {
            document.fonts.ready.then(() => bAttendData(this.currentData));
        });
    },
});

/* ── Alpine: tApp (topshiriqlar page) ───────────────────────────────── */
window.tApp = () => ({
    fs: '',
    modal: false,
    modalTeacher: '',
    modalTasks: [],
    openModal(id) {
        const d = (TEACHER_FULL || {})[id];
        if (!d) return;
        this.modalTeacher = d.name;
        this.modalTasks = d.tasks || [];
        this.modal = true;
    },
    get filtered() {
        const q = this.fs.trim().toLowerCase();
        return (TK_ROWS || []).filter(r => !q || r.name.toLowerCase().includes(q));
    }
});

/* ── Global init (called from x-init="$nextTick(initCharts)") ────────── */
window.initCharts = function () {
    document.fonts.ready.then(() => {
        bTeacher();
        bDir();
        bDonut();
    });
};
