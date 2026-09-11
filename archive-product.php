<?php
/**
 * PRDRWNDN® WooCommerce Shop Archive Template
 * Overrides WooCommerce's default shop page layout
 */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

// Page info
$current_cat = get_queried_object();
$is_cat      = is_product_category();
$page_title  = $is_cat ? $current_cat->name : __( 'Shop', 'prdrwndn' );
$page_desc   = $is_cat ? $current_cat->description : 'Everything we make — for the global Rwandan community.';
?>

<!-- SHOP HERO -->
<section class="prdrwndn-shop-hero">
  <?php prdrwndn_flag_stripe(); ?>
  <div class="prdrwndn-shop-hero-inner">
    <div class="eyebrow">PRDRWNDN® Store</div>
    <h1 class="prdrwndn-shop-title"><?php echo esc_html( $page_title ); ?></h1>
    <?php if ( $page_desc ) : ?>
      <p class="prdrwndn-shop-desc"><?php echo esc_html( $page_desc ); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- CATEGORY CHIPS -->
<?php
$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => get_option( 'default_product_cat' ) ] );
if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
<div class="prdrwndn-shop-filters">
  <div class="prdrwndn-filter-inner">
    <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"
       class="prdrwndn-chip <?php echo ! $is_cat ? 'active' : ''; ?>">All</a>
    <?php foreach ( $cats as $cat ) : ?>
      <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
         class="prdrwndn-chip <?php echo ( $is_cat && $current_cat->term_id === $cat->term_id ) ? 'active' : ''; ?>">
        <?php echo esc_html( $cat->name ); ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- SHOP TOOLBAR -->
<div class="prdrwndn-shop-toolbar">
  <div class="prdrwndn-toolbar-inner">
    <?php woocommerce_result_count(); ?>
    <?php woocommerce_catalog_ordering(); ?>
  </div>
</div>

<!-- PRODUCTS -->
<div class="prdrwndn-shop-grid-wrap">
  <?php if ( woocommerce_product_loop() ) :
    do_action( 'woocommerce_before_shop_loop' );
    woocommerce_product_loop_start();
    while ( have_posts() ) : the_post();
      wc_get_template_part( 'content', 'product' );
    endwhile;
    woocommerce_product_loop_end();
    do_action( 'woocommerce_after_shop_loop' );
  else :
    do_action( 'woocommerce_no_products_found' );
  endif; ?>
</div>

<?php get_footer( 'shop' ); ?>
