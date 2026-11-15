/**
 * PRDRWNDN® — "Get the app" banner and how-to sheet.
 *
 * Banner: phones only, appears after a few seconds or a little scrolling,
 *   never on cart / checkout / account, never inside the installed app.
 *   Closing it (×) or closing the sheet hides it for 30 days on that phone.
 * Sheet: opens only when someone taps the banner or "Get the app" in the
 *   footer — it never opens by itself.
 * Android / desktop Chrome: uses the browser's own install prompt when offered.
 */
(function () {
  'use strict';

  var banner = document.getElementById('prd-app-banner');
  var sheet  = document.getElementById('prd-app-sheet');
  if (!sheet) return;

  var KEY = 'prdAppHiddenUntil';
  var SNOOZE_DAYS = 30;
  var DAY = 864e5;

  var ua = navigator.userAgent || '';
  var isIOS = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  var isAndroid = /Android/.test(ua);
  var isPhone = isIOS || isAndroid || window.matchMedia('(pointer: coarse) and (max-width: 820px)').matches;
  var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  var platform = isIOS ? 'ios' : isAndroid ? 'android' : 'desktop';

  var installEvent = null;
  var lastFocus = null;
  var installBtn = sheet.querySelector('[data-prd-app-install]');
  var panel = sheet.querySelector('.prd-app-panel');

  sheet.setAttribute('data-platform', platform);
  if (isStandalone) document.documentElement.classList.add('prd-standalone');

  /* ── Remembering "closed" ── */
  function read() { try { return parseInt(localStorage.getItem(KEY) || '0', 10) || 0; } catch (e) { return 0; } }
  function hideFor(days) { try { localStorage.setItem(KEY, String(Date.now() + days * DAY)); } catch (e) {} }
  function isHidden() { return read() > Date.now(); }

  /* ── Banner ── */
  function showBanner() {
    if (!banner || !banner.hidden || isHidden()) return;
    banner.hidden = false;
    requestAnimationFrame(function () { requestAnimationFrame(function () { banner.classList.add('is-in'); }); });
  }
  function hideBanner() {
    if (!banner || banner.hidden) return;
    banner.classList.remove('is-in');
    setTimeout(function () { banner.hidden = true; }, 300);
  }
  function dismiss() {
    hideFor(SNOOZE_DAYS);
    hideBanner();
  }

  /* ── Sheet ── */
  function openSheet() {
    if (!sheet.hidden) return;
    lastFocus = document.activeElement;
    hideBanner();
    sheet.hidden = false;
    document.documentElement.classList.add('prd-app-lock');
    requestAnimationFrame(function () { requestAnimationFrame(function () { sheet.classList.add('is-open'); }); });
    setTimeout(function () { if (panel) panel.focus({ preventScroll: true }); }, 50);
  }
  function closeSheet() {
    if (sheet.hidden) return;
    dismiss();
    sheet.classList.remove('is-open');
    document.documentElement.classList.remove('prd-app-lock');
    setTimeout(function () { sheet.hidden = true; }, 300);
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
    if (location.hash === '#get-app' && history.replaceState) {
      history.replaceState(null, '', location.pathname + location.search);
    }
  }

  /* ── Install ── */
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    installEvent = e;
    sheet.classList.add('can-install');
    if (installBtn) installBtn.hidden = false;
  });
  window.addEventListener('appinstalled', function () {
    hideFor(3650);
    hideBanner();
    closeSheet();
  });
  function install() {
    if (!installEvent) { openSheet(); return; }
    var ev = installEvent;
    installEvent = null;
    sheet.classList.remove('can-install');
    if (installBtn) installBtn.hidden = true;
    ev.prompt();
    ev.userChoice.then(function (choice) {
      if (choice && choice.outcome === 'accepted') { hideFor(3650); hideBanner(); closeSheet(); }
    }).catch(function () {});
  }

  /* ── Clicks ── */
  document.addEventListener('click', function (e) {
    var t = e.target;
    if (!t || !t.closest) return;
    if (t.closest('[data-prd-app-dismiss]')) { e.preventDefault(); dismiss(); return; }
    if (t.closest('[data-prd-app-close]')) { e.preventDefault(); closeSheet(); return; }
    if (t.closest('[data-prd-app-install]')) { e.preventDefault(); install(); return; }
    if (t.closest('[data-prd-app-open]')) { e.preventDefault(); if (installEvent) install(); else openSheet(); return; }
    if (t.closest('[data-prd-app-sheet]')) { e.preventDefault(); openSheet(); }
  });
  document.addEventListener('keydown', function (e) {
    if (sheet.hidden) return;
    if (e.key === 'Escape') { closeSheet(); return; }
    if (e.key === 'Tab') {
      var items = [].slice.call(sheet.querySelectorAll('button:not([hidden]), a[href]'));
      if (!items.length) return;
      var first = items[0], last = items[items.length - 1];
      if (e.shiftKey && (document.activeElement === first || document.activeElement === panel)) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  /* ── Open from a link like proudrwandan.com/#get-app ── */
  if (location.hash === '#get-app' && !isStandalone) openSheet();

  /* ── When to show the banner ── */
  var body = document.body.classList;
  var busyPage = body.contains('woocommerce-cart') || body.contains('woocommerce-checkout') || body.contains('woocommerce-account');
  if (banner && isPhone && !isStandalone && !busyPage && !isHidden()) {
    var timer = setTimeout(showBanner, 6000);
    var onScroll = function () {
      if (window.scrollY > 400) {
        clearTimeout(timer);
        window.removeEventListener('scroll', onScroll);
        showBanner();
      }
    };
    window.addEventListener('scroll', onScroll, { passive: true });
  }
})();
