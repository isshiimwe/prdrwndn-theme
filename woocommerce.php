<?php
/**
 * WooCommerce template wrapper
 * Handles: single product, cart, checkout, account pages
 * Shop archive uses: woocommerce/archive-product.php
 */
get_header();

// Determine page context for hero
$is_cart      = is_cart();
$is_checkout  = is_checkout();
$is_account   = is_account_page();
$is_product   = is_product();

// Page titles
if ( $is_cart )     { $page_title = 'Your Cart';    $page_icon = '🛒'; }
elseif ( $is_checkout ) { $page_title = 'Checkout'; $page_icon = '💳'; }
elseif ( $is_account )  { $page_title = is_user_logged_in() ? 'My Account' : 'Sign In'; $page_icon = '👤'; }
else                    { $page_title = ''; $page_icon = ''; }
?>

<?php if ( ! $is_product && $page_title ) : ?>
<!-- PAGE MINI HERO -->
<div class="woo-page-hero">
  <?php prdrwndn_flag_stripe(); ?>
  <div class="woo-page-hero-inner">
    <div class="eyebrow"><?php echo esc_html( $page_icon . ' PRDRWNDN®'); ?></div>
    <h1 class="woo-page-title"><?php echo esc_html( $page_title ); ?></h1>
  </div>
</div>
<?php endif; ?>

<main class="woo-main-wrap <?php echo $is_product ? 'is-product' : ''; ?>">
  <?php woocommerce_content(); ?>
</main>

<?php get_footer(); ?>
