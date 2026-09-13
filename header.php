<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- HEADER -->
<header id="site-header">
  <div class="header-inner">

    <!-- Logo -->
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" aria-label="<?php bloginfo( 'name' ); ?>">
      <?php if ( has_custom_logo() ) :
        the_custom_logo();
      else : ?>
        <span class="logo-main">PRDRNDN<em>®</em></span>
        <span class="logo-sub">Proud Rwandan</span>
      <?php endif; ?>
    </a>

    <!-- Primary Nav -->
    <nav class="main-nav" aria-label="Primary navigation">
      <?php wp_nav_menu( [
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => '',
        'fallback_cb'    => function() {
          echo '<a href="' . home_url( '/' ) . '">Home</a>';
          echo '<a href="' . home_url( '/shop/' ) . '">Shop</a>';
          echo '<a href="' . home_url( '/about/' ) . '">About</a>';
          echo '<a href="' . home_url( '/collection/' ) . '">Collection</a>';
        },
        'items_wrap'     => '%3$s',
        'link_before'    => '',
        'link_after'     => '',
      ] ); ?>
    </nav>

    <!-- Actions -->
    <div class="header-actions">
      <a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>" class="header-app-btn">
        📲 Get the App
      </a>
      <?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-cart-btn" aria-label="Cart">
          🛒
          <?php $count = WC()->cart->get_cart_contents_count(); ?>
          <?php if ( $count > 0 ) : ?>
            <span class="cart-count"><?php echo esc_html( $count ); ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <button class="mobile-menu-btn" id="mobile-menu-toggle" aria-label="Open menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
  <!-- Flag stripe separator — sits at bottom of header like footer -->
  <div class="flag-stripe"></div>
</header>

<!-- MOBILE MENU -->
<div id="mobile-menu" role="dialog" aria-label="Mobile navigation" aria-hidden="true">
  <?php wp_nav_menu( [
    'theme_location' => 'mobile',
    'container'      => false,
    'menu_class'     => '',
    'fallback_cb'    => function() {
      $links = [
        home_url( '/' )            => 'Home',
        home_url( '/shop/' )       => 'Shop',
        home_url( '/about/' )      => 'About',
        home_url( '/collection/' ) => 'Collection',
        home_url( '/app.html' )    => 'Get the App',
      ];
      foreach ( $links as $url => $label ) {
        echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
      }
    },
    'items_wrap' => '%3$s',
  ] ); ?>
</div>

<div id="page-wrap">
