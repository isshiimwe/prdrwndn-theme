<?php
/**
 * Template Name: Collection
 * Every product on one page, with category filters.
 */
get_header();

$collection = new WP_Query( [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
] );
$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => get_option( 'default_product_cat' ) ] );
?>

<section class="prd-pagehead">
  <div class="prd-wrap">
    <div class="prd-eyebrow">PRDRWNDN®</div>
    <h1 class="prd-h1">The full collection</h1>
    <p class="prd-pagehead-desc">Everything we make — jerseys, the car freshener that started it all, flags and apparel.</p>
  </div>
</section>

<div class="prd-wrap prd-shop">
  <?php if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) : ?>
  <div class="prd-chips" role="group" aria-label="Filter by category">
    <button type="button" class="prd-chip is-active" data-filter="all" aria-pressed="true">All</button>
    <?php foreach ( $cats as $cat ) : ?>
      <button type="button" class="prd-chip" data-filter="<?php echo esc_attr( $cat->slug ); ?>" aria-pressed="false"><?php echo esc_html( $cat->name ); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <ul class="products prd-grid" id="prd-collection">
    <?php while ( $collection->have_posts() ) : $collection->the_post();
      global $product;
      $product = wc_get_product( get_the_ID() );
      if ( ! $product ) continue;
      $slugs = wp_get_post_terms( get_the_ID(), 'product_cat', [ 'fields' => 'slugs' ] );
      ob_start();
      wc_get_template_part( 'content', 'product' );
      $html = ob_get_clean();
      // Tag the card with its categories for the filter
      echo preg_replace( '/<li /', '<li data-cats="' . esc_attr( ' ' . implode( ' ', $slugs ) . ' ' ) . '" ', $html, 1 );
    endwhile; wp_reset_postdata(); ?>
  </ul>
  <p class="prd-empty" id="prd-collection-empty" hidden>No products in this category yet.</p>
</div>

<script>
(function () {
  var chips = document.querySelectorAll('.prd-chips [data-filter]');
  var items = document.querySelectorAll('#prd-collection > li');
  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      var f = chip.getAttribute('data-filter'), shown = 0;
      chips.forEach(function (c) { var on = c === chip; c.classList.toggle('is-active', on); c.setAttribute('aria-pressed', on); });
      items.forEach(function (li) {
        var ok = f === 'all' || (li.getAttribute('data-cats') || '').indexOf(' ' + f + ' ') !== -1;
        li.hidden = !ok; if (ok) shown++;
      });
      document.getElementById('prd-collection-empty').hidden = shown !== 0;
    });
  });
})();
</script>

<?php get_footer(); ?>
