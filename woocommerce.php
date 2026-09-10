<?php
/**
 * WooCommerce template wrapper.
 * For single products, cart, checkout, account pages.
 * The shop/archive uses woocommerce/archive-product.php
 */
get_header(); ?>

<main style="padding-top:56px;min-height:60vh;">
  <?php woocommerce_content(); ?>
</main>

<?php get_footer(); ?>
