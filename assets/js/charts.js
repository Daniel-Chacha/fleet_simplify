/* FleetSimplify VBMS — charts.js
   Vanilla Canvas/SVG primitives: pie, donut, vertical bar, horizontal bar.
   Reads window.__charts (rendered server-side) and draws into canvases. */

(function () {
    'use strict';

    const PALETTE = [
        '#FF6B35', '#0A1628', '#2563EB', '#1F9D55', '#F59E0B',
        '#7C3AED', '#DC2626', '#0891B2', '#DB2777', '#65A30D',
        '#475569', '#FF8554', '#3B82F6', '#10B981', '#FBBF24'
    ];

    // ---------- Tooltip ----------
    let tip = null;
    function ensureTip() {
        if (!tip) {
            tip = document.createElement('div');
            tip.className = 'chart-tooltip';
            document.body.appendChild(tip);
        }
        return tip;
    }
    function showTip(x, y, text) {
        const t = ensureTip();
        t.textContent = text;
        t.style.left = x + 'px';
        t.style.top = y + 'px';
        t.classList.add('show');
    }
    function hideTip() {
        if (tip) tip.classList.remove('show');
    }

    // ---------- Pie / donut ----------
    function drawPie(canvas, data, opts) {
        opts = opts || {};
        const dpr = window.devicePixelRatio || 1;
        const W = canvas.clientWidth, H = canvas.clientHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, W, H);

        const total = data.reduce(function (a, d) { return a + d.value; }, 0) || 1;
        const cx = W / 2, cy = H / 2;
        const r = Math.min(W, H) / 2 - 12;
        const inner = opts.donut ? r * 0.55 : 0;

        const slices = [];
        let acc = -Math.PI / 2;
        data.forEach(function (d, i) {
            const ang = (d.value / total) * Math.PI * 2;
            const start = acc, end = acc + ang;
            const color = PALETTE[i % PALETTE.length];
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, start, end);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 1.5;
            ctx.stroke();
            slices.push({ start: start, end: end, color: color, label: d.label, value: d.value });
            acc = end;
        });

        if (opts.donut) {
            ctx.beginPath();
            ctx.fillStyle = '#fff';
            ctx.arc(cx, cy, inner, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillStyle = '#0A1628';
            ctx.font = '600 14px Outfit';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(total.toLocaleString(), cx, cy - 8);
            ctx.fillStyle = '#7A8595';
            ctx.font = '500 11px DM Sans';
            ctx.fillText('Total', cx, cy + 10);
        }

        canvas._slices = slices;
        canvas._geom = { cx: cx, cy: cy, r: r, inner: inner };
        canvas.onmousemove = function (e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            const dx = x - cx, dy = y - cy;
            const dist = Math.sqrt(dx*dx + dy*dy);
            if (dist > r || dist < inner) { hideTip(); return; }
            let ang = Math.atan2(dy, dx);
            // Normalize so 12 o'clock is start (-PI/2)
            for (let i = 0; i < slices.length; i++) {
                const s = slices[i];
                let a = ang, st = s.start, en = s.end;
                if (en > Math.PI) { /* unnecessary, but covers wrapping */ }
                // shift everything by +2PI if needed
                if (a < st) a += Math.PI * 2;
                if (en < st) en += Math.PI * 2;
                if (a >= st && a <= en) {
                    const pct = (s.value / total * 100).toFixed(1);
                    showTip(e.clientX, e.clientY, s.label + ': ' + s.value + ' (' + pct + '%)');
                    return;
                }
            }
            hideTip();
        };
        canvas.onmouseleave = hideTip;
    }

    // ---------- Vertical bar ----------
    function drawBar(canvas, data, opts) {
        opts = opts || {};
        const dpr = window.devicePixelRatio || 1;
        const W = canvas.clientWidth, H = canvas.clientHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, W, H);

        const padL = 36, padR = 12, padT = 12, padB = 32;
        const innerW = W - padL - padR;
        const innerH = H - padT - padB;
        const max = data.reduce(function (a, d) { return Math.max(a, d.value); }, 0) || 1;
        // round-up Y axis
        const yMax = Math.ceil(max * 1.1) || 1;
        const ticks = 4;

        // grid + Y labels
        ctx.strokeStyle = '#EEF1F6';
        ctx.fillStyle = '#7A8595';
        ctx.font = '500 10px DM Sans';
        ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
        for (let i = 0; i <= ticks; i++) {
            const y = padT + (innerH * i / ticks);
            ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(W - padR, y); ctx.stroke();
            const label = Math.round(yMax - (yMax * i / ticks));
            ctx.fillText(label.toString(), padL - 4, y);
        }

        const bw = innerW / data.length * 0.65;
        const gap = innerW / data.length;

        const bars = [];
        ctx.textAlign = 'center'; ctx.textBaseline = 'top';
        data.forEach(function (d, i) {
            const h = (d.value / yMax) * innerH;
            const x = padL + gap * i + (gap - bw) / 2;
            const y = padT + (innerH - h);
            const color = PALETTE[i % PALETTE.length];
            ctx.fillStyle = color;
            ctx.fillRect(x, y, bw, h);
            bars.push({ x: x, y: y, w: bw, h: h, label: d.label, value: d.value, color: color });
            ctx.fillStyle = '#7A8595';
            ctx.font = '500 10px DM Sans';
            ctx.fillText(d.label, x + bw / 2, padT + innerH + 6);
        });

        canvas._bars = bars;
        canvas.onmousemove = function (e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            for (let i = 0; i < bars.length; i++) {
                const b = bars[i];
                if (x >= b.x && x <= b.x + b.w && y >= b.y && y <= b.y + b.h) {
                    showTip(e.clientX, e.clientY, b.label + ': ' + b.value);
                    return;
                }
            }
            hideTip();
        };
        canvas.onmouseleave = hideTip;
    }

    // ---------- Line chart ----------
    function drawLine(canvas, data, opts) {
        opts = opts || {};
        const dpr = window.devicePixelRatio || 1;
        const W = canvas.clientWidth, H = canvas.clientHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, W, H);

        const padL = 36, padR = 12, padT = 12, padB = 32;
        const innerW = W - padL - padR;
        const innerH = H - padT - padB;
        const max = data.reduce(function (a, d) { return Math.max(a, d.value); }, 0) || 1;
        const yMax = Math.ceil(max * 1.15) || 1;
        const ticks = 4;

        // grid + Y labels
        ctx.strokeStyle = '#EEF1F6';
        ctx.fillStyle = '#7A8595';
        ctx.font = '500 10px DM Sans';
        ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
        for (let i = 0; i <= ticks; i++) {
            const y = padT + (innerH * i / ticks);
            ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(W - padR, y); ctx.stroke();
            const label = Math.round(yMax - (yMax * i / ticks));
            ctx.fillText(label.toString(), padL - 4, y);
        }

        // Plot points
        const stepX = innerW / Math.max(1, data.length - 1);
        const points = data.map(function (d, i) {
            return {
                x: padL + (data.length === 1 ? innerW / 2 : stepX * i),
                y: padT + innerH - (d.value / yMax) * innerH,
                label: d.label, value: d.value
            };
        });

        // Filled area under the line
        if (points.length > 1) {
            const grad = ctx.createLinearGradient(0, padT, 0, padT + innerH);
            grad.addColorStop(0, 'rgba(255,107,53,0.30)');
            grad.addColorStop(1, 'rgba(255,107,53,0.02)');
            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.moveTo(points[0].x, padT + innerH);
            points.forEach(function (p) { ctx.lineTo(p.x, p.y); });
            ctx.lineTo(points[points.length - 1].x, padT + innerH);
            ctx.closePath();
            ctx.fill();
        }

        // The line itself
        ctx.strokeStyle = '#FF6B35';
        ctx.lineWidth = 2.5;
        ctx.beginPath();
        points.forEach(function (p, i) { i === 0 ? ctx.moveTo(p.x, p.y) : ctx.lineTo(p.x, p.y); });
        ctx.stroke();

        // Dots + x-axis labels
        ctx.textAlign = 'center'; ctx.textBaseline = 'top';
        points.forEach(function (p) {
            ctx.fillStyle = '#FF6B35';
            ctx.beginPath(); ctx.arc(p.x, p.y, 4, 0, Math.PI * 2); ctx.fill();
            ctx.fillStyle = '#FFFFFF';
            ctx.beginPath(); ctx.arc(p.x, p.y, 2, 0, Math.PI * 2); ctx.fill();
            ctx.fillStyle = '#7A8595';
            ctx.font = '500 10px DM Sans';
            ctx.fillText(p.label, p.x, padT + innerH + 6);
        });

        canvas._points = points;
        canvas.onmousemove = function (e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            for (let i = 0; i < points.length; i++) {
                const p = points[i];
                const d = Math.hypot(p.x - x, p.y - y);
                if (d < 12) {
                    showTip(e.clientX, e.clientY, p.label + ': ' + p.value);
                    return;
                }
            }
            hideTip();
        };
        canvas.onmouseleave = hideTip;
    }

    // ---------- Horizontal bar ----------
    function drawHBar(canvas, data, opts) {
        opts = opts || {};
        const dpr = window.devicePixelRatio || 1;
        const W = canvas.clientWidth, H = canvas.clientHeight;
        canvas.width = W * dpr; canvas.height = H * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, W, H);

        const padL = 110, padR = 30, padT = 10, padB = 22;
        const innerW = W - padL - padR;
        const innerH = H - padT - padB;
        const max = data.reduce(function (a, d) { return Math.max(a, d.value); }, 0) || 1;
        const xMax = Math.ceil(max * 1.1) || 1;

        // X grid
        const ticks = 4;
        ctx.strokeStyle = '#EEF1F6';
        ctx.fillStyle = '#7A8595';
        ctx.font = '500 10px DM Sans';
        ctx.textAlign = 'center'; ctx.textBaseline = 'top';
        for (let i = 0; i <= ticks; i++) {
            const x = padL + innerW * i / ticks;
            ctx.beginPath(); ctx.moveTo(x, padT); ctx.lineTo(x, padT + innerH); ctx.stroke();
            ctx.fillText(Math.round(xMax * i / ticks).toString(), x, padT + innerH + 4);
        }

        const bh = innerH / data.length * 0.65;
        const gap = innerH / data.length;
        const bars = [];

        ctx.textAlign = 'right'; ctx.textBaseline = 'middle';
        data.forEach(function (d, i) {
            const w = (d.value / xMax) * innerW;
            const y = padT + gap * i + (gap - bh) / 2;
            const color = PALETTE[i % PALETTE.length];
            ctx.fillStyle = color;
            ctx.fillRect(padL, y, w, bh);
            bars.push({ x: padL, y: y, w: w, h: bh, label: d.label, value: d.value, color: color });
            ctx.fillStyle = '#1B2230';
            ctx.font = '500 11px DM Sans';
            ctx.fillText(d.label, padL - 6, y + bh / 2);
        });

        canvas._bars = bars;
        canvas.onmousemove = function (e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left, y = e.clientY - rect.top;
            for (let i = 0; i < bars.length; i++) {
                const b = bars[i];
                if (x >= b.x && x <= b.x + b.w && y >= b.y && y <= b.y + b.h) {
                    showTip(e.clientX, e.clientY, b.label + ': ' + b.value);
                    return;
                }
            }
            hideTip();
        };
        canvas.onmouseleave = hideTip;
    }

    // ---------- Legend rendering ----------
    function renderLegend(el, data) {
        const html = data.map(function (d, i) {
            const c = PALETTE[i % PALETTE.length];
            return '<span><span class="swatch" style="background:' + c + '"></span>' +
                   escapeHtml(d.label) + ' <strong>(' + d.value + ')</strong></span>';
        }).join('');
        el.innerHTML = html;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    // ---------- Entry point ----------
    function render() {
        const charts = window.__charts || {};
        Object.keys(charts).forEach(function (key) {
            const conf = charts[key];
            const canvas = document.querySelector('[data-chart="' + key + '"]');
            if (!canvas) return;
            const data = (conf.data || []).filter(function (d) { return d.value > 0 || conf.keepZeros; });
            if (!data.length) {
                const card = canvas.closest('.chart-card');
                if (card) {
                    canvas.style.display = 'none';
                    const empty = document.createElement('div');
                    empty.className = 'empty';
                    empty.textContent = 'No data available.';
                    card.appendChild(empty);
                }
                return;
            }
            if (conf.type === 'pie')   drawPie(canvas, data, { donut: false });
            else if (conf.type === 'donut') drawPie(canvas, data, { donut: true });
            else if (conf.type === 'bar')   drawBar(canvas, data);
            else if (conf.type === 'hbar')  drawHBar(canvas, data);
            else if (conf.type === 'line')  drawLine(canvas, data);
            const legendEl = document.querySelector('[data-legend="' + key + '"]');
            if (legendEl && (conf.type === 'pie' || conf.type === 'donut')) renderLegend(legendEl, data);
        });
    }

    document.addEventListener('DOMContentLoaded', render);
    window.addEventListener('resize', function () {
        // simple: re-render on resize
        clearTimeout(window.__chartsResizeT);
        window.__chartsResizeT = setTimeout(render, 200);
    });
})();
