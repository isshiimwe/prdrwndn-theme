<?php get_header(); ?>
<section class="prd-wrap prd-404">
  <div class="prd-eyebrow">404</div>
  <h1 class="prd-h1">Page not <em class="prd-serif">found.</em></h1>
  <?php prdrwndn_flag_stripe( 'prd-flag-mini' ); ?>
  <p>This page doesn’t exist, but Rwanda does, and so does our shop.</p>
  <div class="prd-actions">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="prd-btn prd-btn-primary">Go home</a>
    <a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>" class="prd-btn prd-btn-ghost">Browse the shop</a>
  </div>
</section>
<?php get_footer(); ?>
