<?php
/**
 * Shop / Product Archive Template
 * Replaces the default WooCommerce shop page
 */
get_header();

// Get current category if filtering
$current_cat = get_queried_object();
$is_category  = is_product_category();
$page_title   = $is_category ? $current_cat->name : 'Shop';
$page_desc    = $is_category ? $current_cat->description : 'Everything we make — for the global Rwandan community.';
?>

<!-- SHOP HERO -->
<section style="
  padding: 7rem 2rem 3rem;
  background: linear-gradient(160deg, var(--rw-green3) 0%, var(--ink) 70%);
  position: relative;
  overflow: hidden;
  border-bottom: 1px solid var(--border);
">
  <?php prdrwndn_flag_stripe(); ?>
  <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(8,8,8,0.3),rgba(8,8,8,0.85));z-index:1;"></div>
  <div style="position:relative;z-index:2;max-width:var(--max-w);margin:0 auto;">
    <div class="eyebrow">PRDRWNDN® Store</div>
    <h1 style="font-family:var(--font-display);font-size:clamp(2.5rem,7vw,5rem);color:var(--rw-cream);letter-spacing:1px;line-height:0.95;margin-bottom:0.75rem;">
      <?php echo esc_html( $page_title ); ?>
    </h1>
    <?php if ( $page_desc ) : ?>
      <p style="font-size:0.9rem;color:var(--muted);max-width:440px;line-height:1.7;">
        <?php echo esc_html( $page_desc ); ?>
      </p>
    <?php endif; ?>
  </div>
</section>

<!-- CATEGORY FILTER CHIPS -->
<?php
$product_cats = get_terms( [
    'taxonomy'   => 'product_cat',
    'hide_empty' => true,
    'exclude'    => get_option( 'default_product_cat' ),
] );
if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ) : ?>
<div style="background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:56px;z-index:50;">
  <div style="max-width:var(--max-w);margin:0 auto;padding:0 2rem;">
    <div style="display:flex;gap:0.5rem;padding:0.75rem 0;overflow-x:auto;scrollbar-width:none;">
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>"
         style="flex-shrink:0;padding:0.4rem 1rem;border-radius:20px;font-size:0.75rem;font-weight:600;border:1px solid <?php echo ! $is_category ? 'var(--rw-green)' : 'var(--border)'; ?>;background:<?php echo ! $is_category ? 'rgba(32,170,84,0.12)' : 'var(--card)'; ?>;color:<?php echo ! $is_category ? 'var(--rw-cream)' : 'var(--muted)'; ?>;text-decoration:none;transition:all 0.2s;">
        All
      </a>
      <?php foreach ( $product_cats as $cat ) : ?>
        <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"
           style="flex-shrink:0;padding:0.4rem 1rem;border-radius:20px;font-size:0.75rem;font-weight:600;border:1px solid <?php echo ( $is_category && $current_cat->term_id === $cat->term_id ) ? 'var(--rw-green)' : 'var(--border)'; ?>;background:<?php echo ( $is_category && $current_cat->term_id === $cat->term_id ) ? 'rgba(32,170,84,0.12)' : 'var(--card)'; ?>;color:<?php echo ( $is_category && $current_cat->term_id === $cat->term_id ) ? 'var(--rw-cream)' : 'var(--muted)'; ?>;text-decoration:none;transition:all 0.2s;">
          <?php echo esc_html( $cat->name ); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- SHOP TOOLBAR -->
<div style="max-width:var(--max-w);margin:0 auto;padding:1.5rem 2rem 0.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
  <?php if ( woocommerce_result_count() ) : ?>
    <div style="font-size:0.78rem;color:var(--muted);">
      <?php woocommerce_result_count(); ?>
    </div>
  <?php endif; ?>
  <div style="font-size:0.78rem;color:var(--muted);">
    <?php woocommerce_catalog_ordering(); ?>
  </div>
</div>

<!-- PRODUCTS GRID -->
<div style="max-width:var(--max-w);margin:0 auto;padding:1rem 2rem 5rem;">

  <?php if ( woocommerce_product_loop() ) : ?>

    <?php woocommerce_product_loop_start(); ?>

      <?php while ( have_posts() ) : the_post(); ?>
        <?php wc_get_template_part( 'content', 'product' ); ?>
      <?php endwhile; ?>

    <?php woocommerce_product_loop_end(); ?>

    <!-- PAGINATION -->
    <div style="margin-top:3rem;display:flex;justify-content:center;">
      <?php woocommerce_pagination(); ?>
    </div>

  <?php else : ?>

    <!-- NO PRODUCTS FOUND -->
    <div style="text-align:center;padding:5rem 2rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">🇷🇼</div>
      <h2 style="font-family:var(--font-display);font-size:2rem;color:var(--rw-cream);margin-bottom:0.5rem;">No products found</h2>
      <p style="color:var(--muted);margin-bottom:1.5rem;">Try browsing a different category.</p>
      <a href="<?php echo esc_url( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>" class="btn-primary">View all products</a>
    </div>

  <?php endif; ?>

</div>

<?php get_footer(); ?>
