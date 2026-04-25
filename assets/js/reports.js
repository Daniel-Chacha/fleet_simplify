/* FleetSimplify VBMS — reports.js
   Details modal + PNG export with progress for admin/reports.php charts. */

(function () {
    'use strict';

    const modal = document.getElementById('chart-modal');
    const cmTitle = document.getElementById('cm-title');
    const cmBody  = document.getElementById('cm-body');
    const cmExport = document.getElementById('cm-export');
    const overlay = document.getElementById('export-overlay');
    const overlayBar = document.getElementById('export-bar-fill');
    const overlayStatus = document.getElementById('export-status');
    let activeKey = null;

    function findCard(key) { return document.querySelector('[data-chart-card="' + key + '"]'); }
    function findCanvas(key) { return document.querySelector('canvas[data-chart="' + key + '"]'); }

    window.fsChartDetails = function (key) {
        const card = findCard(key);
        if (!card) return;
        const tpl = card.querySelector('template[data-insight]');
        const title = card.querySelector('h3').textContent;
        cmTitle.textContent = title;
        cmBody.innerHTML = tpl ? tpl.innerHTML : '<p>No insight available.</p>';
        activeKey = key;
        modal.classList.add('is-open');
    };

    cmExport.addEventListener('click', function () {
        if (activeKey) fsChartExport(activeKey);
    });

    // ---------- Export ----------
    function setProgress(pct, msg) {
        overlayBar.style.width = Math.min(100, Math.max(0, pct)) + '%';
        if (msg) overlayStatus.textContent = msg;
    }
    function showOverlay() { overlay.classList.add('is-open'); }
    function hideOverlay() { overlay.classList.remove('is-open'); }
    function wait(ms) { return new Promise(function (r) { setTimeout(r, ms); }); }

    function wrapText(ctx, text, maxWidth) {
        const lines = [];
        const words = String(text || '').split(/\s+/);
        let line = '';
        words.forEach(function (w) {
            const test = line ? line + ' ' + w : w;
            if (ctx.measureText(test).width > maxWidth && line) {
                lines.push(line);
                line = w;
            } else {
                line = test;
            }
        });
        if (line) lines.push(line);
        return lines;
    }

    function htmlToText(node) {
        return (node.textContent || '').replace(/\s+/g, ' ').trim();
    }

    async function buildExportCanvas(key) {
        const card = findCard(key);
        const src = findCanvas(key);
        if (!card || !src) return null;

        const title = card.querySelector('h3').textContent;
        const sub   = card.querySelector('.chart-meta') ? card.querySelector('.chart-meta').textContent : '';
        const tpl   = card.querySelector('template[data-insight]');
        const tplBox = document.createElement('div');
        tplBox.innerHTML = tpl ? tpl.innerHTML : '';

        const summary = tplBox.querySelector('.insight-summary');
        const bullets = tplBox.querySelectorAll('.insight-bullets li');
        const rec     = tplBox.querySelector('.insight-rec');

        const W = 1200;
        const chartH = 480;
        const padX = 40;
        const headerH = 100;
        // Roughly estimate text height
        const lineH = 22;
        const textTop = headerH + chartH + 24;
        const tmpCanvas = document.createElement('canvas');
        const ctx = tmpCanvas.getContext('2d');
        ctx.font = '15px DM Sans, sans-serif';
        let textBlocks = [
            { kind: 'h', text: 'Summary' },
            { kind: 'p', text: htmlToText(summary || tplBox), bold: false },
            { kind: 'h', text: 'Insights' },
        ];
        bullets.forEach(function (li) { textBlocks.push({ kind: 'li', text: htmlToText(li) }); });
        textBlocks.push({ kind: 'h', text: 'Recommendation' });
        textBlocks.push({ kind: 'p', text: htmlToText(rec || { textContent: '' }) });

        // First pass: wrap and measure
        let totalH = 0;
        const wrapped = textBlocks.map(function (b) {
            ctx.font = b.kind === 'h' ? 'bold 17px Outfit, sans-serif' : (b.kind === 'li' ? '14px DM Sans' : '15px DM Sans');
            const lines = wrapText(ctx, b.text, W - padX * 2 - (b.kind === 'li' ? 18 : 0));
            const h = lines.length * lineH + (b.kind === 'h' ? 8 : 4);
            totalH += h + 4;
            return { kind: b.kind, lines: lines, h: h };
        });

        const H = textTop + totalH + 70;
        tmpCanvas.width = W;
        tmpCanvas.height = H;
        const ctx2 = tmpCanvas.getContext('2d');

        // Background
        ctx2.fillStyle = '#FFFFFF';
        ctx2.fillRect(0, 0, W, H);

        // Header bar (navy)
        ctx2.fillStyle = '#0A1628';
        ctx2.fillRect(0, 0, W, headerH);
        ctx2.fillStyle = '#FFFFFF';
        ctx2.font = 'bold 26px Outfit, sans-serif';
        ctx2.textBaseline = 'top';
        ctx2.fillText('FleetSimplify VBMS — ' + title, padX, 24);
        ctx2.fillStyle = '#C4CCDA';
        ctx2.font = '14px DM Sans, sans-serif';
        ctx2.fillText(sub || '', padX, 60);
        ctx2.fillStyle = '#FF6B35';
        ctx2.font = '13px DM Sans, sans-serif';
        ctx2.textAlign = 'right';
        ctx2.fillText('Generated ' + new Date().toLocaleString(), W - padX, 24);
        ctx2.textAlign = 'left';

        // Chart area
        ctx2.drawImage(src, padX, headerH + 12, W - padX * 2, chartH);

        // Render legend (if pie/donut) below the chart
        const legend = card.querySelector('[data-legend]');
        let yCursor = textTop;
        if (legend && legend.children.length) {
            ctx2.fillStyle = '#7A8595';
            ctx2.font = '12px DM Sans';
            const items = Array.from(legend.querySelectorAll('span'));
            // Just print legend as text (compact)
            // Skipped — chart already has its visual; keep export clean.
        }

        // Text blocks
        wrapped.forEach(function (b) {
            if (b.kind === 'h') {
                ctx2.fillStyle = '#0A1628';
                ctx2.font = 'bold 17px Outfit, sans-serif';
                b.lines.forEach(function (ln, i) { ctx2.fillText(ln, padX, yCursor + i * lineH); });
                yCursor += b.h + 4;
            } else if (b.kind === 'li') {
                ctx2.fillStyle = '#FF6B35';
                ctx2.font = 'bold 14px DM Sans';
                ctx2.fillText('•', padX + 2, yCursor);
                ctx2.fillStyle = '#1B2230';
                ctx2.font = '14px DM Sans';
                b.lines.forEach(function (ln, i) { ctx2.fillText(ln, padX + 18, yCursor + i * lineH); });
                yCursor += b.h + 4;
            } else {
                ctx2.fillStyle = '#424B5A';
                ctx2.font = '15px DM Sans';
                b.lines.forEach(function (ln, i) { ctx2.fillText(ln, padX, yCursor + i * lineH); });
                yCursor += b.h + 4;
            }
        });

        // Footer
        ctx2.fillStyle = '#7A8595';
        ctx2.font = '12px DM Sans, sans-serif';
        ctx2.fillText('© FleetSimplify VBMS — Vehicle Breakdown Management System', padX, H - 28);

        return tmpCanvas;
    }

    window.fsChartExport = async function (key) {
        showOverlay();
        setProgress(5, 'Rendering chart…');
        await wait(150);
        try {
            setProgress(25, 'Compositing report…');
            await wait(120);
            const canvas = await buildExportCanvas(key);
            if (!canvas) { hideOverlay(); window.toast('Export failed.', 'error'); return; }
            setProgress(60, 'Encoding PNG…');
            await wait(150);

            await new Promise(function (resolve) {
                canvas.toBlob(function (blob) {
                    setProgress(85, 'Saving file…');
                    setTimeout(function () {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        const stamp = new Date().toISOString().replace(/[:.]/g, '-');
                        a.href = url;
                        a.download = 'fleetsimplify-' + key + '-' + stamp + '.png';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                        setProgress(100, 'Done!');
                        setTimeout(function () { hideOverlay(); setProgress(0, ''); resolve(); }, 500);
                    }, 200);
                }, 'image/png');
            });
            window.toast('Report exported.', 'success');
        } catch (err) {
            console.error(err);
            hideOverlay();
            setProgress(0, '');
            window.toast('Export failed.', 'error');
        }
    };
})();
