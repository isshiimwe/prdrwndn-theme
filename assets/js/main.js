/* PRDRWNDN® Main JS */
(function () {
  'use strict';

  // ── Scroll reveal ──
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var revealObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          revealObs.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });
    reveals.forEach(function (el) { revealObs.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('visible'); });
  }

  // ── Header scroll ──
  var header = document.getElementById('site-header');
  if (header) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 60) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }, { passive: true });
  }

  // ── Mobile menu ──
  var menuToggle = document.getElementById('mobile-menu-toggle');
  var mobileMenu = document.getElementById('mobile-menu');
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener('click', function () {
      var isOpen = mobileMenu.classList.toggle('open');
      menuToggle.setAttribute('aria-expanded', isOpen);
      mobileMenu.setAttribute('aria-hidden', !isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Close on link click
    mobileMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
        mobileMenu.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      });
    });
  }

  // ── Scent pills ──
  var pills = document.querySelectorAll('.scent-pill');
  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      var group = pill.closest('.scent-selector');
      if (group) {
        group.querySelectorAll('.scent-pill').forEach(function (p) {
          p.classList.remove('active');
        });
      }
      pill.classList.add('active');

      // Update add-to-cart link if present
      var scent = pill.getAttribute('data-scent');
      var cartLink = document.querySelector('.spotlight-body .btn-primary');
      if (cartLink && scent) {
        var base = cartLink.href.split('?')[0];
        cartLink.href = base + '?attribute_scents=' + encodeURIComponent(scent);
      }
    });
  });

  // ── Product image hover ──
  document.querySelectorAll('.woo-products-grid a img, .collection-item img').forEach(function (img) {
    var parent = img.closest('a, .collection-item');
    if (!parent) return;
    parent.addEventListener('mouseenter', function () { img.style.transform = 'scale(1.04)'; });
    parent.addEventListener('mouseleave', function () { img.style.transform = 'scale(1)'; });
  });

  // ── Live cart count update ──
  function updateCartCount() {
    if (typeof prdrwndn === 'undefined') return;
    fetch(prdrwndn.ajaxUrl + '?action=prdrwndn_cart_count')
      .then(function (r) { return r.text(); })
      .then(function (count) {
        var badge = document.querySelector('.cart-count');
        var n = parseInt(count, 10);
        if (badge) {
          badge.textContent = n;
          badge.style.display = n > 0 ? 'flex' : 'none';
        }
      })
      .catch(function () {});
  }

  // Update cart count on page load
  updateCartCount();

  // ── Smooth anchor scrolls ──
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

})();
