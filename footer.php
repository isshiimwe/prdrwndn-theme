</div><!-- /#page-wrap -->

<?php prdrwndn_flag_stripe(); ?>

<footer id="site-footer">
  <div class="footer-inner">
    <div class="footer-grid">

      <!-- Brand -->
      <div>
        <div class="footer-brand-logo">PRDRNDN<em>®</em></div>
        <p class="footer-brand-desc">
          <?php echo esc_html( get_theme_mod( 'prdrwndn_footer_desc', 'Shop your roots. Rwanda map-shaped car fresheners, heritage apparel, and cultural pride — for the global Rwandan community.' ) ); ?>
        </p>
        <div class="footer-flag">
          <span style="background:#20AA54;"></span>
          <span style="background:#FFD700;"></span>
          <span style="background:#20A0D0;"></span>
        </div>
        <a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>" class="footer-app-link">
          📱 Get the app — free
        </a>
      </div>

      <!-- Shop -->
      <div>
        <div class="footer-col-title">Shop</div>
        <ul class="footer-links">
          <li><a href="<?php echo esc_url( home_url( '/shop/prdrwndn-car-fresheners/' ) ); ?>">Car Fresheners</a></li>
          <li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">Heritage Apparel</a></li>
          <li><a href="<?php echo esc_url( home_url( '/shop/' ) ); ?>">Flags</a></li>
          <li><a href="<?php echo esc_url( home_url( '/collection/' ) ); ?>">Full Collection</a></li>
          <li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>">Cart</a></li>
        </ul>
      </div>

      <!-- Company -->
      <div>
        <div class="footer-col-title">Company</div>
        <ul class="footer-links">
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
          <li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a></li>
          <li><a href="<?php echo esc_url( home_url( '/app.html' ) ); ?>">Get the App</a></li>
          <li><a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>">My Account</a></li>
          <li><a href="mailto:<?php echo antispambot( 'info@proudrwandan.com' ); ?>">Contact</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div>
        <div class="footer-col-title">Contact</div>
        <ul class="footer-links">
          <li><a href="mailto:<?php echo antispambot( 'info@proudrwandan.com' ); ?>">info@proudrwandan.com</a></li>
          <li><a href="tel:+14803827077">+1 (480) 382-7077</a></li>
          <li style="margin-top:0.5rem;"><a href="https://instagram.com/prdrwndn" target="_blank" rel="noopener">Instagram</a></li>
          <li><a href="https://tiktok.com/@prdrwndn" target="_blank" rel="noopener">TikTok</a></li>
          <li><a href="https://facebook.com/prdrwndn" target="_blank" rel="noopener">Facebook</a></li>
          <li><a href="https://x.com/prdrwndn" target="_blank" rel="noopener">X (Twitter)</a></li>
        </ul>
      </div>

    </div><!-- /footer-grid -->

    <div class="footer-bottom">
      <div class="footer-copy">
        &copy; <?php echo date( 'Y' ); ?> PRDRWNDN&reg; | Proud Rwandan 🇷🇼. All rights reserved.
      </div>
      <div class="footer-socials">
        <a href="https://instagram.com/prdrwndn" target="_blank" rel="noopener">Instagram</a>
        <a href="https://tiktok.com/@prdrwndn" target="_blank" rel="noopener">TikTok</a>
        <a href="https://x.com/prdrwndn" target="_blank" rel="noopener">X</a>
        <a href="https://facebook.com/prdrwndn" target="_blank" rel="noopener">Facebook</a>
      </div>
    </div>

  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
