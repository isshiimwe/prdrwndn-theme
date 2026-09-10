<?php get_header(); ?>

<main style="min-height:80vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:8rem 2rem 4rem;">
  <div>
    <svg width="80" height="72" viewBox="0 0 200 180" xmlns="http://www.w3.org/2000/svg" style="margin:0 auto 1.5rem;filter:drop-shadow(0 0 20px rgba(32,170,84,0.4));">
      <path d="M40,30 L60,20 L90,15 L120,18 L150,30 L165,55 L170,80 L160,110 L145,135 L120,155 L95,165 L70,158 L45,140 L30,115 L25,88 L30,60 Z" fill="#20AA54" opacity="0.15"/>
      <path d="M40,30 L60,20 L90,15 L120,18 L150,30 L165,55 L170,80 L160,110 L145,135 L120,155 L95,165 L70,158 L45,140 L30,115 L25,88 L30,60 Z" fill="none" stroke="#20AA54" stroke-width="3"/>
      <circle cx="100" cy="88" r="5" fill="#FFD700"/>
    </svg>
    <div class="eyebrow" style="justify-content:center;">404</div>
    <h1 style="font-family:var(--font-display);font-size:clamp(3rem,8vw,6rem);color:var(--rw-cream);line-height:0.95;margin-bottom:1rem;">
      Page not<br/><em style="color:var(--rw-green);">found.</em>
    </h1>
    <div style="display:flex;gap:4px;justify-content:center;margin:1.25rem 0;">
      <span style="height:4px;width:40px;border-radius:2px;background:#20AA54;"></span>
      <span style="height:4px;width:40px;border-radius:2px;background:#FFD700;"></span>
      <span style="height:4px;width:40px;border-radius:2px;background:#20A0D0;"></span>
    </div>
    <p style="color:var(--muted);font-size:0.95rem;margin-bottom:2rem;max-width:340px;margin-left:auto;margin-right:auto;line-height:1.7;">
      This page doesn't exist — but Rwanda does, and so does our shop.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-primary">Go home</a>
      <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-ghost">Browse shop</a>
    </div>
  </div>
</main>

<?php get_footer(); ?>
