<?php
/**
 * WooCommerce wrapper — single product pages (shop/category pages use woocommerce/archive-product.php).
 */
get_header();

$back = null;
if ( is_product() ) {
    $terms = get_the_terms( get_the_ID(), 'product_cat' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        // Prefer the deepest category
        usort( $terms, function ( $a, $b ) { return $b->parent <=> $a->parent; } );
        $back = $terms[0];
    }
}
?>
<div class="prd-wrap prd-product-wrap">
  <?php if ( $back ) : ?>
    <a class="prd-back" href="<?php echo esc_url( get_term_link( $back ) ); ?>">← <?php echo esc_html( $back->name ); ?></a>
  <?php endif; ?>
  <?php woocommerce_content(); ?>
</div>
<?php get_footer(); ?>
