<?php
/**
 * Default page — also used for Cart, Checkout and My Account.
 */
get_header();
$is_shop_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<div class="prd-wrap <?php echo $is_shop_page ? 'prd-wc-page' : 'prd-text-page'; ?>">
  <?php while ( have_posts() ) : the_post(); ?>
    <article>
      <h1 class="prd-h1 prd-page-title"><?php the_title(); ?></h1>
      <?php if ( function_exists( 'is_cart' ) && is_cart() ) prdrwndn_cart_extras(); ?>
      <div class="prd-content">
        <?php the_content(); ?>
      </div>
    </article>
  <?php endwhile; ?>
</div>
<?php get_footer(); ?>
