<?php
/**
 * WooCommerce Product Card Template Override
 * Controls how each product card looks in the shop grid
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) return;

$img_id  = $product->get_image_id();
$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : wc_placeholder_img_src();
$link    = $product->get_permalink();
$name    = $product->get_name();
$price   = $product->get_price_html();
$is_sale = $product->is_on_sale();
?>

<li <?php wc_product_class( '', $product ); ?>>
  <a href="<?php echo esc_url( $link ); ?>" class="prdrwndn-product-card">

    <!-- Image -->
    <div class="prdrwndn-card-img-wrap">
      <?php if ( $is_sale ) : ?>
        <span class="prdrwndn-sale-badge"><?php echo esc_html__( 'Sale!', 'prdrwndn' ); ?></span>
      <?php endif; ?>
      <img
        src="<?php echo esc_url( $img_url ); ?>"
        alt="<?php echo esc_attr( $name ); ?>"
        loading="lazy"
      />
    </div>

    <!-- Info -->
    <div class="prdrwndn-card-info">
      <h3 class="prdrwndn-card-name"><?php echo esc_html( $name ); ?></h3>
      <div class="prdrwndn-card-price"><?php echo wp_kses_post( $price ); ?></div>
    </div>

    <!-- CTA -->
    <div class="prdrwndn-card-cta">
      <?php if ( $product->is_type( 'variable' ) ) : ?>
        <span class="prdrwndn-cta-btn">Select options</span>
      <?php elseif ( $product->is_type( 'external' ) ) : ?>
        <span class="prdrwndn-cta-btn"><?php echo esc_html( $product->get_button_text() ); ?></span>
      <?php else : ?>
        <span class="prdrwndn-cta-btn">Add to cart</span>
      <?php endif; ?>
    </div>

  </a>
</li>
