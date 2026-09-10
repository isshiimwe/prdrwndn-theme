<?php
/**
 * Homepage Template
 * Template Name: Homepage
 */
get_header();

// ── Get featured freshener ──
$freshener = prdrwndn_get_freshener();

// ── Get secondary products (exclude freshener) ──
$exclude_id  = $freshener ? $freshener->get_id() : 0;
$prod_count  = (int) get_theme_mod( 'prdrwndn_products_count', 4 );
$other_products = new WP_Query( [
    'post_type'      => 'product',
    'posts_per_page' => $prod_count,
    'post_status'    => 'publish',
    'post__not_in'   => $exclude_id ? [ $exclude_id ] : [],
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
] );
?>

<!-- ═══════════════════════════════════
     HERO
═══════════════════════════════════ -->
<section class="site-hero">
  <div class="hero-bg-gradient"></div>
  <div class="hero-bg-img" style="background-image:url('<?php echo esc_url( get_theme_mod( 'prdrwndn_hero_bg', get_template_directory_uri() . '/assets/hero-bg.jpg' ) ); ?>')"></div>

  <?php if ( $freshener ) :
    $img_id  = $freshener->get_image_id();
    $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'large' ) : '';
    if ( $img_url ) : ?>
      <img class="hero-product-float"
           src="<?php echo esc_url( $img_url ); ?>"
           alt="<?php echo esc_attr( $freshener->get_name() ); ?>"
           loading="eager"/>
    <?php endif;
  endif; ?>

  <div class="hero-content">
    <div class="hero-eyebrow">PRDRWNDN® · Phoenix, AZ</div>
    <h1 class="hero-h1">
      <?php echo esc_html( get_theme_mod( 'prdrwndn_hero_line1', 'YOUR ROOTS.' ) ); ?><br/>
      <em><?php echo esc_html( get_theme_mod( 'prdrwndn_hero_line2', 'YOUR HOME.' ) ); ?></em>
    </h1>
    <p class="hero-serif">For Rwandans everywhere — and friends of Rwanda who know that home can be a feeling, a pattern, a color.</p>
    <p class="hero-sub"><?php echo esc_html( get_theme_mod( 'prdrwndn_tagline', 'A contemporary cultural brand rooted in memory, place and pride.' ) ); ?></p>
    <div class="hero-actions">
      <a href="<?php echo esc_url( home_url( '/shop/prdrwndn-car-fresheners/' ) ); ?>" class="btn-primary">Shop Fresheners</a>
      <a href="<?php echo esc_url( home_url( '/collection/' ) ); ?>" class="btn-ghost">Full Collection</a>
    </div>
  </div>

  <div class="hero-stats">
    <div>
      <div class="hero-stat-num">$5</div>
      <div class="hero-stat-label">Per freshener</div>
    </div>
    <div>
      <div class="hero-stat-num">2</div>
      <div class="hero-stat-label">Signature scents</div>
    </div>
    <div>
      <div class="hero-stat-num">🌍</div>
      <div class="hero-stat-label">Ships worldwide</div>
    </div>
  </div>
</section>

<!-- MARQUEE -->
<div class="marquee-bar">
  <div class="marquee-track" aria-hidden="true">
    <?php $items = [ 'Car Fresheners', 'Rwanda Map Shape', 'Black Ice', 'Desert Rose', 'Ships Worldwide', '$5 Each', 'Heritage Apparel', 'Cultural Fashion', 'Proud Rwandan', 'Free Shipping Over $35' ];
    $repeated = array_merge( $items, $items );
    foreach ( $repeated as $item ) : ?>
      <span class="marquee-item">
        <?php echo esc_html( $item ); ?>
        <span class="marquee-dot"></span>
      </span>
    <?php endforeach; ?>
  </div>
</div>

<!-- ═══════════════════════════════════
     FRESHENER SPOTLIGHT
═══════════════════════════════════ -->
<?php if ( $freshener ) :
  $f_img_id  = $freshener->get_image_id();
  $f_img_url = $f_img_id ? wp_get_attachment_image_url( $f_img_id, 'large' ) : '';
  $f_price   = $freshener->get_price();
  $f_url     = $freshener->get_permalink();
  $gallery   = $freshener->get_gallery_image_ids();

  // Get variations for scent pills
  $variations = [];
  if ( $freshener->is_type( 'variable' ) ) {
    foreach ( $freshener->get_children() as $child_id ) {
      $variation = wc_get_product( $child_id );
      if ( $variation ) {
        $attrs = $variation->get_variation_attributes();
        foreach ( $attrs as $name => $val ) {
          if ( $val ) $variations[] = $val;
        }
      }
    }
  }
?>
<section class="section-pad">
  <div class="container">
    <div class="eyebrow reveal">Signature Product</div>
    <div class="freshener-spotlight reveal reveal-delay-1">

      <!-- Visual -->
      <div class="spotlight-visual">
        <?php if ( $f_img_url ) : ?>
          <img src="<?php echo esc_url( $f_img_url ); ?>"
               alt="<?php echo esc_attr( $freshener->get_name() ); ?>"/>
        <?php else : ?>
          <div style="font-size:6rem;">🧊</div>
        <?php endif; ?>
        <div class="spotlight-visual-overlay"></div>
      </div>

      <!-- Content -->
      <div class="spotlight-body">
        <div>
          <div class="eyebrow"><?php echo esc_html( $freshener->get_name() ); ?></div>
          <div class="display-title" style="font-size:clamp(1.5rem,3vw,2rem); margin-bottom:0.5rem;">Rwanda Map-Shaped Car Freshener</div>
          <div class="spotlight-price-tag">
            $<?php echo esc_html( number_format( (float) $f_price, 0 ) ); ?>
            <small>per freshener · free shipping over $35</small>
          </div>
        </div>

        <?php if ( ! empty( $variations ) ) : ?>
        <div>
          <div style="font-size:0.7rem;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:0.5rem;">Choose your scent</div>
          <div class="scent-selector">
            <?php foreach ( array_unique( $variations ) as $i => $scent ) : ?>
              <button class="scent-pill <?php echo $i === 0 ? 'active' : ''; ?>"
                      data-scent="<?php echo esc_attr( $scent ); ?>">
                <?php echo esc_html( $scent ); ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else : ?>
        <div class="scent-selector">
          <button class="scent-pill active">🧊 Black Ice</button>
          <button class="scent-pill">🌹 Desert Rose</button>
        </div>
        <?php endif; ?>

        <div class="spotlight-feats">
          <div class="spotlight-feat"><span class="feat-check">✓</span> Cut in the exact shape of Rwanda's borders</div>
          <div class="spotlight-feat"><span class="feat-check">✓</span> Rwanda flag colors throughout the design</div>
          <div class="spotlight-feat"><span class="feat-check">✓</span> Long-lasting premium fragrance</div>
          <div class="spotlight-feat"><span class="feat-check">✓</span> Ships worldwide from Phoenix, AZ</div>
        </div>

        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
          <a href="<?php echo esc_url( $f_url ); ?>" class="btn-primary">Add to cart — $<?php echo esc_html( number_format( (float) $f_price, 0 ) ); ?></a>
          <a href="<?php echo esc_url( $f_url ); ?>" class="btn-ghost">View details</a>
        </div>
      </div>

    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════
     EDITORIAL — "Not just what we wear"
═══════════════════════════════════ -->
<div class="editorial-strip">
  <div class="editorial-inner">
    <div class="reveal">
      <h2 class="editorial-headline">Not just<br/>what we <em>wear.</em></h2>
    </div>
    <div class="editorial-body reveal reveal-delay-1">
      <p>PRDRWNDN started with a simple question: <strong>what would it look like to make a homesick person feel held?</strong></p>
      <p>We make contemporary things with an old-soul point of view. A graphic tee can carry a landscape. A bracelet can carry a blessing. A flag can turn any room into a return.</p>
      <p>A car freshener shaped like Rwanda — hanging from your mirror, carrying the scent of something familiar — is a small act of remembering that you belong somewhere.</p>
      <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>" class="btn-cream" style="margin-top:0.5rem;">Our story →</a>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════
     HOME COLLECTION — Other Products
═══════════════════════════════════ -->
<?php if ( $other_products->have_posts() ) : ?>
<section class="section-pad">
  <div class="container">
    <div class="products-section-head reveal">
      <div>
        <div class="eyebrow">Home Collection</div>
        <h2 style="font-family:var(--font-display);font-size:clamp(1.8rem,4vw,3rem);letter-spacing:1px;line-height:0.95;">More from PRDRWNDN<em style="color:var(--rw-green);">®</em></h2>
      </div>
      <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="see-all-link">See all →</a>
    </div>

    <div class="woo-products-grid reveal reveal-delay-1">
      <?php while ( $other_products->have_posts() ) : $other_products->the_post();
        global $product;
        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) continue;
        $img_id  = $product->get_image_id();
        $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';
        $price   = $product->get_price_html();
        $link    = $product->get_permalink();
        $name    = $product->get_name();
      ?>
        <a href="<?php echo esc_url( $link ); ?>" style="display:block;">
          <div style="aspect-ratio:1;overflow:hidden;background:var(--surface2);">
            <?php if ( $img_url ) : ?>
              <img src="<?php echo esc_url( $img_url ); ?>"
                   alt="<?php echo esc_attr( $name ); ?>"
                   style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s ease;"
                   loading="lazy"/>
            <?php else : ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;">🇷🇼</div>
            <?php endif; ?>
          </div>
          <div style="padding:0.85rem 1rem;">
            <div style="font-size:0.85rem;font-weight:600;color:var(--rw-cream);margin-bottom:4px;"><?php echo esc_html( $name ); ?></div>
            <div style="font-size:0.9rem;font-weight:700;color:var(--rw-green);"><?php echo wp_kses_post( $price ); ?></div>
          </div>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <div style="text-align:center;margin-top:2rem;" class="reveal">
      <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-ghost">Browse the full collection →</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════
     APP PROMO
═══════════════════════════════════ -->
<div class="app-promo-section">
  <div class="app-promo-inner">

    <!-- Phone mockup -->
    <div class="app-phone-mockup reveal">
      <div class="app-phone-shell">
        <div class="app-phone-notch"></div>
        <div class="app-phone-screen">
          <div class="app-screen-header">
            <div class="app-screen-logo">PRDRNDN®</div>
            <div class="app-screen-flag">
              <span style="background:#20AA54;"></span>
              <span style="background:#FFD700;"></span>
              <span style="background:#20A0D0;"></span>
            </div>
          </div>
          <div class="app-screen-body">
            <div class="app-screen-card">
              <span class="app-screen-card-icon">🧊</span>
              <div>
                <div class="app-screen-card-label">Black Ice Freshener</div>
                <div class="app-screen-card-price">$5.00</div>
              </div>
            </div>
            <div class="app-screen-card">
              <span class="app-screen-card-icon">🌹</span>
              <div>
                <div class="app-screen-card-label">Desert Rose Freshener</div>
                <div class="app-screen-card-price">$5.00</div>
              </div>
            </div>
            <div class="app-screen-card">
              <span class="app-screen-card-icon">🇷🇼</span>
              <div>
                <div class="app-screen-card-label">Heritage Apparel</div>
                <div class="app-screen-card-price">From $25.00</div>
              </div>
            </div>
            <div class="app-screen-btn">Shop Now</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="app-content reveal reveal-delay-1">
      <div class="eyebrow">PRDRWNDN® App</div>
      <h2>Shop faster.<br/><em>Anywhere.</em></h2>
      <p>Install our PWA app directly from your browser — no App Store needed. Full shop, cart, orders, and offline browsing right from your home screen.</p>
      <div class="app-features">
        <div class="app-feature"><span class="app-feature-icon">⚡</span> Faster than the website — fullscreen app</div>
        <div class="app-feature"><span class="app-feature-icon">📦</span> Track your orders in real time</div>
        <div class="app-feature"><span class="app-feature-icon">✈️</span> Browse products offline</div>
        <div class="app-feature"><span class="app-feature-icon">🔒</span> Secure checkout with Stripe</div>
      </div>
      <a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>" class="btn-primary">📲 Get the free app</a>
    </div>

  </div>
</div>

<!-- ═══════════════════════════════════
     REVIEWS
═══════════════════════════════════ -->
<section class="section-pad">
  <div class="container">
    <div class="eyebrow reveal">What people are saying</div>
    <h2 class="display-title reveal reveal-delay-1" style="margin-bottom:2rem;">From the community.</h2>
    <div class="reviews-grid">
      <?php
      $reviews = [
        [ 'text' => 'Bought the Black Ice freshener and my car smells amazing. The Rwanda shape is so clean — everyone who gets in asks about it.', 'author' => 'Amira U.', 'location' => 'Kigali, Rwanda', 'stars' => 5 ],
        [ 'text' => 'I ordered two Desert Rose ones for my mom and sister. Perfect gift. Shipped fast from Phoenix, arrived in great condition.', 'author' => 'Patrick N.', 'location' => 'Brussels, Belgium', 'stars' => 5 ],
        [ 'text' => 'As a Rwandan in the diaspora this brand hits different. The car freshener is a small but meaningful thing to carry home with you.', 'author' => 'Cynthia K.', 'location' => 'Toronto, Canada', 'stars' => 5 ],
      ];
      foreach ( $reviews as $i => $r ) : ?>
        <div class="review-card reveal reveal-delay-<?php echo $i + 1; ?>">
          <div class="review-stars"><?php echo prdrwndn_stars( $r['stars'] ); ?></div>
          <p class="review-text">"<?php echo esc_html( $r['text'] ); ?>"</p>
          <div>
            <div class="review-author"><?php echo esc_html( $r['author'] ); ?></div>
            <div class="review-location"><?php echo esc_html( $r['location'] ); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
