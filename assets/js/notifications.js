/* FleetSimplify VBMS — notifications.js
   Mechanic dashboard notification panel + Web Audio beep for new requests. */

(function () {
    'use strict';
    const cfg = window.__notif;
    if (!cfg) return;

    const panel = document.getElementById('notif-panel');
    const bell  = document.getElementById('notif-bell');
    const bellCount = bell ? bell.querySelector('.count') : null;
    let lastSeenIds = new Set();
    let audioCtx = null;
    let muted = false;

    function ensureAudio() {
        if (!audioCtx) {
            try { audioCtx = new (window.AudioContext || window.webkitAudioContext)(); }
            catch (_) { audioCtx = null; }
        }
        return audioCtx;
    }

    function beep() {
        if (muted) return;
        const ctx = ensureAudio();
        if (!ctx) return;
        // two-tone alert — ~800ms total
        const tones = [
            { f: 880, t: 0.0,  d: 0.18 },
            { f: 660, t: 0.22, d: 0.18 },
            { f: 880, t: 0.46, d: 0.18 },
            { f: 660, t: 0.68, d: 0.18 }
        ];
        const now = ctx.currentTime;
        tones.forEach(function (tn) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = tn.f;
            gain.gain.setValueAtTime(0, now + tn.t);
            gain.gain.linearRampToValueAtTime(0.18, now + tn.t + 0.02);
            gain.gain.linearRampToValueAtTime(0, now + tn.t + tn.d);
            osc.connect(gain).connect(ctx.destination);
            osc.start(now + tn.t);
            osc.stop(now + tn.t + tn.d + 0.02);
        });
    }

    // Unlock audio on first user interaction (browsers block auto-start).
    function unlockAudio() {
        const ctx = ensureAudio();
        if (ctx && ctx.state === 'suspended') ctx.resume();
        document.removeEventListener('click', unlockAudio);
        document.removeEventListener('keydown', unlockAudio);
    }
    document.addEventListener('click', unlockAudio);
    document.addEventListener('keydown', unlockAudio);

    function renderCount(n) {
        if (!bellCount) return;
        if (n > 0) { bellCount.textContent = String(n); bellCount.style.display = 'inline-flex'; }
        else { bellCount.style.display = 'none'; }
    }

    function renderPanel(items) {
        if (!panel) return;
        if (!items.length) { panel.innerHTML = '<div class="notif-empty">No new requests.</div>'; return; }
        const html = items.map(function (b) {
            return '<div class="notif-card" data-id="' + b.id + '">'
                + '<h4>' + escapeHtml(b.booking_number) + ' • ' + escapeHtml(b.vehicle_plate) + '</h4>'
                + '<p><strong>' + escapeHtml(b.driver_name || 'Driver') + '</strong> — '
                + escapeHtml(b.breakdown_cause) + ' (' + escapeHtml(b.severity) + ')<br>'
                + 'Location: ' + escapeHtml(b.breakdown_location) + ' • ' + escapeHtml(b.vehicle_type) + '</p>'
                + '<div class="notif-actions">'
                + '<button class="btn btn-success btn-sm" data-action="accept" data-id="' + b.id + '">Accept</button>'
                + '<button class="btn btn-danger btn-sm" data-action="reject" data-id="' + b.id + '">Reject</button>'
                + '<a class="btn btn-outline btn-sm" href="' + cfg.chatBase + '?booking_id=' + b.id + '">View</a>'
                + '</div></div>';
        }).join('');
        panel.innerHTML = html;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
        });
    }

    async function poll(initial) {
        try {
            const r = await fetch(cfg.fetchUrl, { credentials: 'same-origin' });
            if (!r.ok) return;
            const data = await r.json();
            if (!data.ok) return;
            const items = data.bookings || [];
            renderPanel(items);
            renderCount(items.length);

            // Detect newly arrived items vs lastSeenIds
            const ids = new Set(items.map(function (b) { return b.id; }));
            let hasNew = false;
            ids.forEach(function (id) { if (!lastSeenIds.has(id)) hasNew = true; });
            if (!initial && hasNew) beep();
            lastSeenIds = ids;
        } catch (_) {}
    }

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const id = btn.dataset.id;
        const action = btn.dataset.action;
        if (!id || !action) return;
        btn.disabled = true;
        const fd = new FormData();
        fd.append('action', action);
        fd.append('booking_id', id);
        fd.append('csrf', cfg.csrf);
        try {
            const r = await fetch(cfg.actionUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (data.ok) {
                window.toast(action === 'accept' ? 'Job accepted.' : 'Request rejected.', 'success');
                poll(true);
            } else {
                window.toast(data.error || 'Failed.', 'error');
                btn.disabled = false;
            }
        } catch (err) {
            window.toast('Network error.', 'error');
            btn.disabled = false;
        }
    });

    poll(true);
    setInterval(poll, 10000);
})();
