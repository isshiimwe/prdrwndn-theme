<?php get_header(); ?>

<main style="padding:8rem 2rem 4rem;min-height:60vh;">
  <div class="container">
    <?php if ( have_posts() ) : ?>
      <div class="eyebrow">Latest</div>
      <h1 class="display-title" style="margin-bottom:2rem;">
        <?php if ( is_archive() ) the_archive_title();
        elseif ( is_search() ) echo 'Search: ' . get_search_query();
        else echo 'Posts'; ?>
      </h1>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem;">
        <?php while ( have_posts() ) : the_post(); ?>
          <article style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;">
            <?php if ( has_post_thumbnail() ) : ?>
              <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail( 'medium', [ 'style' => 'width:100%;height:200px;object-fit:cover;' ] ); ?>
              </a>
            <?php endif; ?>
            <div style="padding:1.25rem;">
              <h2 style="font-size:1rem;font-weight:600;color:var(--rw-cream);margin-bottom:0.5rem;">
                <a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a>
              </h2>
              <p style="font-size:0.82rem;color:var(--muted);line-height:1.6;"><?php the_excerpt(); ?></p>
              <a href="<?php the_permalink(); ?>" style="font-size:0.78rem;color:var(--rw-green);font-weight:600;margin-top:0.75rem;display:inline-block;">Read more →</a>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <div style="margin-top:2rem;"><?php the_posts_pagination(); ?></div>
    <?php else : ?>
      <div style="text-align:center;padding:4rem 0;">
        <div style="font-size:3rem;margin-bottom:1rem;">🇷🇼</div>
        <h2 style="font-family:var(--font-display);font-size:2rem;color:var(--rw-cream);margin-bottom:0.5rem;">Nothing found.</h2>
        <p style="color:var(--muted);">Try a different search or browse the shop.</p>
        <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="btn-primary" style="margin-top:1.5rem;display:inline-flex;">Browse Shop</a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>
