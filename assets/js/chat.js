/* FleetSimplify VBMS — chat.js
   Polls /api/get-messages.php every 5s, posts to /api/send-message.php. */

(function () {
    'use strict';
    const cfg = window.__chat;
    if (!cfg) return;

    const body = document.getElementById('chat-body');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    let lastId = 0;

    function bubble(m) {
        const div = document.createElement('div');
        const mine = m.sender_type === cfg.role;
        div.className = 'bubble ' + (mine ? 'sent' : 'received');
        const text = document.createElement('div');
        text.textContent = m.message;
        div.appendChild(text);
        const ts = document.createElement('small');
        ts.textContent = m.sent_at;
        div.appendChild(ts);
        body.appendChild(div);
    }

    function scrollBottom() { body.scrollTop = body.scrollHeight; }

    async function poll() {
        try {
            const r = await fetch(cfg.getUrl + '?booking_id=' + cfg.bookingId + '&since_id=' + lastId, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            if (!r.ok) return;
            const data = await r.json();
            if (data.ok && data.messages && data.messages.length) {
                data.messages.forEach(function (m) {
                    bubble(m);
                    lastId = Math.max(lastId, m.id);
                });
                scrollBottom();
            }
        } catch (_) {}
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const msg = input.value.trim();
        if (!msg) return;
        input.disabled = true;
        try {
            const fd = new FormData();
            fd.append('booking_id', cfg.bookingId);
            fd.append('message', msg);
            fd.append('csrf', cfg.csrf);
            const r = await fetch(cfg.sendUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = await r.json();
            if (data.ok) {
                input.value = '';
                await poll();
            } else {
                window.toast(data.error || 'Failed to send.', 'error');
            }
        } catch (err) {
            window.toast('Network error.', 'error');
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    // Initial scroll + start polling
    scrollBottom();
    // Capture last id from already-rendered server-side bubbles
    if (cfg.lastId) lastId = parseInt(cfg.lastId, 10) || 0;
    setInterval(poll, 5000);
})();
