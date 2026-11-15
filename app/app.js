/**
 * PRDRWNDN® app — /app/
 *
 * One page, four tabs (Home, Shop, Cart, Account) plus a product sheet.
 *  • Products: /wp-json/prdrwndn/v1/catalog (sizes, scents, stock in one call)
 *  • Cart:     WooCommerce Store API — the same cart as the website
 *  • Account:  sign in with password or Face ID (passkeys), orders, tracking, wishlist
 *  • Pay:      the site's checkout in "app mode" (Apple Pay / Google Pay / card)
 */
(function () {
  'use strict';

  var C = window.PRD_APP || {};
  var S = {
    products: [], bySlug: {}, byId: {}, parentOf: {},
    cart: null, storeNonce: '',
    session: { user: null, nonce: '', passkeys: 0, wishlist: [] },
    wish: [], tab: 'home', filter: 'all', query: '',
    orders: null, ready: false, bio: false, lastBase: '#home'
  };
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return [].slice.call((root || document).querySelectorAll(sel)); };

  try { sessionStorage.removeItem('prdInApp'); } catch (e) {}

  /* ════════ Helpers ════════ */
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function money(n) {
    n = Number(n) || 0;
    var s = n.toFixed(2).replace(/\.00$/, '');
    return (n < 0 ? '−' : '') + (C.currency || '$') + s.replace('-', '');
  }
  function minor(v, unit) { return (Number(v) || 0) / Math.pow(10, unit == null ? 2 : unit); }
  function store(k, v) { try { if (v === undefined) return localStorage.getItem(k); if (v === null) localStorage.removeItem(k); else localStorage.setItem(k, v); } catch (e) { return null; } }
  function b64u(buf) { var s = ''; new Uint8Array(buf).forEach(function (b) { s += String.fromCharCode(b); }); return btoa(s).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, ''); }
  function unb64u(s) { s = s.replace(/-/g, '+').replace(/_/g, '/'); while (s.length % 4) s += '='; var bin = atob(s), out = new Uint8Array(bin.length); for (var i = 0; i < bin.length; i++) out[i] = bin.charCodeAt(i); return out.buffer; }
  var ua = navigator.userAgent || '';
  var isIOS = /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
  var bioName = isIOS ? 'Face ID' : /Macintosh/.test(ua) ? 'Touch ID' : /Android/.test(ua) ? 'fingerprint' : 'passkey';

  var I = {
    heart: '<svg viewBox="0 0 24 24"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>',
    close: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>',
    share: '<svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M8 7l4-4 4 4"/><path d="M6 11H5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8a1 1 0 0 0-1-1h-1"/></svg>',
    chev: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>',
    search: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>',
    box: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/></svg>',
    truck: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M2 6h12v10H2zM14 10h4l3 3v3h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>',
    pin: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
    wa: '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.7.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3z"/></svg>',
    mail: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>',
    phone: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>',
    face: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M9 9v1M15 9v1M12 9v4h-1M9.5 16a3.5 3.5 0 0 0 5 0"/></svg>',
    out: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/></svg>',
    check: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg>',
    bag: '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
    lock: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>'
  };

  /* ════════ Network ════════ */
  function rest(path, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json' };
    if (opts.body) headers['Content-Type'] = 'application/json';
    if (S.session.nonce && !opts.noNonce) headers['X-WP-Nonce'] = S.session.nonce;
    return fetch(C.rest + path, { method: opts.method || 'GET', credentials: 'same-origin', headers: headers, body: opts.body ? JSON.stringify(opts.body) : undefined })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) { var e = new Error(j && j.message || 'Something went wrong.'); e.status = r.status; throw e; } return j; }); });
  }
  function storeApi(path, opts, retried) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json' };
    if (opts.body) headers['Content-Type'] = 'application/json';
    if (S.storeNonce) headers['Nonce'] = S.storeNonce;
    return fetch(C.store + path, { method: opts.method || 'GET', credentials: 'same-origin', headers: headers, body: opts.body ? JSON.stringify(opts.body) : undefined })
      .then(function (r) {
        var n = r.headers.get('Nonce'); if (n) S.storeNonce = n;
        return r.json().catch(function () { return {}; }).then(function (j) {
          if (!r.ok) {
            if (!retried && j && /nonce/i.test(j.code || '')) return storeApi('cart', {}).then(function () { return storeApi(path, opts, true); });
            var e = new Error(j && j.message ? j.message.replace(/<[^>]+>/g, '') : 'Something went wrong.'); e.status = r.status; throw e;
          }
          return j;
        });
      });
  }
  function loadSession() {
    return fetch(C.ajax + '?action=prdrwndn_app_session', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (s) { S.session = s || S.session; mergeWishlist(); return s; })
      .catch(function () { return S.session; });
  }
  function loadCatalog() {
    return rest('catalog', { noNonce: true }).then(function (list) {
      S.products = Array.isArray(list) ? list : [];
      S.bySlug = {}; S.byId = {}; S.parentOf = {};
      S.products.forEach(function (p) {
        S.bySlug[p.slug] = p; S.byId[p.id] = p;
        (p.variants || []).forEach(function (v) { S.parentOf[v.id] = p.id; });
      });
      try { localStorage.setItem('prdCatalog', JSON.stringify(S.products)); } catch (e) {}
    }).catch(function () {
      try { var cached = JSON.parse(localStorage.getItem('prdCatalog') || '[]'); if (cached.length && !S.products.length) { S.products = cached; cached.forEach(function (p) { S.bySlug[p.slug] = p; S.byId[p.id] = p; (p.variants || []).forEach(function (v) { S.parentOf[v.id] = p.id; }); }); } } catch (e) {}
    });
  }
  function loadCart() {
    return storeApi('cart').then(function (c) { S.cart = c; updateBadge(); return c; }).catch(function () { return S.cart; });
  }

  /* ════════ Wishlist ════════ */
  function localWish() { try { return JSON.parse(store('prdWish') || '[]').map(Number).filter(Boolean); } catch (e) { return []; } }
  function mergeWishlist() {
    var local = localWish();
    if (S.session.user) {
      var server = S.session.wishlist || [];
      var merged = server.slice(); local.forEach(function (id) { if (merged.indexOf(id) < 0) merged.push(id); });
      S.wish = merged;
      if (merged.length !== server.length) saveWish();
    } else {
      S.wish = local;
    }
  }
  var wishTimer = null;
  function saveWish() {
    store('prdWish', JSON.stringify(S.wish));
    if (!S.session.user) return;
    clearTimeout(wishTimer);
    wishTimer = setTimeout(function () { rest('wishlist', { method: 'POST', body: { ids: S.wish } }).catch(function () {}); }, 400);
  }
  function toggleWish(id) {
    id = Number(id);
    var i = S.wish.indexOf(id);
    if (i >= 0) S.wish.splice(i, 1); else S.wish.unshift(id);
    saveWish();
    $$('[data-wish="' + id + '"]').forEach(function (b) { b.classList.toggle('is-on', i < 0); b.setAttribute('aria-pressed', i < 0 ? 'true' : 'false'); });
    if (i < 0) toast('Saved to your wishlist', '#wishlist', 'View');
    if (location.hash === '#wishlist') renderSub();
  }

  /* ════════ Product helpers ════════ */
  function inCat(p, key) {
    var c = (C.cats || []).filter(function (x) { return x.key === key; })[0];
    return c ? p.cats.indexOf(c.slug) >= 0 : false;
  }
  function priceText(p) {
    if (p.priceMax && p.priceMax !== p.price) return money(p.price) + ' – ' + money(p.priceMax);
    return money(p.price);
  }
  function lowNote(p) {
    var low = null;
    (p.variants || []).forEach(function (v) {
      if (v.inStock && v.stock !== null && v.stock > 0 && v.stock <= 2 && (!low || v.stock < low.stock)) low = v;
    });
    if (!low) return '';
    var label = variantLabel(p, low);
    return 'Only ' + low.stock + ' left in ' + label;
  }
  function variantLabel(p, v) {
    var parts = [];
    (p.options || []).forEach(function (o) {
      var val = v.attrs[o.key];
      var match = o.values.filter(function (x) { return x.value === val; })[0];
      if (match) parts.push(match.label);
    });
    return parts.join(' / ');
  }
  function card(p) {
    var img = p.images[0] ? p.images[0].thumb || p.images[0].src : '';
    var on = S.wish.indexOf(p.id) >= 0;
    var badge = !p.inStock ? '<span class="badge-sold">Sold out</span>' : (p.jersey && C.deal && C.deal.on ? '<span class="badge-deal">' + esc(C.deal.label) + '</span>' : '');
    var note = p.inStock ? lowNote(p) : '';
    return '<div class="card"><a href="#p/' + esc(p.slug) + '"><div class="card-img">' + (img ? '<img src="' + esc(img) + '" alt="' + esc(p.name) + '" loading="lazy" decoding="async"/>' : '') + badge + '</div>' +
      '<div class="card-name">' + esc(p.name) + '</div><div class="card-price">' + priceText(p) + '</div>' + (note ? '<div class="card-note">' + esc(note) + '</div>' : '') + '</a>' +
      '<button class="heart' + (on ? ' is-on' : '') + '" type="button" data-wish="' + p.id + '" aria-pressed="' + on + '" aria-label="Save ' + esc(p.name) + '">' + I.heart + '</button></div>';
  }
  function skeletonCards(n) { var h = ''; for (var i = 0; i < n; i++) h += '<div><div class="skeleton" style="aspect-ratio:1"></div><div class="skeleton" style="height:14px;margin-top:10px;width:80%"></div></div>'; return h; }

  /* ════════ Views ════════ */
  function renderHome() {
    var v = $('#view-home');
    var jerseys = S.products.filter(function (p) { return p.jersey; });
    var fresh = S.products.filter(function (p) { return inCat(p, 'fresh'); });
    var more = S.products.filter(function (p) { return !p.jersey && !inCat(p, 'fresh'); });
    var a = jerseys[0] && jerseys[0].images[0] ? jerseys[0].images[0].src : C.img + 'izuba-front.webp';
    var b = jerseys[1] && jerseys[1].images[0] ? jerseys[1].images[0].src : C.img + 'imigongo-front.webp';
    var hero = C.hero || {};
    var h = '<div class="hero"><div class="eyebrow">' + esc(hero.eyebrow || 'Jersey Drop 01 · In stock now') + '</div>' +
      '<h2>' + esc(hero.title || 'Wear home.') + '</h2><p>Two football jerseys drawn from Rwanda: the sun from our flag, and the Imigongo art of the east.</p>' +
      '<a class="btn" href="#shop/jerseys">Shop jerseys</a>' +
      '<div class="hero-art"><img src="' + esc(a) + '" alt=""/><img src="' + esc(b) + '" alt=""/></div><span class="stripe"></span></div>';
    if (C.deal && C.deal.on) h += '<a class="deal" href="#shop/jerseys"><strong>' + esc(C.deal.label.toUpperCase()) + '</strong><span>Mix and match any two jerseys. The discount comes off in your cart.</span></a>';
    if (!S.ready) {
      h += '<div class="section"><div class="rail">' + skeletonCards(3) + '</div></div>';
    } else {
      if (jerseys.length) h += '<div class="section"><div class="section-head"><h2>The jerseys</h2><a href="#shop/jerseys">See all</a></div><div class="rail">' + jerseys.map(card).join('') + '</div></div>';
      if (fresh.length) h += '<div class="section"><div class="section-head"><h2>Car fresheners</h2><a href="#shop/fresh">See all</a></div><div class="rail">' + fresh.map(card).join('') + '</div></div>';
      if (more.length) h += '<div class="section"><div class="section-head"><h2>Flags &amp; more</h2><a href="#shop">Shop all</a></div><div class="rail">' + more.map(card).join('') + '</div></div>';
    }
    h += '<div class="section"><div class="chips-info">' +
      (C.pickup ? '<span class="chip-info">' + I.pin + esc(C.pickup) + '</span>' : '') +
      '<span class="chip-info">' + I.box + 'Ships from Phoenix, AZ</span>' +
      (C.whatsapp ? '<a class="chip-info" href="https://wa.me/' + esc(C.whatsapp) + '" target="_blank" rel="noopener">' + I.wa + 'WhatsApp us</a>' : '') +
      '</div></div>';
    v.innerHTML = h;
  }

  function renderShop(gridOnly) {
    var v = $('#view-shop');
    var filters = [{ key: 'all', name: 'All' }].concat(C.cats || []);
    var q = S.query.trim().toLowerCase();
    var rank = function (p) { return p.jersey ? 0 : inCat(p, 'fresh') ? 1 : 2; };
    var list = S.products.slice().sort(function (a, b) { return rank(a) - rank(b); }).filter(function (p) {
      if (S.filter !== 'all' && !inCat(p, S.filter)) return false;
      if (q && (p.name + ' ' + p.short).toLowerCase().indexOf(q) < 0) return false;
      return true;
    });
    var grid;
    if (!S.ready) grid = '<div class="grid">' + skeletonCards(4) + '</div>';
    else if (!list.length) grid = '<div class="empty"><div class="empty-ico">' + I.search + '</div><h3>Nothing here</h3><p>Try another word or category.</p></div>';
    else grid = '<div class="grid">' + list.map(card).join('') + '</div>';
    if (gridOnly && $('#shop-grid')) { $('#shop-grid').innerHTML = grid; return; }
    v.innerHTML = '<div class="search">' + I.search + '<input type="search" id="shop-q" placeholder="Search jerseys, fresheners…" value="' + esc(S.query) + '" enterkeyhint="search" autocomplete="off"/></div>' +
      '<div class="filters">' + filters.map(function (f) { return '<button class="filter' + (S.filter === f.key ? ' is-on' : '') + '" type="button" data-filter="' + esc(f.key) + '">' + esc(f.name) + '</button>'; }).join('') + '</div>' +
      '<div id="shop-grid">' + grid + '</div>';
  }

  function cartJerseyCount() {
    if (!S.cart) return 0;
    return S.cart.items.reduce(function (n, it) { var pid = S.parentOf[it.id] || it.id; var p = S.byId[pid]; return n + (p && p.jersey ? it.quantity : 0); }, 0);
  }
  function renderCart() {
    var v = $('#view-cart');
    var c = S.cart;
    if (!c) { v.innerHTML = '<div class="pad">' + skeletonCards(1) + '</div>'; return; }
    if (!c.items || !c.items.length) {
      v.innerHTML = '<div class="empty"><div class="empty-ico">' + I.bag + '</div><h3>Your cart is empty</h3><p>Jerseys are 2 for $' + esc(C.deal && C.deal.price) + ' — mix and match.</p><a class="btn btn-primary" href="#shop/jerseys">Shop jerseys</a></div>';
      return;
    }
    var unit = c.totals.currency_minor_unit;
    var h = '<div class="cart-list">' + c.items.map(function (it) {
      var img = it.images && it.images[0] ? it.images[0].thumbnail : '';
      var meta = (it.variation || []).map(function (x) { return esc(x.attribute) + ': ' + esc(x.value); }).join(' · ');
      var pid = S.parentOf[it.id] || it.id; var p = S.byId[pid];
      return '<div class="line"><a class="line-img" href="' + (p ? '#p/' + esc(p.slug) : '#cart') + '">' + (img ? '<img src="' + esc(img) + '" alt=""/>' : '') + '</a>' +
        '<div><div class="line-name">' + esc(it.name) + '</div>' + (meta ? '<div class="line-meta">' + meta + '</div>' : '') +
        '<div class="qty"><button type="button" data-qty="' + esc(it.key) + '" data-d="-1" aria-label="One less">−</button><span>' + it.quantity + '</span><button type="button" data-qty="' + esc(it.key) + '" data-d="1" aria-label="One more">+</button></div></div>' +
        '<div><div class="line-price">' + money(minor(it.totals.line_subtotal, unit)) + '</div><button class="line-remove" type="button" data-remove="' + esc(it.key) + '">Remove</button></div></div>';
    }).join('') + '</div>';
    var jc = cartJerseyCount();
    if (C.deal && C.deal.on && jc % 2 === 1) h += '<div class="nudge"><strong>' + esc(C.deal.label) + ':</strong> add one more jersey and the pair costs $' + esc(C.deal.price) + '. <a href="#shop/jerseys">Pick another</a></div>';
    var t = c.totals;
    var rows = '<div><span>Subtotal</span><span>' + money(minor(t.total_items, unit)) + '</span></div>';
    (c.fees || []).forEach(function (f) { var amt = minor(f.totals.total, unit); rows += '<div class="' + (amt < 0 ? 'save' : '') + '"><span>' + esc(f.name) + '</span><span>' + money(amt) + '</span></div>'; });
    if (Number(t.total_discount)) rows += '<div class="save"><span>Coupon</span><span>' + money(-minor(t.total_discount, unit)) + '</span></div>';
    var shipKnown = c.shipping_rates && c.shipping_rates.some(function (pk) { return pk.shipping_rates.some(function (r) { return r.selected; }); });
    rows += '<div><span>Shipping</span><span>' + (shipKnown ? money(minor(t.total_shipping, unit)) : 'At checkout') + '</span></div>';
    if (Number(t.total_tax)) rows += '<div><span>Tax</span><span>' + money(minor(t.total_tax, unit)) + '</span></div>';
    rows += '<div class="grand"><span>' + (shipKnown ? 'Total' : 'Total so far') + '</span><span>' + money(minor(t.total_price, unit)) + '</span></div>';
    h += '<div class="totals">' + rows + (C.pickup ? '<small>' + esc(C.pickup) + ' — choose it at checkout.</small>' : '') + '</div>';
    h += '<div class="pay"><button class="btn btn-primary btn-block" type="button" data-checkout>' + I.lock + ' Checkout</button>' +
      '<div class="pay-note">Pay with <span class="pay-marks"><span>Apple Pay</span><span>Google Pay</span><span>Card</span></span></div></div>';
    v.innerHTML = h;
  }

  function renderAccount() {
    var v = $('#view-account');
    var u = S.session.user;
    var h = '';
    if (!u) {
      var hasKey = store('prdPasskey') === '1' && S.bio;
      h += '<div class="signin"><div class="signin-head"><img src="' + esc(C.img + 'crest.svg') + '" alt=""/><h2>Welcome back</h2><p>Sign in to see your orders and track them.</p></div>';
      if (hasKey) h += '<button class="btn btn-primary btn-block" type="button" data-passkey-login>' + I.face + ' Sign in with ' + esc(bioName) + '</button><div class="or">or use your password</div>';
      h += '<form id="signin-form" novalidate><label class="field"><span>Email</span><input type="email" name="login" autocomplete="username" inputmode="email" required/></label>' +
        '<label class="field"><span>Password</span><input type="password" name="password" autocomplete="current-password" required/></label>' +
        '<p class="form-error" id="signin-error" hidden></p>' +
        '<button class="btn ' + (hasKey ? 'btn-ghost' : 'btn-primary') + ' btn-block" type="submit">Sign in</button></form>' +
        '<div class="signin-links"><a class="link" href="' + esc(C.lostPass) + '">Forgot password?</a>' + (C.canRegister ? '<a class="link" href="' + esc(C.account) + '">Create account</a>' : '') + '</div></div>';
      h += '<div class="list-title">More</div><div class="list">' + rowLink('#wishlist', I.heart.replace('<svg', '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"'), 'Wishlist', S.wish.length ? S.wish.length + ' saved' : '') + contactRows() + '</div>';
      v.innerHTML = h;
      return;
    }
    h += '<div class="acct-card"><span class="avatar">' + esc(u.initials) + '</span><div><strong>Hi, ' + esc(u.name) + '</strong><span>' + esc(u.email) + '</span></div><span class="stripe"></span></div>';
    if (S.bio && !S.session.passkeys && store('prdPasskeyNo') !== '1') {
      h += '<div class="faceid-card"><span class="row-ico">' + I.face + '</span><p>Sign in with ' + esc(bioName) + ' next time. No password needed.</p><button class="btn btn-primary" type="button" data-passkey-add>Turn on</button></div>';
    }
    var orders = S.orders || [];
    h += '<div class="stats"><a class="stat" href="#orders"><b>' + (S.orders ? orders.length : '–') + '</b><span>Orders</span></a><a class="stat" href="#wishlist"><b>' + S.wish.length + '</b><span>Saved</span></a></div>';
    h += '<div class="list-title">Shopping</div><div class="list">' +
      rowLink('#orders', I.box, 'My orders', S.orders ? orders.length + '' : '') +
      rowLink('#orders/tracking', I.truck, 'Track a shipment', '') +
      rowLink('#wishlist', I.heart.replace('<svg', '<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"'), 'Wishlist', S.wish.length ? S.wish.length + '' : '') + '</div>';
    if (S.bio) {
      h += '<div class="list-title">Sign-in</div><div class="list"><button class="row" type="button" data-passkey-toggle><span class="row-ico">' + I.face + '</span><span class="row-text">Sign in with ' + esc(bioName) + '<small>' + (S.session.passkeys ? 'On for this account' : 'Off') + '</small></span><span class="switch' + (S.session.passkeys ? ' is-on' : '') + '"></span></button></div>';
    }
    h += '<div class="list-title">Help</div><div class="list">' + contactRows() + '</div>';
    h += '<div class="list"><button class="row" type="button" data-signout><span class="row-ico">' + I.out + '</span><span class="row-text">Sign out</span></button></div>';
    v.innerHTML = h;
    if (!S.orders) loadOrders().then(function () { if (S.tab === 'account' && !subRoute()) renderAccount(); });
  }
  function rowLink(href, icon, text, end) {
    return '<a class="row" href="' + esc(href) + '"><span class="row-ico">' + icon + '</span><span class="row-text">' + esc(text) + '</span><span class="row-end">' + esc(end || '') + I.chev + '</span></a>';
  }
  function contactRows() {
    var r = '';
    if (C.whatsapp) r += '<a class="row" href="https://wa.me/' + esc(C.whatsapp) + '?text=' + encodeURIComponent('Hi PRDRWNDN!') + '" target="_blank" rel="noopener"><span class="row-ico">' + I.wa + '</span><span class="row-text">WhatsApp us<small>Fastest reply</small></span><span class="row-end">' + I.chev + '</span></a>';
    r += '<a class="row" href="mailto:' + esc(C.email) + '"><span class="row-ico">' + I.mail + '</span><span class="row-text">Email<small>' + esc(C.email) + '</small></span><span class="row-end">' + I.chev + '</span></a>';
    r += '<button class="row" type="button" data-share><span class="row-ico">' + I.share + '</span><span class="row-text">Share PRDRWNDN</span><span class="row-end">' + I.chev + '</span></button>';
    return r;
  }

  function loadOrders() {
    if (!S.session.user) { S.orders = []; return Promise.resolve([]); }
    return rest('orders').then(function (o) { S.orders = o; return o; }).catch(function () { S.orders = S.orders || []; return S.orders; });
  }

  /* Sub views: orders, order detail, wishlist, thanks */
  function subRoute() {
    var h = location.hash.replace(/^#/, '');
    if (/^(orders|orders\/tracking|wishlist|order\/\d+|thanks\/\d+)$/.test(h)) return h;
    return '';
  }
  function renderSub() {
    var r = subRoute(); var v = $('#view-sub');
    if (r === 'wishlist') {
      setBar('Wishlist', true);
      var items = S.wish.map(function (id) { return S.byId[id]; }).filter(Boolean);
      v.innerHTML = items.length ? '<div class="grid" style="padding-top:8px">' + items.map(card).join('') + '</div>' :
        '<div class="empty"><div class="empty-ico">' + I.heart.replace('<svg', '<svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"') + '</div><h3>Nothing saved yet</h3><p>Tap the heart on anything you like and it lands here.</p><a class="btn btn-primary" href="#shop">Browse the shop</a></div>';
      return;
    }
    if (r === 'orders' || r === 'orders/tracking') {
      var tracking = r === 'orders/tracking';
      setBar(tracking ? 'Track a shipment' : 'My orders', true);
      if (!S.session.user) { v.innerHTML = signInFirst('to see your orders'); return; }
      if (!S.orders) { v.innerHTML = '<div class="pad"><div class="skeleton" style="height:180px"></div></div>'; loadOrders().then(renderSub); return; }
      var list = tracking ? S.orders.filter(function (o) { return ['processing', 'on-hold', 'completed'].indexOf(o.status) >= 0; }) : S.orders;
      if (!list.length) { v.innerHTML = '<div class="empty"><div class="empty-ico">' + I.box + '</div><h3>' + (tracking ? 'Nothing on the way' : 'No orders yet') + '</h3><p>' + (tracking ? 'Orders show here once they’re paid.' : 'Your orders will show up here.') + '</p><a class="btn btn-primary" href="#shop">Start shopping</a></div>'; return; }
      v.innerHTML = '<div class="list" style="margin-top:8px">' + list.map(function (o) {
        return '<a class="order" href="#order/' + o.id + '">' + (o.image ? '<img src="' + esc(o.image) + '" alt=""/>' : '<span class="ph"></span>') +
          '<span><strong>Order #' + esc(o.number) + '</strong><small>' + esc(o.date) + ' · ' + o.items + ' item' + (o.items === 1 ? '' : 's') + '</small><br/><span class="status status-' + esc(o.status) + '">' + esc(o.label) + '</span></span>' +
          '<span class="row-end">' + esc(o.total) + I.chev + '</span></a>';
      }).join('') + '</div>';
      return;
    }
    var m = r.match(/^order\/(\d+)$/);
    if (m) {
      setBar('Order', true);
      if (!S.session.user) { v.innerHTML = signInFirst('to see this order'); return; }
      v.innerHTML = '<div class="pad"><div class="skeleton" style="height:240px"></div></div>';
      rest('orders/' + m[1]).then(function (o) {
        if (subRoute() !== r) return;
        setBar('Order #' + o.number, true);
        var h = '<div class="pad" style="display:flex;justify-content:space-between;align-items:center;margin:6px 0 12px"><span class="status status-' + esc(o.status) + '">' + esc(o.label) + '</span><small style="color:var(--muted)">' + esc(o.date) + '</small></div>';
        if (o.step >= 0) {
          h += '<div class="steps">' + o.steps.map(function (s, i) {
            var cls = i < o.step ? 'is-done' : i === o.step ? 'is-done is-now' : 'is-todo';
            return '<div class="step ' + cls + '"><i>' + (i <= o.step ? I.check : '') + '</i><span>' + esc(s) + '</span></div>';
          }).join('') + '</div>';
        }
        if (o.tracking) {
          h += '<div class="track">' + I.truck + '<div><small>' + esc(o.tracking.carrier) + ' tracking</small><strong>' + esc(o.tracking.number) + '</strong></div>' + (o.tracking.url ? '<a class="btn" href="' + esc(o.tracking.url) + '" target="_blank" rel="noopener">Track</a>' : '') + '</div>';
        } else if (['processing', 'on-hold'].indexOf(o.status) >= 0) {
          h += '<p class="pad" style="margin-top:12px;font-size:14px;color:var(--muted)">' + (o.pickup ? 'We’ll message you when it’s ready for pickup.' : 'You’ll get a tracking number here as soon as it ships.') + '</p>';
        }
        if (o.payUrl) h += '<div class="pay"><button class="btn btn-primary btn-block" type="button" data-pay="' + esc(o.payUrl) + '">' + I.lock + ' Pay for this order</button></div>';
        h += '<div class="list-title">Items</div><div class="list">' + o.items.map(function (it) {
          return '<div class="order">' + (it.image ? '<img src="' + esc(it.image) + '" alt=""/>' : '<span class="ph"></span>') + '<span><strong>' + esc(it.name) + '</strong><small>' + esc(it.meta.join(' · ')) + (it.meta.length ? ' · ' : '') + 'Qty ' + it.qty + '</small></span><span class="row-end">' + esc(it.total) + '</span></div>';
        }).join('') + '</div>';
        h += '<div class="totals">' + o.totals.map(function (t) { return '<div' + (/total$/i.test(t.label) && !/sub/i.test(t.label) ? ' class="grand"' : '') + '><span>' + esc(t.label) + '</span><span>' + esc(t.value) + '</span></div>'; }).join('') + '</div>';
        if (o.address) h += '<div class="list-title">' + (o.pickup ? 'Pickup' : 'Shipping to') + '</div><p class="pad" style="font-size:15px">' + esc(o.address) + '</p>';
        if (C.whatsapp) h += '<div class="pay"><a class="btn btn-wa btn-block" href="https://wa.me/' + esc(C.whatsapp) + '?text=' + encodeURIComponent('Hi PRDRWNDN! A question about order #' + o.number) + '" target="_blank" rel="noopener">' + I.wa + ' Ask about this order</a></div>';
        v.innerHTML = h;
      }).catch(function (e) { v.innerHTML = '<div class="empty"><h3>Can’t open this order</h3><p>' + esc(e.message) + '</p></div>'; });
      return;
    }
    var t = r.match(/^thanks\/(\d+)$/);
    if (t) {
      setBar('Thank you', true);
      v.innerHTML = '<div class="success"><div class="empty-ico">' + I.check.replace('width="16" height="16"', 'width="36" height="36"') + '</div><h2>Order placed!</h2><p>Order #' + esc(t[1]) + ' is in. We’ve emailed your receipt.</p></div>' +
        '<div class="pay">' + (S.session.user ? '<a class="btn btn-primary btn-block" href="#order/' + esc(t[1]) + '">View order</a>' : '') + '<a class="btn btn-light btn-block" href="#home">Keep shopping</a></div>';
      S.orders = null;
      loadCart();
    }
  }
  function signInFirst(why) {
    return '<div class="empty"><div class="empty-ico">' + I.box + '</div><h3>Sign in first</h3><p>Sign in ' + esc(why) + '.</p><a class="btn btn-primary" href="#account">Sign in</a></div>';
  }

  /* ════════ Product sheet ════════ */
  var sheet = $('#sheet'), panel = $('#sheet-panel');
  var P = null; // { product, sel: {key: value}, qty }
  function openSheet(html, full) {
    panel.innerHTML = html;
    sheet.classList.toggle('is-full', !!full);
    if (sheet.hidden) {
      sheet.hidden = false;
      panel.scrollTop = 0;
      requestAnimationFrame(function () { requestAnimationFrame(function () { sheet.classList.add('is-open'); }); });
    }
  }
  function closeSheet() {
    if (sheet.hidden) return;
    sheet.classList.remove('is-open');
    setTimeout(function () { sheet.hidden = true; panel.innerHTML = ''; }, 300);
    P = null;
  }
  function openProduct(slug) {
    var p = S.bySlug[slug];
    if (!p) { if (S.ready) { toast('That item isn’t available any more.'); location.replace(S.lastBase); } return; }
    P = { product: p, sel: {}, qty: 1, guide: false };
    // Preselect when there is only one choice.
    (p.options || []).forEach(function (o) { var avail = o.values.filter(function (v) { return valueAvailable(p, o.key, v.value, {}); }); if (o.values.length === 1) P.sel[o.key] = o.values[0].value; else if (avail.length === 1 && !p.jersey) P.sel[o.key] = avail[0].value; });
    drawProduct();
  }
  function matchVariant(p, sel) {
    return (p.variants || []).filter(function (v) {
      return (p.options || []).every(function (o) { var need = sel[o.key]; var have = v.attrs[o.key]; return need && (have === '' || have === need); });
    })[0] || null;
  }
  function valueAvailable(p, key, value, sel) {
    return (p.variants || []).some(function (v) {
      if (!v.inStock) return false;
      return (p.options || []).every(function (o) {
        var want = o.key === key ? value : sel[o.key];
        var have = v.attrs[o.key];
        return !want || have === '' || have === want;
      });
    });
  }
  function drawProduct() {
    var p = P.product, on = S.wish.indexOf(p.id) >= 0;
    var v = p.type === 'variable' ? matchVariant(p, P.sel) : null;
    var price = v ? money(v.price) : priceText(p);
    var imgs = p.images.length ? p.images : [{ src: '', alt: '' }];
    var h = '<div class="pp"><div class="pp-top"><button class="bar-btn" type="button" data-close-sheet aria-label="Close">' + I.close + '</button>' +
      '<span style="display:flex;gap:8px"><button class="bar-btn" type="button" data-share-product aria-label="Share">' + I.share + '</button>' +
      '<button class="bar-btn' + (on ? ' is-on' : '') + '" type="button" data-wish="' + p.id + '" aria-label="Save">' + I.heart.replace('<svg', '<svg width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"') + '</button></span></div>' +
      '<div class="pp-gallery" id="pp-gallery">' + imgs.map(function (im, i) { return im.src ? '<img src="' + esc(im.src) + '" alt="' + esc(im.alt || p.name) + '"' + (i ? ' loading="lazy"' : '') + '/>' : ''; }).join('') + '</div>' +
      (imgs.length > 1 ? '<div class="pp-dots" id="pp-dots">' + imgs.map(function (_, i) { return '<i' + (i === 0 ? ' class="is-on"' : '') + '></i>'; }).join('') + '</div>' : '') +
      '<div class="pp-body"><div class="eyebrow">' + (p.jersey ? 'Football jersey · PRDRWNDN®' : 'PRDRWNDN®') + '</div><h2 class="pp-name">' + esc(p.name) + '</h2>' +
      '<div class="pp-price">' + price + (p.onSale && p.regular > p.price && !v ? '<del>' + money(p.regular) + '</del>' : '') + '</div>';
    if (p.jersey && C.deal && C.deal.on) h += '<div class="pp-deal"><strong>' + esc(C.deal.label) + '</strong> — mix and match any two jerseys. The discount comes off in your cart.</div>';
    (p.options || []).forEach(function (o) {
      var isSize = /size/i.test(o.name);
      h += '<div class="opt"><div class="opt-head"><strong>' + esc(o.name) + (P.sel[o.key] ? ': ' + esc(o.values.filter(function (x) { return x.value === P.sel[o.key]; })[0].label) : '') + '</strong>' +
        (isSize && C.sizeChart && C.sizeChart.length > 1 ? '<button type="button" data-guide>' + (P.guide ? 'Hide size guide' : 'Size guide') + '</button>' : '') + '</div><div class="opt-values">';
      o.values.forEach(function (val) {
        var ok = valueAvailable(p, o.key, val.value, P.sel);
        var testSel = Object.assign({}, P.sel); testSel[o.key] = val.value;
        var tv = matchVariant(p, testSel);
        var few = ok && tv && tv.stock !== null && tv.stock > 0 && tv.stock <= 3 ? '<small>' + tv.stock + ' left</small>' : '';
        h += '<button class="opt-v' + (P.sel[o.key] === val.value ? ' is-on' : '') + '" type="button" data-opt="' + esc(o.key) + '" data-val="' + esc(val.value) + '"' + (ok ? '' : ' disabled aria-label="' + esc(val.label) + ' sold out"') + '>' + esc(val.label) + few + '</button>';
      });
      h += '</div></div>';
      if (isSize && P.guide && C.sizeChart && C.sizeChart.length > 1) {
        var rows = C.sizeChart;
        h += '<div class="table-scroll" style="margin-top:16px"><table class="size-table"><thead><tr>' + rows[0].map(function (c) { return '<th>' + esc(c) + '</th>'; }).join('') + '</tr></thead><tbody>' +
          rows.slice(1).map(function (r) { return '<tr>' + r.map(function (c, i) { return i ? '<td>' + esc(c) + '</td>' : '<th>' + esc(c) + '</th>'; }).join('') + '</tr>'; }).join('') + '</tbody></table></div>';
      }
    });
    h += '<div class="opt"><div class="opt-head"><strong>Quantity</strong></div><div class="qty"><button type="button" data-pqty="-1" aria-label="One less">−</button><span>' + P.qty + '</span><button type="button" data-pqty="1" aria-label="One more">+</button></div></div>';
    var desc = '';
    if (p.desc) desc += '<details open><summary>Details</summary><div class="rich">' + p.desc + '</div></details>';
    else if (p.short) desc += '<details open><summary>Details</summary><div class="rich"><p>' + esc(p.short) + '</p></div></details>';
    desc += '<details><summary>Shipping &amp; pickup</summary><div class="rich"><p>Ships from Phoenix, AZ.' + (C.pickup ? ' ' + esc(C.pickup) + ' — choose it at checkout.' : '') + '</p></div></details>';
    h += '<div class="pp-desc">' + desc + '</div></div>';
    var needs = (p.options || []).filter(function (o) { return !P.sel[o.key]; });
    var label = !p.inStock ? 'Sold out' : needs.length ? 'Pick a ' + needs[0].name.toLowerCase() : (v && !v.inStock ? 'Sold out' : 'Add to cart · ' + money((v ? v.price : p.price) * P.qty));
    var disabled = !p.inStock || (v && !v.inStock);
    var waText = 'Hi PRDRWNDN! I’d like the ' + p.name + (v ? ' (' + variantLabel(p, v) + ')' : '') + '.';
    h += '<div class="pp-buy"><div class="pp-buy-row"><button class="btn btn-primary" type="button" data-add' + (disabled ? ' disabled' : '') + '>' + esc(label) + '</button>' +
      (C.whatsapp ? '<a class="btn btn-wa" href="https://wa.me/' + esc(C.whatsapp) + '?text=' + encodeURIComponent(waText) + '" target="_blank" rel="noopener" aria-label="Order on WhatsApp">' + I.wa + '</a>' : '') + '</div></div></div>';
    var keepScroll = panel.scrollTop, galleryX = $('#pp-gallery') ? $('#pp-gallery').scrollLeft : 0;
    openSheet(h, true);
    panel.scrollTop = keepScroll;
    var g = $('#pp-gallery');
    if (g) {
      g.scrollLeft = galleryX;
      g.addEventListener('scroll', function () {
        var i = Math.round(g.scrollLeft / g.clientWidth);
        $$('#pp-dots i').forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
      }, { passive: true });
    }
  }
  function addToCart(btn) {
    var p = P.product;
    var needs = (p.options || []).filter(function (o) { return !P.sel[o.key]; });
    if (needs.length) { toast('Pick a ' + needs[0].name.toLowerCase() + ' first.'); return; }
    var v = p.type === 'variable' ? matchVariant(p, P.sel) : null;
    var body = { id: v ? v.id : p.id, quantity: P.qty };
    if (v) {
      var any = (p.options || []).filter(function (o) { return v.attrs[o.key] === ''; });
      if (any.length) body.variation = any.map(function (o) { return { attribute: o.key.replace(/^attribute_/, ''), value: P.sel[o.key] }; });
    }
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    storeApi('cart/add-item', { method: 'POST', body: body }).then(function (c) {
      S.cart = c; updateBadge();
      if (navigator.vibrate) navigator.vibrate(12);
      var jc = cartJerseyCount();
      var msg = p.jersey && C.deal && C.deal.on && jc % 2 === 1 ? 'Added. Add one more jersey for ' + C.deal.label + '.' : 'Added to your cart';
      closeSheet(); goBack(S.lastBase);
      setTimeout(function () { toast(msg, '#cart', 'View cart'); }, 320);
    }).catch(function (e) {
      toast(e.message || 'Couldn’t add that. Try again.');
      drawProduct();
    });
  }

  /* ════════ Account actions ════════ */
  function afterSignIn(sess) {
    S.session = sess; S.orders = null; mergeWishlist();
    return loadCart().then(function () { renderAccount(); $('#view-account').scrollTop = 0; refreshCards(); });
  }
  function signIn(form) {
    var err = $('#signin-error'), btn = form.querySelector('[type=submit]');
    var data = { login: form.login.value.trim(), password: form.password.value };
    if (!data.login || !data.password) { err.textContent = 'Enter your email and password.'; err.hidden = false; return; }
    err.hidden = true; btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>';
    S.session.nonce = '';
    rest('login', { method: 'POST', body: data, noNonce: true }).then(function (sess) {
      return afterSignIn(sess).then(function () { toast('Signed in. Welcome back, ' + sess.user.name + '!'); });
    }).catch(function (e) {
      err.textContent = e.message; err.hidden = false; btn.disabled = false; btn.textContent = 'Sign in';
    });
  }
  function passkeyLogin() {
    rest('passkey/login-options', { method: 'POST', noNonce: true }).then(function (res) {
      var o = res.options;
      return navigator.credentials.get({ publicKey: { challenge: unb64u(o.challenge), rpId: o.rpId, userVerification: o.userVerification, timeout: o.timeout, allowCredentials: [] } }).then(function (cred) {
        var r = cred.response;
        return rest('passkey/login', { method: 'POST', noNonce: true, body: { token: res.token, id: cred.id, clientDataJSON: b64u(r.clientDataJSON), authenticatorData: b64u(r.authenticatorData), signature: b64u(r.signature), userHandle: r.userHandle ? b64u(r.userHandle) : '' } });
      });
    }).then(function (sess) {
      store('prdPasskey', '1');
      return afterSignIn(sess).then(function () { toast('Signed in with ' + bioName + '.'); });
    }).catch(function (e) {
      if (e && e.name === 'NotAllowedError') return; // cancelled
      toast(e && e.message ? e.message : bioName + ' sign-in didn’t work.');
    });
  }
  function passkeyAdd() {
    rest('passkey/register-options', { method: 'POST' }).then(function (o) {
      var pk = {
        challenge: unb64u(o.challenge), rp: o.rp,
        user: { id: unb64u(o.user.id), name: o.user.name, displayName: o.user.displayName },
        pubKeyCredParams: o.pubKeyCredParams, authenticatorSelection: o.authenticatorSelection,
        attestation: o.attestation, timeout: o.timeout,
        excludeCredentials: (o.excludeCredentials || []).map(function (c) { return { type: 'public-key', id: unb64u(c.id) }; })
      };
      return navigator.credentials.create({ publicKey: pk });
    }).then(function (cred) {
      var r = cred.response;
      return rest('passkey/register', { method: 'POST', body: { id: cred.id, clientDataJSON: b64u(r.clientDataJSON), attestationObject: b64u(r.attestationObject), device: isIOS ? 'iPhone' : navigator.platform || '' } });
    }).then(function (res) {
      S.session.passkeys = res.passkeys; store('prdPasskey', '1'); store('prdPasskeyNo', null);
      renderAccount(); toast(bioName + ' sign-in is on.');
    }).catch(function (e) {
      if (e && e.name === 'NotAllowedError') return;
      if (e && e.name === 'InvalidStateError') { toast(bioName + ' is already set up on this device.'); return; }
      toast(e && e.message ? e.message : 'Couldn’t turn on ' + bioName + '.');
    });
  }
  function passkeyOff() {
    rest('passkeys', { method: 'DELETE' }).then(function () {
      S.session.passkeys = 0; store('prdPasskey', null); store('prdPasskeyNo', '1');
      renderAccount(); toast(bioName + ' sign-in is off.');
    }).catch(function (e) { toast(e.message); });
  }
  function signOut() {
    rest('logout', { method: 'POST' }).catch(function () {}).then(function () {
      S.session = { user: null, nonce: '', passkeys: 0, wishlist: [] }; S.orders = null;
      S.wish = localWish(); S.storeNonce = '';
      return loadCart();
    }).then(function () { renderAccount(); $('#view-account').scrollTop = 0; refreshCards(); toast('Signed out.'); });
  }
  function shareApp(title, url) {
    var data = { title: title || 'PRDRWNDN', text: 'Rwanda jerseys, car fresheners and more from PRDRWNDN.', url: url || C.home };
    if (navigator.share) { navigator.share(data).catch(function () {}); return; }
    if (navigator.clipboard) navigator.clipboard.writeText(data.url).then(function () { toast('Link copied.'); });
  }
  function goCheckout(url) {
    try { sessionStorage.setItem('prdInApp', '1'); } catch (e) {}
    location.href = url || C.checkout;
  }

  /* ════════ Shell: bar, tabs, badge, toast ════════ */
  function setBar(title, back) {
    var bar = $('#bar');
    $('#bar-logo').hidden = !!title;
    $('#bar-title').hidden = !title;
    $('#bar-title').textContent = title || '';
    $('#bar-back').hidden = !back;
    bar.classList.toggle('bar-back-visible', !!back);
  }
  function updateBadge() {
    var n = S.cart ? S.cart.items_count : 0;
    var b = $('#cart-badge'); b.textContent = n; b.hidden = !n;
  }
  var toastTimer = null;
  function toast(msg, href, label) {
    var t = $('#toast');
    t.innerHTML = '<span>' + esc(msg) + '</span>' + (href ? '<a href="' + esc(href) + '">' + esc(label || 'View') + '</a>' : '');
    t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.hidden = true; }, 3200);
  }
  function refreshCards() {
    if (S.tab === 'home') renderHome();
    if (S.tab === 'shop') renderShop();
    if (subRoute()) renderSub();
  }

  /* ════════ Router ════════ */
  var TITLES = { home: '', shop: 'Shop', cart: 'Cart', account: 'Account' };
  function route() {
    var h = location.hash.replace(/^#/, '') || 'home';
    var pm = h.match(/^p\/(.+)$/);
    if (pm) {
      if (!$('.view.is-active')) show(S.lastBase.replace('#', '') || 'home');
      openProduct(decodeURIComponent(pm[1]));
      return;
    }
    closeSheet();
    if (subRoute()) {
      showView('sub');
      renderSub();
      S.lastBase = '#' + h;
      return;
    }
    show(h);
  }
  function show(h) {
    var parts = h.split('/');
    var tab = TITLES.hasOwnProperty(parts[0]) ? parts[0] : 'home';
    if (tab === 'shop') { S.filter = parts[1] || 'all'; }
    S.tab = tab; S.lastBase = '#' + h;
    showView(tab);
    setBar(TITLES[tab], false);
    if (tab === 'home') renderHome();
    if (tab === 'shop') renderShop();
    if (tab === 'cart') { renderCart(); loadCart().then(function () { if (S.tab === 'cart') renderCart(); }); }
    if (tab === 'account') renderAccount();
  }
  function showView(name) {
    $$('.view').forEach(function (v) { v.classList.toggle('is-active', v.id === 'view-' + name); });
    var active = $('#view-' + name); if (active) active.scrollTop = 0;
    $$('.tabs a').forEach(function (a) {
      var t = a.getAttribute('data-tab');
      var on = name === 'sub' ? (/order|tracking/.test(location.hash) && t === 'account') || (location.hash === '#wishlist' && t === 'account') : t === name;
      a.classList.toggle('is-active', on);
    });
    $('#bar').classList.remove('is-scrolled');
  }

  /* ════════ Events ════════ */
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target : null;
    if (!t) return;
    var el;
    if ((el = t.closest('[data-wish]'))) { e.preventDefault(); toggleWish(el.getAttribute('data-wish')); if (P) drawProduct(); return; }
    if ((el = t.closest('[data-close-sheet]'))) { e.preventDefault(); if (/^#p\//.test(location.hash)) goBack(S.lastBase); else closeSheet(); return; }
    if ((el = t.closest('[data-filter]'))) { var f = el.getAttribute('data-filter'); location.replace(f === 'all' ? '#shop' : '#shop/' + f); return; }
    if ((el = t.closest('[data-opt]'))) { var k = el.getAttribute('data-opt'), val = el.getAttribute('data-val'); P.sel[k] = P.sel[k] === val ? '' : val; drawProduct(); return; }
    if ((el = t.closest('[data-guide]'))) { P.guide = !P.guide; drawProduct(); return; }
    if ((el = t.closest('[data-pqty]'))) { P.qty = Math.max(1, Math.min(10, P.qty + Number(el.getAttribute('data-pqty')))); drawProduct(); return; }
    if ((el = t.closest('[data-add]'))) { addToCart(el); return; }
    if ((el = t.closest('[data-share-product]'))) { shareApp(P.product.name, P.product.url); return; }
    if ((el = t.closest('[data-qty]'))) {
      var key = el.getAttribute('data-qty'), item = S.cart.items.filter(function (i) { return i.key === key; })[0];
      if (!item) return;
      var q = item.quantity + Number(el.getAttribute('data-d'));
      el.parentNode.style.opacity = '.5';
      (q < 1 ? storeApi('cart/remove-item', { method: 'POST', body: { key: key } }) : storeApi('cart/update-item', { method: 'POST', body: { key: key, quantity: q } }))
        .then(function (c) { S.cart = c; updateBadge(); renderCart(); }).catch(function (er) { toast(er.message); renderCart(); });
      return;
    }
    if ((el = t.closest('[data-remove]'))) {
      storeApi('cart/remove-item', { method: 'POST', body: { key: el.getAttribute('data-remove') } }).then(function (c) { S.cart = c; updateBadge(); renderCart(); }).catch(function (er) { toast(er.message); });
      return;
    }
    if ((el = t.closest('[data-checkout]'))) { el.innerHTML = '<span class="spinner"></span>'; goCheckout(); return; }
    if ((el = t.closest('[data-pay]'))) { goCheckout(el.getAttribute('data-pay')); return; }
    if ((el = t.closest('[data-passkey-login]'))) { passkeyLogin(); return; }
    if ((el = t.closest('[data-passkey-add]'))) { passkeyAdd(); return; }
    if ((el = t.closest('[data-passkey-toggle]'))) { if (S.session.passkeys) passkeyOff(); else passkeyAdd(); return; }
    if ((el = t.closest('[data-signout]'))) { signOut(); return; }
    if ((el = t.closest('[data-share]'))) { shareApp(); return; }
  });
  document.addEventListener('submit', function (e) {
    if (e.target.id === 'signin-form') { e.preventDefault(); signIn(e.target); }
  });
  document.addEventListener('input', function (e) {
    if (e.target.id === 'shop-q') { S.query = e.target.value; renderShop(true); }
  });
  document.addEventListener('scroll', function (e) {
    if (e.target.classList && e.target.classList.contains('view')) $('#bar').classList.toggle('is-scrolled', e.target.scrollTop > 4);
  }, true);
  $('#bar-back').addEventListener('click', function () {
    var h = location.hash;
    if (/^#order\//.test(h)) location.hash = '#orders';
    else if (/^#thanks\//.test(h)) location.hash = '#home';
    else goBack('#account');
  });
  var stack = [location.hash || '#home'];
  window.addEventListener('hashchange', function () {
    var h = location.hash || '#home';
    if (stack.length > 1 && stack[stack.length - 2] === h) stack.pop(); else stack.push(h);
    route();
  });
  function goBack(fallback) {
    if (stack.length > 1) history.back(); else location.replace(fallback || '#home');
  }
  function onlineState() {
    var o = $('#offline-note');
    if (navigator.onLine) { if (o) o.remove(); return; }
    if (!o) { o = document.createElement('div'); o.id = 'offline-note'; o.className = 'offline'; o.textContent = 'You’re offline. Showing what’s saved.'; document.body.appendChild(o); }
  }
  window.addEventListener('online', function () { onlineState(); loadCatalog().then(refreshCards); loadCart(); });
  window.addEventListener('offline', onlineState);
  document.addEventListener('visibilitychange', function () { if (!document.hidden && S.ready) loadCart(); });

  /* ════════ Start ════════ */
  var started = Date.now();
  var launch = $('#launch');
  var firstOpen = true;
  try { firstOpen = sessionStorage.getItem('prdLaunched') !== '1'; sessionStorage.setItem('prdLaunched', '1'); } catch (e) {}
  if (!firstOpen) launch.classList.add('is-done');
  function hideLaunch() {
    var wait = firstOpen ? Math.max(0, 1100 - (Date.now() - started)) : 0;
    setTimeout(function () { launch.classList.add('is-done'); }, wait);
  }
  setTimeout(hideLaunch, 2600); // never hold the launch screen longer than this

  var bioCheck = (window.PublicKeyCredential && PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable)
    ? PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(function () { return false; }) : Promise.resolve(false);

  S.wish = localWish();
  route();
  onlineState();
  Promise.all([loadCatalog(), loadSession(), loadCart(), bioCheck.then(function (ok) { S.bio = !!ok; })]).then(function () {
    S.ready = true;
    hideLaunch();
    route();
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(function () {}); });
  }
})();
