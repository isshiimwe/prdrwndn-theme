</main><!-- /#prd-main -->

<?php
$prd_shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$prd_wa   = prdrwndn_whatsapp_url( 'Hi PRDRWNDN!' );
?>
<footer class="prd-footer">
  <?php prdrwndn_imigongo( 'prd-imigongo-footer' ); ?>
  <div class="prd-wrap">
    <div class="prd-footer-grid">

      <div class="prd-footer-brand">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="prd-footer-logo" aria-label="PRDRWNDN® home">
          <?php
          $logo_id = get_theme_mod( 'custom_logo' );
          if ( $logo_id ) :
              echo wp_get_attachment_image( $logo_id, 'medium', false, [ 'class' => 'prd-footer-logo-img', 'alt' => 'PRDRWNDN®', 'loading' => 'lazy' ] );
          else : ?>
            <span class="prd-wordmark">PRDRWNDN<sup>®</sup></span>
          <?php endif; ?>
        </a>
        <p><?php echo esc_html( prd_mod( 'prdrwndn_footer_desc' ) ); ?></p>
        <?php prdrwndn_flag_stripe( 'prd-flag-mini' ); ?>
      </div>

      <div>
        <div class="prd-footer-title">Shop</div>
        <ul class="prd-footer-links">
          <li><a href="<?php echo esc_url( prdrwndn_cat_url( 'prd_jersey_cat' ) ); ?>">Jerseys</a></li>
          <li><a href="<?php echo esc_url( prdrwndn_cat_url( 'prd_fresh_cat' ) ); ?>">Car Fresheners</a></li>
          <li><a href="<?php echo esc_url( prdrwndn_cat_url( 'prd_flag_cat' ) ); ?>">Flags</a></li>
          <li><a href="<?php echo esc_url( prdrwndn_cat_url( 'prd_tee_cat' ) ); ?>">T-Shirts</a></li>
          <li><a href="<?php echo esc_url( $prd_shop ); ?>">Shop all</a></li>
        </ul>
      </div>

      <div>
        <div class="prd-footer-title">Help</div>
        <ul class="prd-footer-links">
          <?php if ( $prd_wa ) : ?><li><a href="<?php echo esc_url( $prd_wa ); ?>" target="_blank" rel="noopener">WhatsApp us</a></li><?php endif; ?>
          <li><a href="mailto:<?php echo antispambot( 'info@proudrwandan.com' ); ?>"><?php echo antispambot( 'info@proudrwandan.com' ); ?></a></li>
          <li><a href="tel:+14803827077">+1 (480) 382-7077</a></li>
          <?php if ( class_exists( 'WooCommerce' ) ) : ?><li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">My account</a></li><?php endif; ?>
          <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a></li>
          <li><a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>">Get the app</a></li>
        </ul>
      </div>

      <div>
        <div class="prd-footer-title">New drops, first</div>
        <p class="prd-footer-small">Get an email when new designs land.</p>
        <form id="prdrwndn-subscribe-form" class="prd-subscribe">
          <label class="screen-reader-text" for="prd-sub-email">Email address</label>
          <input id="prd-sub-email" type="email" name="subscribe_email" placeholder="Your email" required autocomplete="email"/>
          <button type="submit">Subscribe</button>
        </form>
        <div id="prdrwndn-subscribe-msg" class="prd-footer-small" role="status" hidden></div>
      </div>

    </div>

    <div class="prd-footer-bottom">
      <div>&copy; <?php echo esc_html( date( 'Y' ) ); ?> PRDRWNDN&reg; · Proud Rwandan · Phoenix, AZ</div>
      <div class="prd-socials">
        <a href="https://instagram.com/prdrwndn" target="_blank" rel="noopener">Instagram</a>
        <a href="https://tiktok.com/@prdrwndn" target="_blank" rel="noopener">TikTok</a>
        <a href="https://x.com/prdrwndn" target="_blank" rel="noopener">X</a>
        <a href="https://facebook.com/prdrwndn" target="_blank" rel="noopener">Facebook</a>
      </div>
    </div>
  </div>
</footer>

<script>
(function() {
  var form = document.getElementById('prdrwndn-subscribe-form');
  if (!form) return;
  var msg = document.getElementById('prdrwndn-subscribe-msg');
  var btn = form.querySelector('button[type="submit"]');
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var email = form.subscribe_email.value.trim();
    if (!email) return;
    btn.disabled = true;
    var originalText = btn.textContent;
    btn.textContent = '…';
    var data = new URLSearchParams();
    data.append('action', 'prdrwndn_subscribe');
    data.append('email', email);
    data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'prdrwndn_subscribe' ) ); ?>');
    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
      msg.hidden = false;
      msg.textContent = res.success ? (res.data.message || "You're subscribed!") : ((res.data && res.data.message) || 'Something went wrong. Try again.');
      if (res.success) form.reset();
    })
    .catch(function() { msg.hidden = false; msg.textContent = 'Something went wrong. Try again.'; })
    .finally(function() { btn.disabled = false; btn.textContent = originalText; });
  });
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
