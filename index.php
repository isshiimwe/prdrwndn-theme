<?php get_header(); ?>
<div class="prd-wrap prd-text-page">
  <?php if ( have_posts() ) : ?>
    <h1 class="prd-h1 prd-page-title">
      <?php if ( is_archive() ) the_archive_title();
      elseif ( is_search() ) echo 'Search: ' . esc_html( get_search_query() );
      else echo 'Posts'; ?>
    </h1>
    <div class="prd-post-list">
      <?php while ( have_posts() ) : the_post(); ?>
        <article class="prd-post">
          <?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a><?php endif; ?>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="prd-post-excerpt"><?php the_excerpt(); ?></div>
        </article>
      <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(); ?>
  <?php else : ?>
    <h1 class="prd-h1 prd-page-title">Nothing found.</h1>
    <p>Try a different search or browse the shop.</p>
    <a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>" class="prd-btn prd-btn-primary">Browse the shop</a>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
