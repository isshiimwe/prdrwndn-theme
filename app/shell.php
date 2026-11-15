<?php
/**
 * PRDRWNDN® app shell — served at /app/ by inc/app.php.
 * Everything inside is drawn by app/app.js from the store's live data.
 */
defined( 'ABSPATH' ) || exit;
$prd_v   = wp_get_theme( get_template() )->get( 'Version' );
$prd_dir = get_template_directory_uri();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<title>PRDRWNDN</title>
<meta name="description" content="Rwanda jerseys, map-shaped car fresheners, flags and more."/>
<meta name="theme-color" content="#F7F3EC"/>
<meta name="color-scheme" content="light only"/>
<style>/* First paint: cream, never black, launch screen visible before app.css loads. */
html,body{margin:0;background:#F7F3EC;color-scheme:light}
.launch{position:fixed;inset:0;z-index:100;background:#F7F3EC;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px}
.launch-crest{width:120px;height:auto}.launch-logo{width:150px;height:auto}</style>
<meta name="apple-mobile-web-app-capable" content="yes"/>
<meta name="mobile-web-app-capable" content="yes"/>
<meta name="apple-mobile-web-app-status-bar-style" content="default"/>
<meta name="apple-mobile-web-app-title" content="PRDRWNDN"/>
<link rel="manifest" href="<?php echo esc_url( home_url( '/manifest.json' ) ); ?>"/>
<link rel="apple-touch-icon" href="<?php echo esc_url( $prd_dir . '/assets/img/apple-touch-icon.png' ); ?>"/>
<link rel="icon" href="<?php echo esc_url( $prd_dir . '/assets/img/app-icon-192.png' ); ?>"/>
<?php prdrwndn_app_splash_links(); ?>
<link rel="preload" href="<?php echo esc_url( $prd_dir . '/assets/fonts/bebas-neue-latin-400-normal.woff2' ); ?>" as="font" type="font/woff2" crossorigin/>
<link rel="preload" href="<?php echo esc_url( $prd_dir . '/assets/fonts/inter-latin-wght-normal.woff2' ); ?>" as="font" type="font/woff2" crossorigin/>
<link rel="stylesheet" href="<?php echo esc_url( $prd_dir . '/app/app.css?v=' . $prd_v ); ?>"/>
<script>window.PRD_APP = <?php echo wp_json_encode( prdrwndn_app_config() ); ?>;</script>
</head>
<body>
<div id="launch" class="launch" aria-hidden="true">
  <img class="launch-crest" src="<?php echo esc_url( $prd_dir . '/assets/img/crest.svg' ); ?>" alt="" width="120" height="96" data-no-lazy="1"/>
  <img class="launch-logo" src="<?php echo esc_url( $prd_dir . '/assets/img/logo.svg' ); ?>" alt="" width="150" height="112" data-no-lazy="1"/>
  <span class="launch-stripe"></span>
</div>

<header class="bar" id="bar">
  <button class="bar-btn" id="bar-back" type="button" aria-label="Back" hidden><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>
  <img class="bar-logo" id="bar-logo" src="<?php echo esc_url( $prd_dir . '/assets/img/logo.svg' ); ?>" alt="Proud Rwandan" width="54" height="40" data-no-lazy="1"/>
  <h1 class="bar-title" id="bar-title" hidden></h1>
  <span class="bar-spacer"></span>
  <a class="bar-btn" href="#wishlist" aria-label="Wishlist"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg><span class="dot" id="wish-dot" hidden></span></a>
</header>

<main id="views">
  <section class="view" id="view-home" data-tab="home"></section>
  <section class="view" id="view-shop" data-tab="shop"></section>
  <section class="view" id="view-cart" data-tab="cart"></section>
  <section class="view" id="view-account" data-tab="account"></section>
  <section class="view" id="view-sub" data-tab=""></section>
</main>

<nav class="tabs" aria-label="App">
  <a href="#home" data-tab="home"><svg viewBox="0 0 24 24"><path d="M4 11l8-7 8 7v8a1 1 0 0 1-1 1h-4v-6h-6v6H5a1 1 0 0 1-1-1z"/></svg><span>Home</span></a>
  <a href="#shop" data-tab="shop"><svg viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg><span>Shop</span></a>
  <a href="#cart" data-tab="cart"><svg viewBox="0 0 24 24"><path d="M3 4h2l2.4 11h10.2L20 8H7"/><circle cx="9" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/></svg><span>Cart</span><b class="badge" id="cart-badge" hidden>0</b></a>
  <a href="#account" data-tab="account"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Account</span></a>
</nav>

<div class="sheet" id="sheet" hidden>
  <div class="sheet-backdrop" data-close-sheet></div>
  <div class="sheet-panel" id="sheet-panel" role="dialog" aria-modal="true"></div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>

<script src="<?php echo esc_url( $prd_dir . '/app/app.js?v=' . $prd_v ); ?>" defer></script>
</body>
</html>
