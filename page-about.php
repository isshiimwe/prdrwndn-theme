<?php
/**
 * Template Name: About
 */
get_header();
$jersey_url = prdrwndn_cat_url( 'prd_jersey_cat' );
$img = get_template_directory_uri() . '/assets/img/';
?>

<section class="prd-pagehead prd-pagehead-lg">
  <div class="prd-wrap">
    <div class="prd-eyebrow">Our story</div>
    <h1 class="prd-h1">Not just what we <em class="prd-serif">wear.</em></h1>
    <p class="prd-pagehead-desc"><?php echo esc_html( prd_mod( 'prdrwndn_tagline' ) ); ?></p>
  </div>
</section>

<section class="prd-section">
  <div class="prd-wrap prd-feature">
    <div class="prd-feature-img prd-feature-img-tall">
      <?php
      $about_jersey = prdrwndn_cat_products( 'prd_jersey_cat', 1 );
      $about_img    = $about_jersey ? prdrwndn_product_img( $about_jersey[0], 'large' ) : '';
      if ( has_post_thumbnail() ) :
        the_post_thumbnail( 'large' );
      elseif ( $about_img ) : ?>
        <img src="<?php echo esc_url( $about_img ); ?>" alt="" loading="lazy"/>
      <?php else : ?>
        <img src="<?php echo esc_url( $img . 'izuba-front.webp' ); ?>" alt="" loading="lazy"/>
      <?php endif; ?>
    </div>
    <div class="prd-feature-body prd-prose">
      <div class="prd-eyebrow">Who we are</div>
      <h2 class="prd-h2">PRDRWNDN®</h2>
      <p><strong>PRDRWNDN started with a simple question:</strong> what would it look like to make a homesick person feel held?</p>
      <p>We make contemporary things with an old-soul point of view. A jersey can carry the sun from a flag. A pattern can carry a region’s art. A flag can turn any room into a return.</p>
      <p>Our car fresheners — cut in the exact shape of Rwanda’s borders — started it all. Now there are football jerseys too: Izuba and Imigongo, made for Rwandans everywhere.</p>
      <?php prdrwndn_flag_stripe( 'prd-flag-mini' ); ?>
      <div class="prd-actions">
        <a href="<?php echo esc_url( $jersey_url ); ?>" class="prd-btn prd-btn-primary">Shop the jerseys</a>
        <a href="<?php echo esc_url( prdrwndn_cat_url( 'prd_fresh_cat' ) ); ?>" class="prd-btn prd-btn-ghost">Car fresheners</a>
      </div>
    </div>
  </div>
</section>

<?php
/* From the founder: the About page's own text (edit it in Pages → About). */
$prd_story = get_post_field( 'post_content', get_queried_object_id() );
if ( trim( wp_strip_all_tags( (string) $prd_story ) ) !== '' ) : ?>
<section class="prd-founder" id="founder">
  <div class="prd-wrap prd-founder-grid">
    <div class="prd-founder-head">
      <div class="prd-eyebrow">From the founder</div>
      <h2 class="prd-h2">My story.</h2>
      <?php prdrwndn_flag_stripe( 'prd-flag-mini' ); ?>
    </div>
    <div class="prd-founder-body">
      <?php echo apply_filters( 'the_content', $prd_story ); // phpcs:ignore ?>
      <p class="prd-founder-sign"><a href="https://instagram.com/isshiimwe" target="_blank" rel="noopener">Kanyarwanda</a> · Founder · Phoenix, Arizona</p>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="prd-story">
  <?php prdrwndn_imigongo(); ?>
  <div class="prd-wrap prd-story-grid">
    <div class="prd-story-intro">
      <div class="prd-eyebrow">What we stand for</div>
      <h2 class="prd-h2">Our values.</h2>
    </div>
    <div class="prd-story-items prd-story-items-4">
      <?php
      $values = [
        [ 'Memory',    'Every product carries a piece of Rwanda — its shape, its colors, its feeling.' ],
        [ 'Place',     'The thousand hills. The borders you’d trace on a map. The land that holds you.' ],
        [ 'Pride',     'Not loud. Not performative. Just the quiet certainty of knowing where you’re from.' ],
        [ 'Community', 'For Rwandans everywhere — and friends of Rwanda who’ve felt the pull of a place they call home.' ],
      ];
      foreach ( $values as $v ) : ?>
        <article><h3><?php echo esc_html( $v[0] ); ?></h3><p><?php echo esc_html( $v[1] ); ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
