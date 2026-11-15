<?php
/**
 * Shop and category pages.
 */
defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$current_cat = get_queried_object();
$is_cat      = is_product_category();
$page_title  = $is_cat ? $current_cat->name : __( 'Shop', 'prdrwndn' );
$page_desc   = $is_cat ? $current_cat->description : 'Jerseys, car fresheners, flags and apparel for the global Rwandan community.';
$is_jersey_cat = $is_cat && $current_cat->slug === prd_mod( 'prd_jersey_cat' );

// We print the result count and sorting ourselves in the toolbar
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
?>

<section class="prd-pagehead">
  <div class="prd-wrap">
    <div class="prd-eyebrow"><?php echo $is_cat ? 'Shop' : 'PRDRWNDN®'; ?></div>
    <h1 class="prd-h1"><?php echo esc_html( $page_title ); ?></h1>
    <?php if ( $page_desc ) : ?><p class="prd-pagehead-desc"><?php echo esc_html( wp_strip_all_tags( $page_desc ) ); ?></p><?php endif; ?>
  </div>
</section>

<div class="prd-wrap prd-shop">
  <?php
  $cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => get_option( 'default_product_cat' ), 'orderby' => 'count', 'order' => 'DESC' ] );
  if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
      // Jerseys first
      usort( $cats, function ( $a, $b ) { $j = prd_mod( 'prd_jersey_cat' ); return ( $b->slug === $j ) <=> ( $a->slug === $j ); } );
  }
  if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
  <nav class="prd-chips" aria-label="Categories">
    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="prd-chip<?php echo ! $is_cat ? ' is-active' : ''; ?>">All</a>
    <?php foreach ( $cats as $cat ) : ?>
      <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="prd-chip<?php echo ( $is_cat && $current_cat->term_id === $cat->term_id ) ? ' is-active' : ''; ?>"><?php echo esc_html( $cat->name ); ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <?php if ( $is_jersey_cat && prdrwndn_deal_on() ) : ?>
    <div class="prd-deal-band prd-deal-band-sm">
      <span class="prd-deal-big"><?php echo esc_html( prdrwndn_deal_label() ); ?></span>
      <span>Mix and match any two jerseys. The discount is taken off in your cart.</span>
    </div>
  <?php endif; ?>

  <?php if ( woocommerce_product_loop() ) : ?>
    <div class="prd-toolbar">
      <?php woocommerce_result_count(); ?>
      <?php woocommerce_catalog_ordering(); ?>
    </div>
    <?php
    do_action( 'woocommerce_before_shop_loop' );
    echo '<ul class="products prd-grid">';
    while ( have_posts() ) : the_post();
      wc_get_template_part( 'content', 'product' );
    endwhile;
    echo '</ul>';
    do_action( 'woocommerce_after_shop_loop' );
  else :
    do_action( 'woocommerce_no_products_found' );
  endif; ?>
</div>

<?php get_footer( 'shop' ); ?>
