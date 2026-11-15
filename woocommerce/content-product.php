<?php
/**
 * Product card (shop grid, related products, homepage "Flags & more").
 */
defined( 'ABSPATH' ) || exit;

global $product;
if ( empty( $product ) || ! $product->is_visible() ) return;

$name      = $product->get_name();
$link      = $product->get_permalink();
$front     = prdrwndn_product_img( $product, 'woocommerce_thumbnail', 0 ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' );
$is_jersey = prdrwndn_is_jersey( $product->get_id() );
$back      = $is_jersey ? prdrwndn_product_img( $product, 'woocommerce_thumbnail', 1 ) : '';
$sizes     = $is_jersey ? prdrwndn_sizes( $product ) : [];
$low       = $sizes ? prdrwndn_low_stock_note( $sizes ) : '';
?>
<li <?php wc_product_class( 'prd-card-li', $product ); ?>>
  <a href="<?php echo esc_url( $link ); ?>" class="prd-card<?php echo $back ? ' has-back' : ''; ?>">
    <div class="prd-card-img">
      <img class="prd-card-front" src="<?php echo esc_url( $front ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy"/>
      <?php if ( $back ) : ?><img class="prd-card-back" src="<?php echo esc_url( $back ); ?>" alt="" loading="lazy"/><?php endif; ?>
      <span class="prd-card-badges">
        <?php if ( ! $product->is_in_stock() ) : ?>
          <span class="prd-badge prd-badge-out">Sold out</span>
        <?php elseif ( $product->is_on_sale() ) : ?>
          <span class="prd-badge prd-badge-sale">Sale</span>
        <?php endif; ?>
        <?php if ( $is_jersey && prdrwndn_deal_on() && $product->is_in_stock() ) : ?>
          <span class="prd-badge prd-badge-deal"><?php echo esc_html( prdrwndn_deal_label() ); ?></span>
        <?php endif; ?>
      </span>
    </div>
    <div class="prd-card-body">
      <h3 class="prd-card-name"><?php echo esc_html( $name ); ?></h3>
      <div class="prd-card-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
      <?php if ( $low ) : ?><p class="prd-low"><?php echo esc_html( $low ); ?></p><?php endif; ?>
    </div>
  </a>
</li>
