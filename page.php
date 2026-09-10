<?php get_header(); ?>

<main style="padding:8rem 2rem 4rem;min-height:60vh;">
  <div class="container" style="max-width:860px;">
    <?php while ( have_posts() ) : the_post(); ?>
      <article>
        <h1 style="font-family:var(--font-display);font-size:clamp(2.5rem,6vw,5rem);color:var(--rw-cream);letter-spacing:1px;margin-bottom:1.5rem;line-height:0.95;">
          <?php the_title(); ?>
        </h1>
        <div style="font-size:0.95rem;color:var(--muted);line-height:1.85;" class="page-content">
          <?php the_content(); ?>
        </div>
      </article>
    <?php endwhile; ?>
  </div>
</main>

<?php get_footer(); ?>
