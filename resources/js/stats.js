/* ── Icon font FOUC fix: ikonlar faqat font tayyor bo'lganda ko'rinadi ── */
document.fonts.ready.then(() => {
    document.documentElement.classList.add('fonts-loaded');
});

/* ── Chart.js global defaults ──────────────────────────────────────── */
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;
    Chart.defaults.font.weight = 'normal';
}

/* ── Read PHP-injected data ─────────────────────────────────────────── */
const { TD, DD, TK, ROWS, TEACHER_FULL, DIR_FULL, ATTEND, DONE_N, PEND_N, ATTEND_ALL, DEFAULT_MONTH, TK_ROWS } = window.STATS_DATA ?? {};

/* ── Alpine: sApp ──────────────────────────────────────────────────── */
window.sApp = () => ({
    fs: '',
    get filtered() {
        const q = this.fs.trim().toLowerCase();
        return (ROWS || []).filter(r => !q || r.name.toLowerCase().includes(q));
    },
    modal: false,
    modalTab: 'tasks',
    modalTeacher: '',
    modalTasks: [],
    modalDirs: [],
    openModal(id, tab = 'tasks') {
        const d = TEACHER_FULL[id];
        if (!d) return;
        this.modalTeacher = d.name;
        this.modalTasks   = d.tasks || [];
        this.modalDirs    = d.dirs  || [];
        this.modalTab     = tab;
        this.modal        = true;
    },
    dirModal: false,
    dirModalName: '',
    dirModalScore: 0,
    dirModalTeachers: [],
    openDirModal(id) {
        const d = DIR_FULL[id];
        if (!d) return;
        this.dirModalName     = d.name;
        this.dirModalScore    = d.score;
        this.dirModalTeachers = d.teachers || [];
        this.dirModal         = true;
    },
});

/* ── Score gradient helper (Chart.js) ───────────────────────────────── */
function scoreGrad(ctx, chartArea, value, horiz = true) {
    const g = horiz
        ? ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0)
        : ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    const [s, e] = value >= 4.5
        ? ['#006c49', '#2d9e6b']
        : value >= 3.5
            ? ['#784b00', '#a86800']
            : ['#ba1a1a', '#d94444'];
    g.addColorStop(0, s);
    g.addColorStop(1, e);
    return g;
}

/* ── Teacher horizontal bar (Chart.js) ─────────────────────────────── */
function bTeacher() {
    const el = document.getElementById('tc');
    if (!el || !TD || !TD.labels.length) return;
    const n    = TD.labels.length;
    const wrap = document.getElementById('twrap');
    if (wrap) wrap.style.height = Math.max(160, n * 56) + 'px';
    new Chart(el, {
        type: 'bar',
        data: {
            labels: TD.labels,
            datasets: [{
                label: 'Ball',
                data: TD.scores,
                backgroundColor(ctx) {
                    const { ctx: c, chartArea: ca } = ctx.chart;
                    if (!ca) return '#3B82F6';
                    return scoreGrad(c, ca, TD.scores[ctx.dataIndex], true);
                },
                borderRadius: 5,
                borderSkipped: false,
                borderWidth: 0,
            }],
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
                            ` Baholashlar: ${TD.counts[c.dataIndex]} ta`,
                        ],
                    },
                },
            },
            scales: {
                x: {
                    min: 0, max: 5,
                    grid: { color: '#F9FAFB', drawBorder: false },
                    border: { display: false },
                    ticks: { font: { size: 10 }, color: '#9CA3AF', stepSize: 1 },
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 13 }, color: '#374151' },
                },
            },
            barPercentage: 0.5,
            animation: { duration: 900, easing: 'easeOutQuart' },
        },
    });
}

/* ── Direction horizontal bar (Chart.js) ───────────────────────────── */
function bDir() {
    const el = document.getElementById('dc');
    if (!el || !DD || !DD.labels.length) return;
    const n    = DD.labels.length;
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
                borderWidth: 0,
            }],
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
                            ` Baholashlar: ${DD.counts[c.dataIndex]} ta`,
                        ],
                    },
                },
            },
            scales: {
                x: {
                    min: 0, max: 5,
                    grid: { color: '#F9FAFB', drawBorder: false },
                    border: { display: false },
                    ticks: { font: { size: 10 }, color: '#9CA3AF', stepSize: 1 },
                },
                y: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { size: 13 }, color: '#374151' },
                },
            },
            barPercentage: 0.5,
            animation: { duration: 900, easing: 'easeOutQuart' },
        },
    });
}

/* ── Task donut (Chart.js) ──────────────────────────────────────────── */
function bDonut() {
    const el = document.getElementById('dn');
    if (!el) return;
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels: ['Bajarilgan', 'Bajarilmagan'],
            datasets: [{
                data: [DONE_N || 0.01, PEND_N || 0.01],
                backgroundColor: ['#006c49', '#ba1a1a'],
                hoverBackgroundColor: ['#005038', '#8f1414'],
                borderWidth: 0,
                hoverOffset: 4,
            }],
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
                    callbacks: { label: c => ` ${c.label}: ${c.raw} ta` },
                },
            },
            animation: { animateRotate: true, duration: 900 },
        },
    });
}

/* ── Alpine: attApp ─────────────────────────────────────────────────── */
window.attApp = () => ({
    allData:       ATTEND_ALL ?? {},
    selectedMonth: DEFAULT_MONTH ?? '',
    get monthKeys()    { return Object.keys(this.allData).sort().reverse(); },
    get currentData()  { return this.allData[this.selectedMonth] ?? null; },
    get currentLabel() { return this.currentData?.label ?? ''; },
    get summary()      { return this.currentData?.summary ?? { on_time: 0, late: 0, excused: 0, absent: 0, total: 0 }; },
    get hasData()      { return (this.currentData?.labels?.length ?? 0) > 0; },
    setMonth(mk) { this.selectedMonth = mk; },
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
        this.modalTasks   = d.tasks || [];
        this.modal        = true;
    },
    get filtered() {
        const q = this.fs.trim().toLowerCase();
        return (TK_ROWS || []).filter(r => !q || r.name.toLowerCase().includes(q));
    },
});

/* ── Global init ────────────────────────────────────────────────────── */
window.initCharts = function () {
    document.fonts.ready.then(() => {
        bTeacher();
        bDir();
        bDonut();
    });
};
