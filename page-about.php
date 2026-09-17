<?php
/**
 * Template Name: About
 */
get_header(); ?>

<!-- HERO -->
<section style="min-height:70vh;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:8rem 2rem 4rem;position:relative;overflow:hidden;background:var(--rw-green3);">
  <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(8,8,8,0.4) 0%,rgba(8,8,8,0.85) 100%);z-index:1;"></div>
  <div style="position:relative;z-index:2;max-width:700px;">
    <div class="eyebrow">Our Story</div>
    <h1 style="font-family:var(--font-serif);font-size:clamp(3rem,8vw,7rem);line-height:1;font-weight:400;color:var(--rw-cream);margin-bottom:1rem;">
      Not just what<br/>we <em style="color:var(--rw-green);">wear.</em>
    </h1>
    <p style="font-size:1.05rem;color:var(--muted);max-width:500px;line-height:1.75;">A contemporary cultural brand rooted in memory, place and pride.</p>
  </div>
</section>

<!-- STORY -->
<section class="section-pad">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center;" class="reveal">
      <div style="aspect-ratio:4/5;border-radius:var(--radius-lg);overflow:hidden;background:var(--surface2);">
        <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'large', [ 'style' => 'width:100%;height:100%;object-fit:cover;' ] );
        else : ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--rw-green3);font-size:6rem;">🇷🇼</div>
        <?php endif; ?>
      </div>
      <div>
        <div class="eyebrow">Who we are</div>
        <h2 class="display-title" style="margin-bottom:1.5rem;">PRDRWNDN<em style="color:var(--rw-green);">®</em></h2>
        <p style="font-size:1rem;color:rgba(232,226,213,0.75);line-height:1.85;margin-bottom:1rem;"><strong style="color:var(--rw-cream);">PRDRWNDN started with a simple question:</strong> what would it look like to make a homesick person feel held?</p>
        <p style="font-size:0.92rem;color:var(--muted);line-height:1.85;margin-bottom:1rem;">We make contemporary things with an old-soul point of view. A graphic tee can carry a landscape. A bracelet can carry a blessing. A flag can turn any room into a return.</p>
        <p style="font-size:0.92rem;color:var(--muted);line-height:1.85;margin-bottom:1.5rem;">Our car fresheners — cut in the exact shape of Rwanda's borders — started it all. A small thing to hang from your mirror, carrying the scent of something familiar. A daily reminder that you belong somewhere.</p>
        <div style="display:flex;gap:4px;margin-bottom:1.5rem;">
          <span style="height:4px;width:40px;border-radius:2px;background:#20AA54;"></span>
          <span style="height:4px;width:40px;border-radius:2px;background:#FFD700;"></span>
          <span style="height:4px;width:40px;border-radius:2px;background:#20A0D0;"></span>
        </div>
        <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-primary">Shop the collection</a>
      </div>
    </div>
  </div>
</section>

<!-- VALUES -->
<div style="background:var(--rw-green3);padding:5rem 0;">
  <div class="container">
    <div class="eyebrow reveal">What we stand for</div>
    <h2 class="serif-title reveal reveal-delay-1" style="color:var(--rw-cream);margin-bottom:2.5rem;">Our values.</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius);overflow:hidden;">
      <?php
      $values = [
        [ 'icon' => '🌍', 'title' => 'Memory',  'desc' => 'Every product carries a piece of Rwanda — its shape, its colors, its feeling.' ],
        [ 'icon' => '🏔️', 'title' => 'Place',   'desc' => 'The thousand hills. The borders you\'d trace on a map. The land that holds you.' ],
        [ 'icon' => '❤️', 'title' => 'Pride',   'desc' => 'Not loud. Not performative. Just the quiet certainty of knowing where you\'re from.' ],
        [ 'icon' => '🤝', 'title' => 'Community','desc' => 'For Rwandans everywhere — and friends of Rwanda who\'ve felt the pull of a place they call home.' ],
      ];
      foreach ( $values as $v ) : ?>
        <div style="background:rgba(8,8,8,0.4);padding:2rem;">
          <div style="font-size:1.5rem;margin-bottom:0.75rem;"><?php echo $v['icon']; ?></div>
          <div style="font-family:var(--font-display);font-size:1.4rem;letter-spacing:1px;color:var(--rw-cream);margin-bottom:0.5rem;"><?php echo esc_html( $v['title'] ); ?></div>
          <p style="font-size:0.83rem;color:rgba(232,226,213,0.55);line-height:1.7;"><?php echo esc_html( $v['desc'] ); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- CTA -->
<section class="section-pad">
  <div class="container" style="text-align:center;">
    <h2 class="display-title reveal" style="margin-bottom:1rem;">Start with a<br/><em style="color:var(--rw-green);">freshener.</em></h2>
    <p style="color:var(--muted);font-size:1rem;margin-bottom:2rem;" class="reveal reveal-delay-1">$5. Rwanda map-shaped. Ships worldwide. That's where PRDRWNDN® begins.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;" class="reveal reveal-delay-2">
      <a href="<?php echo esc_url( home_url( '/shop/prdrwndn-car-fresheners/' ) ); ?>" class="btn-primary">Shop car fresheners</a>
      <a href="<?php echo esc_url( home_url( '/collection/' ) ); ?>" class="btn-ghost">Full collection</a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
