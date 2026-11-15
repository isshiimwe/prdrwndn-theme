/* PRDRWNDN® Shop JS — refresh the header cart count after AJAX add-to-cart */
(function ($) {
  'use strict';
  $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', function () {
    if (window.prdUpdateCartCount) window.prdUpdateCartCount();
  });
})(jQuery);
