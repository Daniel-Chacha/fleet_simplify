/* FleetSimplify VBMS — main.js
   Toasts, password show/hide, inline form validation, modal helpers. */

(function () {
    'use strict';

    // ----- Toasts -----
    function ensureToastRoot() {
        let root = document.getElementById('toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'toast-root';
            document.body.appendChild(root);
        }
        return root;
    }
    window.toast = function (msg, type) {
        type = type || 'info';
        const root = ensureToastRoot();
        const el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.textContent = msg;
        root.appendChild(el);
        setTimeout(function () {
            el.style.transition = 'opacity .25s ease, transform .25s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            setTimeout(function () { el.remove(); }, 280);
        }, 3500);
    };

    // ----- Auto-show flash messages from data attributes on <body> -----
    document.addEventListener('DOMContentLoaded', function () {
        const ds = document.body.dataset || {};
        if (ds.flashSuccess) window.toast(ds.flashSuccess, 'success');
        if (ds.flashError) window.toast(ds.flashError, 'error');
        if (ds.flashInfo) window.toast(ds.flashInfo, 'info');
    });

    // ----- Password show/hide -----
    document.addEventListener('click', function (e) {
        const t = e.target;
        if (t && t.classList && t.classList.contains('password-toggle')) {
            const wrap = t.closest('.password-wrap');
            if (!wrap) return;
            const inp = wrap.querySelector('input');
            if (!inp) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            t.textContent = inp.type === 'password' ? 'Show' : 'Hide';
        }
    });

    // ----- Inline validation helpers -----
    function setError(field, msg) {
        const wrap = field.closest('.form-row') || field.parentElement;
        if (!wrap) return;
        let err = wrap.querySelector('.field-error');
        if (!err) {
            err = document.createElement('span');
            err.className = 'field-error';
            wrap.appendChild(err);
        }
        err.textContent = msg || '';
    }
    function clearError(field) { setError(field, ''); }

    function validateRequired(field) {
        if (!field.value.trim()) { setError(field, 'This field is required.'); return false; }
        clearError(field); return true;
    }
    function validateEmail(field) {
        if (!field.value.trim()) { setError(field, 'Email is required.'); return false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim())) { setError(field, 'Enter a valid email.'); return false; }
        clearError(field); return true;
    }
    function validateMobile(field) {
        const v = field.value.trim();
        if (!/^(07|01)[0-9]{8}$/.test(v)) { setError(field, 'Mobile must be 10 digits and start with 07 or 01.'); return false; }
        clearError(field); return true;
    }
    function validatePassword(field) {
        if (field.value.length < 6) { setError(field, 'Password must be at least 6 characters.'); return false; }
        clearError(field); return true;
    }
    function validateMatch(p1, p2) {
        if (p1.value !== p2.value) { setError(p2, 'Passwords do not match.'); return false; }
        clearError(p2); return true;
    }

    // Apply real-time validation rules based on data attributes:
    // data-validate="email|mobile|password|required" / data-match="#otherPassword"
    document.addEventListener('input', function (e) {
        const f = e.target;
        if (!f.dataset || !f.dataset.validate) return;
        const v = f.dataset.validate;
        if (v === 'email') validateEmail(f);
        else if (v === 'mobile') validateMobile(f);
        else if (v === 'password') validatePassword(f);
        else if (v === 'required') validateRequired(f);
        if (f.dataset.match) {
            const other = document.querySelector(f.dataset.match);
            if (other) validateMatch(other, f);
        }
    });

    // Pre-submit validation on forms with [data-validate-form]
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.dataset || !form.hasAttribute('data-validate-form')) return;
        let ok = true;
        form.querySelectorAll('[data-validate]').forEach(function (f) {
            const v = f.dataset.validate;
            let pass = true;
            if (v === 'email') pass = validateEmail(f);
            else if (v === 'mobile') pass = validateMobile(f);
            else if (v === 'password') pass = validatePassword(f);
            else if (v === 'required') pass = validateRequired(f);
            if (f.dataset.match) {
                const other = document.querySelector(f.dataset.match);
                if (other) pass = validateMatch(other, f) && pass;
            }
            ok = ok && pass;
        });
        if (!ok) {
            e.preventDefault();
            window.toast('Please fix the highlighted fields.', 'error');
        }
    });

    // ----- Modal helpers -----
    window.openModal = function (id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('is-open');
    };
    window.closeModal = function (id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('is-open');
    };
    document.addEventListener('click', function (e) {
        if (e.target && e.target.matches('[data-modal-close]')) {
            const m = e.target.closest('.modal-backdrop');
            if (m) m.classList.remove('is-open');
        }
        if (e.target && e.target.classList && e.target.classList.contains('modal-backdrop')) {
            e.target.classList.remove('is-open');
        }
    });

    // ----- Password requirement live checklist -----
    // Markup expected:
    //   <input type="password" data-pw-rules="#pw-rules-1">
    //   <div id="pw-rules-1" class="pw-rules">
    //     <span class="pw-rule" data-rule="length">…</span> etc.
    //   </div>
    const PW_TESTS = {
        length:  function (v) { return v.length >= 8; },
        upper:   function (v) { return /[A-Z]/.test(v); },
        lower:   function (v) { return /[a-z]/.test(v); },
        number:  function (v) { return /[0-9]/.test(v); },
        special: function (v) { return /[^A-Za-z0-9]/.test(v); }
    };
    window.fsCheckPassword = function (val) {
        const out = {};
        Object.keys(PW_TESTS).forEach(function (k) { out[k] = PW_TESTS[k](val || ''); });
        out.allMet = Object.keys(PW_TESTS).every(function (k) { return out[k]; });
        return out;
    };
    function updatePwRules(input) {
        const target = input.dataset.pwRules;
        if (!target) return;
        const box = document.querySelector(target);
        if (!box) return;
        const r = window.fsCheckPassword(input.value);
        let allMet = true;
        box.querySelectorAll('.pw-rule').forEach(function (el) {
            const k = el.dataset.rule;
            const ok = !!r[k];
            el.classList.toggle('met', ok);
            if (!ok) allMet = false;
        });
        box.classList.toggle('all-met', allMet);
    }
    document.addEventListener('input', function (e) {
        if (e.target && e.target.dataset && e.target.dataset.pwRules) {
            updatePwRules(e.target);
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input[data-pw-rules]').forEach(function (i) { updatePwRules(i); });
    });

    // ----- Card masking helper for payment form -----
    window.maskCardInput = function (inp) {
        inp.addEventListener('input', function () {
            let v = inp.value.replace(/\D/g, '').slice(0, 16);
            inp.value = v.replace(/(.{4})/g, '$1 ').trim();
        });
    };
})();
