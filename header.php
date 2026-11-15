<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="prd-skip" href="#prd-main">Skip to content</a>

<?php
$prd_announce = trim( (string) prd_mod( 'prd_announce' ) );
$prd_cart_n   = ( class_exists( 'WooCommerce' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
$prd_lanes    = [
    [ 'Jerseys',        prdrwndn_cat_url( 'prd_jersey_cat' ) ],
    [ 'Car Fresheners', prdrwndn_cat_url( 'prd_fresh_cat' ) ],
    [ 'Flags',          prdrwndn_cat_url( 'prd_flag_cat' ) ],
    [ 'T-Shirts',       prdrwndn_cat_url( 'prd_tee_cat' ) ],
];
$prd_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
?>

<?php if ( $prd_announce !== '' ) : ?>
<div class="prd-topbar"><?php echo esc_html( $prd_announce ); ?></div>
<?php endif; ?>

<header class="prd-header" id="prd-header">
  <div class="prd-header-in">

    <button class="prd-icon-btn prd-menu-btn" id="prd-menu-btn" aria-controls="prd-menu" aria-expanded="false" aria-label="Open menu">
      <span class="prd-i-open"><?php echo prdrwndn_icon( 'menu' ); ?></span>
      <span class="prd-i-close"><?php echo prdrwndn_icon( 'close' ); ?></span>
    </button>

    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="prd-brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> home">
      <img class="prd-brand-img" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo.svg' ); ?>" alt="Proud Rwandan PRDRWNDN®" width="59" height="44" loading="eager" fetchpriority="high" data-no-lazy="1"/>
    </a>

    <nav class="prd-nav" aria-label="Primary">
      <?php foreach ( $prd_lanes as $l ) : ?>
        <a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?></a>
      <?php endforeach; ?>
      <a href="<?php echo esc_url( $prd_shop ); ?>">Shop all</a>
      <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a>
    </nav>

    <div class="prd-header-actions">
      <?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="prd-icon-btn prd-cart-btn" aria-label="Cart, <?php echo (int) $prd_cart_n; ?> items">
          <?php echo prdrwndn_icon( 'cart' ); ?>
          <span class="prd-cart-count cart-count" <?php echo $prd_cart_n ? '' : 'hidden'; ?>><?php echo (int) $prd_cart_n; ?></span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="prd-menu" id="prd-menu" hidden>
    <nav class="prd-menu-lanes" aria-label="Shop">
      <?php foreach ( $prd_lanes as $l ) : ?>
        <a href="<?php echo esc_url( $l[1] ); ?>"><?php echo esc_html( $l[0] ); ?> <?php echo prdrwndn_icon( 'arrow' ); ?></a>
      <?php endforeach; ?>
    </nav>
    <nav class="prd-menu-links" aria-label="More">
      <a href="<?php echo esc_url( $prd_shop ); ?>">Shop all</a>
      <a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a>
      <?php if ( class_exists( 'WooCommerce' ) ) : ?><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">My account</a><?php endif; ?>
      <a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>">Get the app</a>
    </nav>
    <?php $wa = prdrwndn_whatsapp_url( 'Hi PRDRWNDN!' ); if ( $wa ) : ?>
      <a class="prd-btn prd-btn-wa prd-btn-block" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo prdrwndn_icon( 'whatsapp' ); ?><span>Message us on WhatsApp</span></a>
    <?php endif; ?>
  </div>
  <?php prdrwndn_flag_stripe( 'prd-flag-thin' ); ?>
</header>

<main id="prd-main" class="prd-page">
