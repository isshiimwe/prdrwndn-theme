/* PRDRWNDN® Shop JS */
(function ($) {
  'use strict';

  // Update cart count after add to cart
  $(document.body).on('added_to_cart', function () {
    if (typeof prdrwndn !== 'undefined') {
      fetch(prdrwndn.ajaxUrl + '?action=prdrwndn_cart_count')
        .then(function (r) { return r.text(); })
        .then(function (count) {
          var badge = document.querySelector('.cart-count');
          var n = parseInt(count, 10);
          if (badge) {
            badge.textContent = n;
            badge.style.display = n > 0 ? 'flex' : 'none';
          } else if (n > 0) {
            var btn = document.querySelector('.header-cart-btn');
            if (btn) {
              var b = document.createElement('span');
              b.className = 'cart-count';
              b.textContent = n;
              btn.appendChild(b);
            }
          }
        });
    }
  });

})(jQuery);
