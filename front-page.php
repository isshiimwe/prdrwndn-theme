<?php
/**
 * Homepage Template
 * Template Name: Homepage
 *
 * Sections: hero (jerseys) → shop lanes → the jerseys → what the designs mean
 * → car fresheners → flags & more → shipping / pickup / WhatsApp
 */
get_header();

$jersey_url = prdrwndn_cat_url( 'prd_jersey_cat' );
$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$wa_jersey  = prdrwndn_whatsapp_url( 'Hi PRDRWNDN! I’d like to order a jersey.' );
$deal_on    = prdrwndn_deal_on();

/* ── Jersey cards: from the jersey category, or the theme's own images until the products exist ── */
$meanings = [ 'izuba' => 'Kinyarwanda for “sun”', 'imigongo' => 'The art of eastern Rwanda' ];
$cards    = [];
$from     = null;
foreach ( prdrwndn_cat_products( 'prd_jersey_cat', 4 ) as $p ) {
    $key = '';
    foreach ( $meanings as $k => $m ) { if ( stripos( $p->get_name(), $k ) !== false ) { $key = $k; break; } }
    $sizes = prdrwndn_sizes( $p );
    $cards[] = [
        'name'    => $p->get_name(),
        'url'     => $p->get_permalink(),
        'front'   => prdrwndn_product_img( $p, 'woocommerce_single', 0 ),
        'back'    => prdrwndn_product_img( $p, 'woocommerce_single', 1 ),
        'price'   => $p->get_price_html(),
        'meaning' => $key ? $meanings[ $key ] : '',
        'sizes'   => $sizes,
        'low'     => prdrwndn_low_stock_note( $sizes ),
        'in'      => $p->is_in_stock(),
    ];
    $pr = (float) $p->get_price();
    if ( $pr > 0 && ( $from === null || $pr < $from ) ) $from = $pr;
}
if ( ! $cards ) {
    foreach ( prdrwndn_fallback_jerseys() as $j ) {
        $cards[] = [ 'name' => $j['name'] . ' Jersey', 'url' => $wa_jersey ?: $shop_url, 'front' => $j['front'], 'back' => $j['back'],
                     'price' => '', 'meaning' => $j['meaning'], 'sizes' => [], 'low' => '', 'in' => true ];
    }
}
$hero_imgs = array_slice( array_values( array_filter( array_column( $cards, 'front' ) ) ), 0, 2 );

/* ── Lanes ── */
$freshener  = prdrwndn_get_freshener();
$flag_items = prdrwndn_cat_products( 'prd_flag_cat', 1 );
$lanes = [
    [ 'k' => 'Wear', 'label' => 'Jerseys & tees',  'url' => $jersey_url,                         'img' => $cards[0]['front'] ?? '' ],
    [ 'k' => 'Ride', 'label' => 'Car fresheners',  'url' => prdrwndn_cat_url( 'prd_fresh_cat' ), 'img' => $freshener ? prdrwndn_product_img( $freshener, 'woocommerce_thumbnail' ) : '' ],
    [ 'k' => 'Fly',  'label' => 'Flags',           'url' => prdrwndn_cat_url( 'prd_flag_cat' ),  'img' => $flag_items ? prdrwndn_product_img( $flag_items[0], 'woocommerce_thumbnail' ) : '' ],
];

/* ── Flags & more ── */
$more = [];
if ( function_exists( 'wc_get_products' ) ) {
    $exclude = [];
    foreach ( prdrwndn_cat_products( 'prd_jersey_cat', 20 ) as $p ) $exclude[] = $p->get_id();
    foreach ( prdrwndn_cat_products( 'prd_fresh_cat', 20 ) as $p ) $exclude[] = $p->get_id();
    if ( $freshener ) $exclude[] = $freshener->get_id();
    $more = wc_get_products( [ 'status' => 'publish', 'limit' => 4, 'exclude' => $exclude, 'orderby' => 'menu_order', 'order' => 'ASC' ] );
}
?>

<!-- ═══ HERO ═══ -->
<section class="prd-hero">
  <div class="prd-wrap prd-hero-grid">
    <div class="prd-hero-copy">
      <div class="prd-eyebrow"><span class="prd-live" aria-hidden="true"></span><?php echo esc_html( prd_mod( 'prd_hero_eyebrow' ) ); ?></div>
      <h1 class="prd-hero-title"><?php echo esc_html( prd_mod( 'prd_hero_title' ) ); ?></h1>
      <p class="prd-hero-text"><?php echo esc_html( prd_mod( 'prd_hero_text' ) ); ?></p>
      <p class="prd-hero-facts">
        <?php if ( $from ) : ?><span><strong>$<?php echo esc_html( wc_format_decimal( $from, 0 ) ); ?></strong> each</span><?php endif; ?>
        <?php if ( $deal_on ) : ?><span><strong><?php echo esc_html( prdrwndn_deal_label() ); ?></strong></span><?php endif; ?>
        <span>Sizes S–3XL</span>
      </p>
      <div class="prd-actions">
        <a class="prd-btn prd-btn-primary" href="<?php echo esc_url( $jersey_url ); ?>">Shop jerseys <?php echo prdrwndn_icon( 'arrow' ); ?></a>
        <?php if ( $wa_jersey ) : ?>
          <a class="prd-btn prd-btn-wa" href="<?php echo esc_url( $wa_jersey ); ?>" target="_blank" rel="noopener"><?php echo prdrwndn_icon( 'whatsapp' ); ?><span>Order on WhatsApp</span></a>
        <?php endif; ?>
      </div>
    </div>

    <div class="prd-hero-art" aria-hidden="true">
      <div class="prd-hero-sun"></div>
      <?php foreach ( $hero_imgs as $i => $src ) : ?>
        <img class="prd-hero-j prd-hero-j<?php echo $i + 1; ?>" src="<?php echo esc_url( $src ); ?>" alt="" loading="eager" fetchpriority="<?php echo $i === 0 ? 'high' : 'auto'; ?>"/>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ LANES ═══ -->
<section class="prd-section prd-lanes-sec" aria-label="Shop by">
  <div class="prd-wrap">
    <div class="prd-lanes">
      <?php foreach ( $lanes as $l ) : ?>
        <a class="prd-lane" href="<?php echo esc_url( $l['url'] ); ?>">
          <span class="prd-lane-img"><?php if ( $l['img'] ) : ?><img src="<?php echo esc_url( $l['img'] ); ?>" alt="" loading="lazy"/><?php endif; ?></span>
          <span class="prd-lane-text">
            <span class="prd-lane-k"><?php echo esc_html( $l['k'] ); ?></span>
            <span class="prd-lane-label"><?php echo esc_html( $l['label'] ); ?></span>
          </span>
          <span class="prd-lane-arrow"><?php echo prdrwndn_icon( 'arrow' ); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ THE JERSEYS ═══ -->
<section class="prd-section" id="jerseys">
  <div class="prd-wrap">
    <div class="prd-sec-head">
      <div>
        <div class="prd-eyebrow">The jerseys</div>
        <h2 class="prd-h2">Izuba &amp; Imigongo</h2>
      </div>
      <a class="prd-link" href="<?php echo esc_url( $jersey_url ); ?>">Shop jerseys <?php echo prdrwndn_icon( 'arrow' ); ?></a>
    </div>

    <div class="prd-jersey-grid">
      <?php foreach ( $cards as $c ) : ?>
        <a class="prd-jcard<?php echo $c['back'] ? ' has-back' : ''; ?>" href="<?php echo esc_url( $c['url'] ); ?>">
          <div class="prd-jcard-img">
            <?php if ( $c['front'] ) : ?><img class="prd-jcard-front" src="<?php echo esc_url( $c['front'] ); ?>" alt="<?php echo esc_attr( $c['name'] . ', front' ); ?>" loading="lazy"/><?php endif; ?>
            <?php if ( $c['back'] ) : ?><img class="prd-jcard-back" src="<?php echo esc_url( $c['back'] ); ?>" alt="<?php echo esc_attr( $c['name'] . ', back' ); ?>" loading="lazy"/><?php endif; ?>
            <?php if ( ! $c['in'] ) : ?><span class="prd-badge prd-badge-out">Sold out</span><?php endif; ?>
          </div>
          <div class="prd-jcard-body">
            <div class="prd-jcard-top">
              <h3><?php echo esc_html( $c['name'] ); ?></h3>
              <?php if ( $c['price'] ) : ?><span class="prd-price"><?php echo wp_kses_post( $c['price'] ); ?></span><?php endif; ?>
            </div>
            <?php if ( $c['meaning'] ) : ?><p class="prd-jcard-meaning"><?php echo esc_html( $c['meaning'] ); ?></p><?php endif; ?>
            <?php if ( $c['sizes'] ) : ?>
              <ul class="prd-size-mini" aria-label="Sizes">
                <?php foreach ( $c['sizes'] as $s ) : ?>
                  <li class="<?php echo $s['in'] ? '' : 'is-out'; ?>"><?php echo esc_html( $s['label'] ); ?><?php if ( ! $s['in'] ) echo '<span class="screen-reader-text"> sold out</span>'; ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if ( $c['low'] ) : ?><p class="prd-low"><?php echo esc_html( $c['low'] ); ?></p><?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ( $deal_on ) : ?>
      <div class="prd-deal-band">
        <span class="prd-deal-big"><?php echo esc_html( prdrwndn_deal_label() ); ?></span>
        <span>Mix and match any two jerseys. The discount is taken off in your cart.</span>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ WHAT THE DESIGNS MEAN ═══ -->
<section class="prd-story">
  <?php prdrwndn_imigongo(); ?>
  <div class="prd-wrap prd-story-grid">
    <div class="prd-story-intro">
      <div class="prd-eyebrow">What you’re wearing</div>
      <h2 class="prd-h2">Drawn from home.</h2>
    </div>
    <div class="prd-story-items">
      <article>
        <h3>Izuba</h3>
        <p class="prd-serif">“sun”</p>
        <p>The sun from Rwanda’s flag rises out of the crest and spreads across the shirt, above a row of green hills at the hem.</p>
      </article>
      <article>
        <h3>Imigongo</h3>
        <p class="prd-serif">the art of the east</p>
        <p>The bold geometric relief art of eastern Rwanda. Its triangles cover the sleeves and run across the shoulders on the back.</p>
      </article>
      <article>
        <h3>Umunyarwanda · 250</h3>
        <p class="prd-serif">on the back</p>
        <p>“A Rwandan”, over 250, Rwanda’s calling code. On both jerseys.</p>
      </article>
    </div>
  </div>
</section>

<!-- ═══ CAR FRESHENERS ═══ -->
<?php if ( $freshener ) :
  $f_img   = prdrwndn_product_img( $freshener, 'woocommerce_single' );
  $f_links = prdrwndn_freshener_links( $freshener, function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '' );
?>
<section class="prd-section">
  <div class="prd-wrap prd-feature">
    <a class="prd-feature-img" href="<?php echo esc_url( $freshener->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
      <?php if ( $f_img ) : ?><img src="<?php echo esc_url( $f_img ); ?>" alt="" loading="lazy"/><?php endif; ?>
    </a>
    <div class="prd-feature-body">
      <div class="prd-eyebrow">Ride · Car fresheners</div>
      <h2 class="prd-h2">The one that started it.</h2>
      <p class="prd-lead">Cut in the exact shape of Rwanda’s borders, in flag colors. Hang it from your mirror.</p>
      <p class="prd-price-lg"><?php echo wp_kses_post( $freshener->get_price_html() ); ?> <small>each</small></p>
      <?php if ( $f_links ) : ?>
        <div class="prd-chip-row">
          <?php foreach ( $f_links as $fl ) : ?>
            <a class="prd-chip-btn" href="<?php echo esc_url( $fl['url'] ); ?>" rel="nofollow">+ <?php echo esc_html( $fl['label'] ); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <a class="prd-link" href="<?php echo esc_url( prdrwndn_cat_url( 'prd_fresh_cat' ) ); ?>">All fresheners <?php echo prdrwndn_icon( 'arrow' ); ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══ FLAGS & MORE ═══ -->
<?php if ( $more ) : ?>
<section class="prd-section prd-section-sand">
  <div class="prd-wrap">
    <div class="prd-sec-head">
      <div>
        <div class="prd-eyebrow">Fly · Flags &amp; more</div>
        <h2 class="prd-h2">For the home and the game.</h2>
      </div>
      <a class="prd-link" href="<?php echo esc_url( $shop_url ); ?>">Shop all <?php echo prdrwndn_icon( 'arrow' ); ?></a>
    </div>
    <ul class="products prd-grid">
      <?php
      foreach ( $more as $p ) {
          $GLOBALS['product'] = $p;
          $GLOBALS['post']    = get_post( $p->get_id() );
          setup_postdata( $GLOBALS['post'] );
          wc_get_template_part( 'content', 'product' );
      }
      wp_reset_postdata();
      ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- ═══ INFO ═══ -->
<section class="prd-info">
  <div class="prd-wrap prd-info-grid">
    <?php if ( prd_mod( 'prd_pickup_on' ) ) : ?>
      <div class="prd-info-item"><?php echo prdrwndn_icon( 'pin' ); ?><div><strong><?php echo esc_html( prd_mod( 'prd_pickup_text' ) ); ?></strong><span>Choose local pickup at checkout.</span></div></div>
    <?php endif; ?>
    <div class="prd-info-item"><?php echo prdrwndn_icon( 'box' ); ?><div><strong>Ships from Phoenix, AZ</strong><span>Shipping options and costs at checkout.</span></div></div>
    <?php $wa_q = prdrwndn_whatsapp_url( 'Hi PRDRWNDN! I have a question.' ); if ( $wa_q ) : ?>
      <a class="prd-info-item" href="<?php echo esc_url( $wa_q ); ?>" target="_blank" rel="noopener"><?php echo prdrwndn_icon( 'chat' ); ?><div><strong>Questions? WhatsApp us</strong><span>Sizing, orders, anything.</span></div></a>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
