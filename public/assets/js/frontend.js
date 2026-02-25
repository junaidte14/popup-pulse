/**
 * Popup Pulse — Frontend Engine
 *
 * Handles: trigger detection, frequency control, animations,
 * exit intent, scroll trigger, click trigger, auto-close, focus trap, a11y
 *
 * No dependencies. Vanilla JS.
 */

/* global ppulseFE */
(function () {
    'use strict';

    if (typeof ppulseFE === 'undefined' || !ppulseFE.popups || !ppulseFE.popups.length) {
        return;
    }

    // ── Utilities ─────────────────────────────────────────────────────────────

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
    }

    function hasDnt() {
        return ppulseFE.respectDnt && (navigator.doNotTrack === '1' || window.doNotTrack === '1');
    }

    function getScrollbarWidth() {
        return window.innerWidth - document.documentElement.clientWidth;
    }

    // ── Frequency / impression checks ─────────────────────────────────────────

    function hasExceededFrequency(cfg) {
        if (hasDnt() && cfg.frequencyType !== 'always' && cfg.frequencyType !== 'per_page') {
            return false; // DNT: can't track, so just show (or block — user's choice via setting)
        }

        var cookieName = ppulseFE.cookiePrefix + cfg.id;

        switch (cfg.frequencyType) {
            case 'always':
                return false;

            case 'per_page':
                return !!window['_ppulse_shown_' + cfg.id];

            case 'per_session':
                return !!sessionStorage.getItem('ppulse_' + cfg.id);

            case 'once':
                return !!getCookie(cookieName);

            case 'limited':
                var count = parseInt(getCookie(cookieName) || '0', 10);
                return count >= cfg.frequencyLimit;

            default:
                return false;
        }
    }

    function recordImpression(cfg) {
        var cookieName = ppulseFE.cookiePrefix + cfg.id;

        if (cfg.frequencyType === 'per_page') {
            window['_ppulse_shown_' + cfg.id] = true;
            return;
        }
        if (cfg.frequencyType === 'per_session') {
            sessionStorage.setItem('ppulse_' + cfg.id, '1');
            return;
        }
        if (cfg.frequencyType === 'once') {
            if (!hasDnt()) setCookie(cookieName, '1', cfg.frequencyDays);
            return;
        }
        if (cfg.frequencyType === 'limited') {
            var count = parseInt(getCookie(cookieName) || '0', 10);
            if (!hasDnt()) setCookie(cookieName, String(count + 1), cfg.frequencyDays);
            return;
        }

        // Report to server (for logged-in user meta tracking)
        if (ppulseFE.ajaxUrl && cfg.nonce) {
            var body = new FormData();
            body.append('action',   'ppulse_record_impression');
            body.append('popup_id', cfg.id);
            body.append('nonce',    cfg.nonce);
            fetch(ppulseFE.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .catch(function () {});
        }
    }

    // ── Focus trap ────────────────────────────────────────────────────────────

    var FOCUSABLE = 'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

    function trapFocus(wrapper) {
        var elements = wrapper.querySelectorAll(FOCUSABLE);
        if (!elements.length) return;
        var first = elements[0];
        var last  = elements[elements.length - 1];

        wrapper._ppFocusTrap = function (e) {
            if (e.key !== 'Tab') return;
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
            }
        };
        wrapper.addEventListener('keydown', wrapper._ppFocusTrap);

        // Defer focus to allow animation to start
        setTimeout(function () {
            if (document.activeElement && wrapper.contains(document.activeElement)) return;
            first.focus();
        }, 100);
    }

    function releaseFocusTrap(wrapper) {
        if (wrapper._ppFocusTrap) {
            wrapper.removeEventListener('keydown', wrapper._ppFocusTrap);
            delete wrapper._ppFocusTrap;
        }
    }

    // ── Body scroll lock ──────────────────────────────────────────────────────

    var _lockCount = 0;
    var _prevFocus = null;

    function lockBodyScroll() {
        if (_lockCount === 0) {
            var sbw = getScrollbarWidth();
            document.documentElement.style.setProperty('--ppulse-scrollbar-width', sbw + 'px');
            document.body.classList.add('ppulse-no-scroll');
        }
        _lockCount++;
    }

    function unlockBodyScroll(force) {
        if (force) { _lockCount = 0; }
        else { _lockCount = Math.max(0, _lockCount - 1); }
        if (_lockCount === 0) {
            document.body.classList.remove('ppulse-no-scroll');
            document.documentElement.style.removeProperty('--ppulse-scrollbar-width');
        }
    }

    // ── Popup open / close ────────────────────────────────────────────────────

    function openPopup(cfg) {
        var wrapper = document.getElementById('ppulse-' + cfg.id);
        if (!wrapper || wrapper.getAttribute('data-ppulse-open') === 'true') return;

        var isBar = wrapper.classList.contains('ppulse-popup-wrapper--bar');

        // Reveal
        wrapper.removeAttribute('hidden');
        wrapper.style.display = '';
        wrapper.setAttribute('data-ppulse-open', 'true');

        // Force reflow then add open class
        void wrapper.offsetWidth;
        wrapper.classList.add('is-open');

        // Record + prevent future triggers
        recordImpression(cfg);

        if (!isBar) {
            lockBodyScroll();
            _prevFocus = document.activeElement;
            trapFocus(wrapper);
        }

        // Auto-close
        if (cfg.autoCloseEnabled && cfg.autoCloseSeconds > 0) {
            wrapper._ppAutoCloseTimer = setTimeout(function () {
                closePopup(cfg, wrapper);
            }, cfg.autoCloseSeconds);
        }

        // Announce to screen readers
        wrapper.setAttribute('aria-hidden', 'false');

        // Dispatch event
        document.dispatchEvent(new CustomEvent('ppulse:open', { detail: { id: cfg.id } }));
    }

    function closePopup(cfg, wrapper) {
        if (!wrapper) wrapper = document.getElementById('ppulse-' + cfg.id);
        if (!wrapper || wrapper.getAttribute('data-ppulse-open') !== 'true') return;

        if (wrapper._ppAutoCloseTimer) {
            clearTimeout(wrapper._ppAutoCloseTimer);
            delete wrapper._ppAutoCloseTimer;
        }

        var popup  = wrapper.querySelector('.ppulse-popup');
        var isBar  = wrapper.classList.contains('ppulse-popup-wrapper--bar');

        wrapper.classList.add('is-closing');
        if (popup) popup.classList.add('is-closing');

        var animDuration = 250;

        setTimeout(function () {
            wrapper.classList.remove('is-open', 'is-closing');
            if (popup) popup.classList.remove('is-closing');
            wrapper.setAttribute('data-ppulse-open', 'false');
            wrapper.style.display = 'none';
            wrapper.setAttribute('hidden', '');
            wrapper.setAttribute('aria-hidden', 'true');

            releaseFocusTrap(wrapper);

            if (!isBar) {
                unlockBodyScroll();
                if (_prevFocus && typeof _prevFocus.focus === 'function') {
                    _prevFocus.focus();
                    _prevFocus = null;
                }
            }

            document.dispatchEvent(new CustomEvent('ppulse:close', { detail: { id: cfg.id } }));
        }, animDuration);
    }

    // ── Trigger setup ─────────────────────────────────────────────────────────

    function setupTriggers(cfg) {
        // Check frequency before setting up any trigger
        if (hasExceededFrequency(cfg)) return;

        var triggered = false;

        function fire() {
            if (triggered) return;
            triggered = true;
            openPopup(cfg);
        }

        // Primary trigger
        switch (cfg.triggerType) {
            case 'pageload':
                fire();
                break;

            case 'delay':
                setTimeout(fire, cfg.triggerDelay || 0);
                break;

            case 'scroll':
                if (cfg.triggerScrollPct > 0) {
                    setupScrollTrigger(cfg, fire);
                }
                break;

            case 'click':
                if (cfg.triggerClickSelector) {
                    setupClickTrigger(cfg, fire);
                }
                break;

            case 'exit':
                // Will be handled by exit intent below
                break;
        }

        // Secondary: exit intent (additive for delay/scroll triggers too)
        if (cfg.exitIntent) {
            setupExitIntent(cfg, function () {
                if (triggered) return;
                triggered = true;
                openPopup(cfg);
            });
        }
    }

    // ── Scroll trigger ────────────────────────────────────────────────────────

    function setupScrollTrigger(cfg, callback) {
        var ticking = false;

        function checkScroll() {
            var scrolled  = window.scrollY || window.pageYOffset;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            if (docHeight <= 0) return;
            var pct = (scrolled / docHeight) * 100;
            if (pct >= cfg.triggerScrollPct) {
                window.removeEventListener('scroll', onScroll);
                callback();
            }
        }

        function onScroll() {
            if (!ticking) {
                requestAnimationFrame(function () {
                    checkScroll();
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        checkScroll(); // Check immediately in case already scrolled
    }

    // ── Click trigger ─────────────────────────────────────────────────────────

    function setupClickTrigger(cfg, callback) {
        // Event delegation — handles dynamically added elements too
        document.addEventListener('click', function handler(e) {
            if (e.target.closest(cfg.triggerClickSelector)) {
                e.preventDefault();
                document.removeEventListener('click', handler);
                callback();
            }
        });
    }

    // ── Exit intent ───────────────────────────────────────────────────────────

    var _exitBound = false;
    var _exitCallbacks = [];

    function setupExitIntent(cfg, callback) {
        _exitCallbacks.push({ cfg: cfg, callback: callback });

        if (_exitBound) return;
        _exitBound = true;

        var threshold   = 10;   // px from top
        var cooldown    = false;
        var minTime     = 2000; // must be on page for at least 2s
        var startTime   = Date.now();
        var lastY       = 0;

        document.addEventListener('mousemove', function (e) {
            var y = e.clientY;
            lastY = y;
        });

        document.addEventListener('mouseleave', function (e) {
            if (cooldown) return;
            if (Date.now() - startTime < minTime) return;
            if (e.clientY > threshold) return;

            cooldown = true;
            setTimeout(function () { cooldown = false; }, 3000);

            _exitCallbacks.forEach(function (item) {
                if (!hasExceededFrequency(item.cfg)) {
                    item.callback();
                }
            });
        });

        // Mobile: back button / page hide
        window.addEventListener('pagehide', function () {
            _exitCallbacks.forEach(function (item) {
                if (!hasExceededFrequency(item.cfg)) {
                    item.callback();
                }
            });
        });
    }

    // ── Close button & overlay click ──────────────────────────────────────────

    function setupCloseHandlers() {
        // Close button
        document.addEventListener('click', function (e) {
            var closeBtn = e.target.closest('.ppulse-close');
            if (closeBtn) {
                var wrapper = closeBtn.closest('.ppulse-popup-wrapper');
                if (wrapper) {
                    var id  = parseInt(wrapper.getAttribute('data-ppulse-id'), 10);
                    var cfg = getCfgById(id);
                    if (cfg) closePopup(cfg, wrapper);
                }
            }
        });

        // Overlay click
        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('ppulse-overlay')) return;
            var wrapper = e.target.closest('.ppulse-popup-wrapper');
            if (!wrapper) return;
            var id  = parseInt(wrapper.getAttribute('data-ppulse-id'), 10);
            var cfg = getCfgById(id);
            if (cfg && cfg.overlayClickClose) closePopup(cfg, wrapper);
        });

        // ppulse-close-trigger class (e.g. inside cookie notice accept btn)
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.ppulse-close-trigger');
            if (trigger) {
                var wrapper = trigger.closest('.ppulse-popup-wrapper');
                if (wrapper) {
                    var id  = parseInt(wrapper.getAttribute('data-ppulse-id'), 10);
                    var cfg = getCfgById(id);
                    if (cfg) closePopup(cfg, wrapper);
                }
            }
        });

        // Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('.ppulse-popup-wrapper[data-ppulse-open="true"]');
            if (!open) return;
            var id  = parseInt(open.getAttribute('data-ppulse-id'), 10);
            var cfg = getCfgById(id);
            if (cfg && cfg.showCloseBtn) closePopup(cfg, open);
        });
    }

    function getCfgById(id) {
        return ppulseFE.popups.find(function (c) { return c.id === id; }) || null;
    }

    // ── Staggered init (multiple popups) ─────────────────────────────────────

    function initAll() {
        setupCloseHandlers();

        var stagger = 0;
        ppulseFE.popups.forEach(function (cfg, idx) {
            // Add small stagger so multiple popups don't fire simultaneously
            setTimeout(function () {
                setupTriggers(cfg);
            }, stagger);
            // Only stagger delay-type triggers to prevent collision
            if (cfg.triggerType === 'delay') {
                stagger += 300;
            }
        });
    }

    // ── Boot ──────────────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    // Expose public API
    window.PopupPulse = {
        open:  function (id) { var cfg = getCfgById(id); if (cfg) openPopup(cfg); },
        close: function (id) { var cfg = getCfgById(id); if (cfg) closePopup(cfg); },
    };

}());
