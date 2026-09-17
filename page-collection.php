<?php
/**
 * Template Name: Collection
 */
get_header();

// Get all published products
$collection = new WP_Query( [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
] );

// Get product categories
$cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true, 'exclude' => get_option( 'default_product_cat' ) ] );
?>

<!-- HERO -->
<section style="padding:9rem 2rem 4rem;background:var(--rw-green3);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(8,8,8,0.5),rgba(8,8,8,0.9));z-index:1;"></div>
  <div style="position:relative;z-index:2;max-width:var(--max-w);margin:0 auto;">
    <div class="eyebrow">PRDRWNDN®</div>
    <h1 class="display-title" style="font-size:clamp(3rem,8vw,7rem);color:var(--rw-cream);">The Full<br/><em style="color:var(--rw-green);">Collection.</em></h1>
    <p style="font-size:0.95rem;color:var(--muted);margin-top:1rem;max-width:460px;line-height:1.75;">Everything we make — from the car freshener that started it all, to heritage apparel, flags, and cultural pieces for the global Rwandan community.</p>
  </div>
</section>

<!-- FILTER BAR -->
<div style="background:var(--surface);border-bottom:1px solid var(--border);position:sticky;top:64px;z-index:50;">
  <div class="container" style="padding-top:0;padding-bottom:0;">
    <div style="display:flex;gap:0.5rem;padding:0.75rem 0;overflow-x:auto;scrollbar-width:none;">
      <button class="filter-chip active" data-cat="all" onclick="filterProducts('all',this)"
        style="flex-shrink:0;padding:0.4rem 1rem;border-radius:20px;font-size:0.75rem;font-weight:600;border:1px solid var(--rw-green);background:rgba(32,170,84,0.12);color:var(--rw-cream);cursor:pointer;transition:all 0.2s;">
        All Products
      </button>
      <?php foreach ( $cats as $cat ) : ?>
        <button class="filter-chip" data-cat="<?php echo esc_attr( $cat->slug ); ?>"
                onclick="filterProducts('<?php echo esc_attr( $cat->slug ); ?>',this)"
          style="flex-shrink:0;padding:0.4rem 1rem;border-radius:20px;font-size:0.75rem;font-weight:600;border:1px solid var(--border);background:rgba(255,255,255,0.04);color:var(--muted);cursor:pointer;transition:all 0.2s;">
          <?php echo esc_html( $cat->name ); ?>
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- PRODUCTS GRID -->
<section class="section-pad">
  <div class="container">
    <div id="collection-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1px;background:var(--border);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
      <?php while ( $collection->have_posts() ) : $collection->the_post();
        global $product;
        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) continue;

        $img_id   = $product->get_image_id();
        $img_url  = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
        $price    = $product->get_price_html();
        $link     = $product->get_permalink();
        $name     = $product->get_name();
        $cats_raw = wp_get_post_terms( get_the_ID(), 'product_cat', [ 'fields' => 'slugs' ] );
        $cats_str = implode( ' ', $cats_raw );
        $is_new   = ( strtotime( get_the_date( 'Y-m-d' ) ) > strtotime( '-30 days' ) );
      ?>
        <div class="collection-item" data-cats="<?php echo esc_attr( $cats_str ); ?>"
             style="background:var(--card);cursor:pointer;transition:background 0.2s;"
             onmouseenter="this.style.background='var(--card2)'"
             onmouseleave="this.style.background='var(--card)'">
          <a href="<?php echo esc_url( $link ); ?>" style="display:block;">
            <div style="aspect-ratio:1;overflow:hidden;background:var(--surface2);position:relative;">
              <?php if ( $img_url ) : ?>
                <img src="<?php echo esc_url( $img_url ); ?>"
                     alt="<?php echo esc_attr( $name ); ?>"
                     loading="lazy"
                     style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s ease;"/>
              <?php else : ?>
                <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;background:var(--rw-green3);">🇷🇼</div>
              <?php endif; ?>
              <?php if ( $is_new ) : ?>
                <span style="position:absolute;top:10px;left:10px;font-size:0.62rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;padding:3px 8px;border-radius:4px;background:var(--rw-green);color:#fff;">New</span>
              <?php endif; ?>
            </div>
            <div style="padding:1rem;">
              <div style="font-size:0.88rem;font-weight:600;color:var(--rw-cream);margin-bottom:4px;"><?php echo esc_html( $name ); ?></div>
              <div style="font-size:0.95rem;font-weight:700;color:var(--rw-green);"><?php echo wp_kses_post( $price ); ?></div>
            </div>
          </a>
        </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <div id="no-results" style="display:none;text-align:center;padding:4rem;color:var(--muted);">
      <div style="font-size:2rem;margin-bottom:1rem;">🇷🇼</div>
      No products in this category yet.
    </div>
  </div>
</section>

<script>
function filterProducts(cat, btn) {
    // Update active chip
    document.querySelectorAll('.filter-chip').forEach(function(c) {
        c.style.border = '1px solid var(--border)';
        c.style.background = 'rgba(255,255,255,0.04)';
        c.style.color = 'var(--muted)';
    });
    btn.style.border = '1px solid var(--rw-green)';
    btn.style.background = 'rgba(32,170,84,0.12)';
    btn.style.color = 'var(--rw-cream)';

    // Filter items
    var items = document.querySelectorAll('.collection-item');
    var visible = 0;
    items.forEach(function(item) {
        var cats = item.getAttribute('data-cats');
        var show = (cat === 'all' || cats.indexOf(cat) !== -1);
        item.style.display = show ? 'block' : 'none';
        if (show) visible++;
    });
    document.getElementById('no-results').style.display = visible === 0 ? 'block' : 'none';
}
</script>

<?php get_footer(); ?>
