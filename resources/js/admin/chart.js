// Gráfico de linha do Painel e tooltips (portado do mockup do painel (.spec/init/design)).
const brl = (n) => 'R$ ' + Number(n || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

const METRICS = {
    revenue: { title: 'Faturamento', format: brl, axis: (n) => 'R$ ' + (n >= 1000 ? (n / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil' : Math.round(n)), unit: () => '' },
    count: { title: 'Vendas', format: (n) => Math.round(n).toLocaleString('pt-BR'), unit: (n) => (n === 1 ? 'venda' : 'vendas') },
    pieces: { title: 'Peças vendidas', format: (n) => Math.round(n).toLocaleString('pt-BR'), unit: (n) => (n === 1 ? 'peça' : 'peças') },
};

const nice = (max) => {
    const e = Math.pow(10, Math.floor(Math.log10(max)));
    const r = max / e;
    return (r <= 1 ? 1 : r <= 2 ? 2 : r <= 2.5 ? 2.5 : r <= 5 ? 5 : 10) * e;
};

let tipEl = null;

function tip() {
    if (!tipEl) {
        tipEl = document.createElement('div');
        tipEl.className = 'tip';
        tipEl.setAttribute('role', 'tooltip');
        document.body.appendChild(tipEl);
    }
    return tipEl;
}

export function showTip(html, px, py) {
    const el = tip();
    el.innerHTML = html;
    el.classList.add('show');
    const tw = el.offsetWidth;
    const th = el.offsetHeight;
    let l = px + 14;
    let t = py - th - 12;
    if (l + tw > window.innerWidth - 8) l = px - tw - 14;
    if (l < 8) l = 8;
    if (t < 8) t = py + 14;
    el.style.left = l + 'px';
    el.style.top = t + 'px';
}

export function hideTip() {
    tip().classList.remove('show');
}

document.addEventListener('mousemove', (e) => {
    const target = e.target.closest?.('[data-tip]');
    if (target && target.dataset.tip) showTip(target.dataset.tip, e.clientX, e.clientY);
    else if (!e.target.closest?.('.plot')) hideTip();
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('salesChart', (series, metric) => ({
        draw() {
            const el = this.$refs.plot;
            const key = metric;
            const m = METRICS[key];
            const fmt = m.axis || m.format;
            const w = el.clientWidth;
            const h = el.clientHeight;
            const L = 40, R = 8, T = 10, B = 26;
            const iw = w - L - R;
            const ih = h - T - B;
            const max = nice(Math.max(...series.map((x) => x[key])) * 1.08 || 1);
            const x = (i) => L + (series.length === 1 ? iw / 2 : (i * iw) / (series.length - 1));
            const y = (v) => T + ih - (v / max) * ih;

            let g = '';
            for (let k = 0; k <= 4; k++) {
                const v = (max * k) / 4;
                g += `<line class="gl" x1="${L}" x2="${w - R}" y1="${y(v)}" y2="${y(v)}"/><text class="ax" x="${L - 8}" y="${y(v) + 4}" text-anchor="end">${fmt(v)}</text>`;
            }
            const every = Math.ceil(series.length / Math.max(2, Math.floor(iw / 64)));
            series.forEach((d, i) => {
                if (i % every === 0 || (i === series.length - 1 && (series.length - 1) % every > every / 2)) {
                    g += `<text class="ax" x="${x(i)}" y="${h - 6}" text-anchor="${i === 0 ? 'start' : i === series.length - 1 ? 'end' : 'middle'}">${d.label}</text>`;
                }
            });

            const pts = series.map((d, i) => `${x(i).toFixed(1)},${y(d[key]).toFixed(1)}`);
            const area = `M${x(0)},${y(0)} L${pts.join(' L')} L${x(series.length - 1)},${y(0)}Z`;
            el.innerHTML = `<svg viewBox="0 0 ${w} ${h}" role="img"><defs><linearGradient id="ag" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#59454D" stop-opacity="0.16"/><stop offset="1" stop-color="#59454D" stop-opacity="0"/></linearGradient></defs>${g}<path d="${area}" fill="url(#ag)"/><polyline id="ln" points="${pts.join(' ')}" fill="none" stroke="#59454D" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/><line id="cx" y1="${T}" y2="${T + ih}" stroke="#3B2E34" stroke-opacity="0.35" stroke-dasharray="3 3" style="display:none"/><circle id="cd" r="5" fill="#59454D" stroke="#FFFFFF" stroke-width="2" style="display:none"/><rect x="${L}" y="0" width="${iw}" height="${h}" fill="transparent" id="hit"/></svg>`;

            const ln = el.querySelector('#ln');
            const len = ln.getTotalLength();
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && ln.animate) {
                ln.animate([{ strokeDasharray: len, strokeDashoffset: len }, { strokeDasharray: len, strokeDashoffset: 0 }], { duration: 900, easing: 'cubic-bezier(.2,.7,.2,1)' });
            }
            const cx = el.querySelector('#cx');
            const cd = el.querySelector('#cd');
            const hit = el.querySelector('#hit');
            const move = (ev) => {
                const r = el.getBoundingClientRect();
                const px = (ev.touches ? ev.touches[0].clientX : ev.clientX) - r.left;
                const i = Math.max(0, Math.min(series.length - 1, Math.round(((px - L) / iw) * (series.length - 1))));
                const d = series[i];
                cx.setAttribute('x1', x(i)); cx.setAttribute('x2', x(i)); cx.style.display = '';
                cd.setAttribute('cx', x(i)); cd.setAttribute('cy', y(d[key])); cd.style.display = '';
                showTip(`<span>${d.range ? 'Semana ' + d.range : d.label}</span><b>${m.format(d[key])}</b> ${m.unit(d[key])}`, r.left + x(i), r.top + y(d[key]));
            };
            hit.addEventListener('mousemove', move);
            hit.addEventListener('touchstart', move, { passive: true });
            hit.addEventListener('touchmove', move, { passive: true });
            hit.addEventListener('mouseleave', () => { cx.style.display = 'none'; cd.style.display = 'none'; hideTip(); });
        },
    }));
});
