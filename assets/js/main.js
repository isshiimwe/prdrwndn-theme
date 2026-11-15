/* PRDRWNDN® Main JS (v2) */
(function () {
  'use strict';

  /* ── Mobile menu ── */
  var btn = document.getElementById('prd-menu-btn');
  var menu = document.getElementById('prd-menu');
  function setMenu(open) {
    if (!btn || !menu) return;
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.classList.toggle('prd-menu-open', open);
  }
  if (btn && menu) {
    btn.addEventListener('click', function () { setMenu(menu.hidden); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !menu.hidden) { setMenu(false); btn.focus(); } });
    window.addEventListener('resize', function () { if (window.innerWidth > 960 && !menu.hidden) setMenu(false); });
  }

  /* ── Header shadow once the page scrolls ── */
  var header = document.getElementById('prd-header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Option buttons for variable products (sizes, scents) ──
     Keeps WooCommerce's own <select> working underneath, so price, stock
     and add-to-cart behave exactly as before. */
  function enhanceVariations(form) {
    if (form.getAttribute('data-prd-enhanced')) return;
    var marker = form.querySelector('.prd-variations');
    var isJersey = marker && marker.getAttribute('data-jersey') === '1';
    var variations = [];
    try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]') || []; } catch (e) { variations = []; }
    var selects = form.querySelectorAll('table.variations select');
    if (!selects.length) return;
    form.setAttribute('data-prd-enhanced', '1');
    var single = selects.length === 1;

    selects.forEach(function (select) {
      var name = select.getAttribute('data-attribute_name') || select.name;
      var opts = Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
      if (!opts.length || opts.length > 12) return;

      var group = document.createElement('div');
      group.className = 'prd-opts';
      group.setAttribute('role', 'group');
      var label = form.querySelector('label[for="' + select.id + '"]');
      if (label) group.setAttribute('aria-label', label.textContent.trim());

      opts.forEach(function (o) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'prd-opt';
        b.setAttribute('data-value', o.value);
        b.setAttribute('aria-pressed', 'false');
        var main = document.createElement('span');
        main.textContent = o.text;
        b.appendChild(main);

        if (single && variations.length) {
          var v = variations.filter(function (x) { var a = x.attributes || {}; return a[name] === o.value || a[name] === ''; })[0];
          if (v) {
            var qty = parseInt(v.max_qty, 10);
            if (!v.is_in_stock) {
              b.disabled = true;
              b.classList.add('is-out');
              b.setAttribute('aria-label', o.text + ', sold out');
            } else if (isJersey && qty > 0 && qty <= 3) {
              var left = document.createElement('small');
              left.textContent = qty + ' left';
              b.appendChild(left);
              b.setAttribute('aria-label', o.text + ', ' + qty + ' left');
            }
          }
        }
        b.addEventListener('click', function () {
          select.value = (select.value === o.value) ? '' : o.value;
          if (window.jQuery) window.jQuery(select).trigger('change');
          else select.dispatchEvent(new Event('change', { bubbles: true }));
          sync();
        });
        group.appendChild(b);
      });

      function sync() {
        group.querySelectorAll('.prd-opt').forEach(function (b) {
          b.setAttribute('aria-pressed', b.getAttribute('data-value') === select.value ? 'true' : 'false');
        });
        updateWhatsApp();
      }
      select.addEventListener('change', sync);
      if (window.jQuery) window.jQuery(form).on('reset_data woocommerce_update_variation_values', sync);

      select.classList.add('prd-visually-hidden');
      select.setAttribute('tabindex', '-1');
      select.setAttribute('aria-hidden', 'true');
      select.parentNode.insertBefore(group, select);
      sync();
    });

    function updateWhatsApp() {
      var wa = document.querySelector('.prd-btn-wa[data-wa-base]');
      if (!wa) return;
      var chosen = [];
      selects.forEach(function (s) { if (s.value) chosen.push(s.value); });
      var product = wa.getAttribute('data-product') || 'jersey';
      var text = 'Hi PRDRWNDN! I’d like the ' + product + (chosen.length ? ' in ' + (isJersey ? 'size ' : '') + chosen.join(', ') : '') + '.';
      wa.href = 'https://wa.me/' + wa.getAttribute('data-wa-base') + '?text=' + encodeURIComponent(text);
    }
  }
  document.querySelectorAll('form.variations_form').forEach(enhanceVariations);

  /* ── Live cart count ── */
  window.prdUpdateCartCount = function () {
    if (typeof prdrwndn === 'undefined') return;
    fetch(prdrwndn.ajaxUrl + '?action=prdrwndn_cart_count', { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (count) {
        var n = parseInt(count, 10) || 0;
        document.querySelectorAll('.prd-cart-count').forEach(function (badge) {
          badge.textContent = n;
          badge.hidden = n < 1;
        });
        var cartBtn = document.querySelector('.prd-cart-btn');
        if (cartBtn) cartBtn.setAttribute('aria-label', 'Cart, ' + n + ' items');
      })
      .catch(function () {});
  };
})();
