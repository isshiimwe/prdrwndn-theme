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
        </ul>

        <div class="footer-subscribe" style="margin-top:1.25rem;">
          <div class="footer-col-title" style="margin-bottom:0.5rem;">Stay Connected</div>
          <p style="font-size:0.82rem;color:var(--muted);margin-bottom:0.75rem;line-height:1.5;">Get updates on new drops and offers.</p>
          <form id="prdrwndn-subscribe-form" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <input type="email" name="subscribe_email" placeholder="Your email" required
                   style="flex:1 1 160px;min-width:0;background:var(--surface);border:1px solid var(--border);color:var(--rw-cream);border-radius:8px;padding:0.6rem 0.85rem;font-size:0.85rem;font-family:var(--font-body);" />
            <button type="submit" style="flex-shrink:0;background:var(--rw-green);color:#fff;border:none;border-radius:8px;padding:0.6rem 1.1rem;font-size:0.82rem;font-weight:700;cursor:pointer;transition:background 0.2s;">Subscribe</button>
          </form>
          <div id="prdrwndn-subscribe-msg" style="font-size:0.78rem;margin-top:0.5rem;display:none;"></div>
        </div>
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
    btn.textContent = '...';

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
      msg.style.display = 'block';
      if (res.success) {
        msg.style.color = 'var(--rw-green)';
        msg.textContent = res.data.message || "You're subscribed!";
        form.reset();
      } else {
        msg.style.color = '#ff6b6b';
        msg.textContent = (res.data && res.data.message) || 'Something went wrong. Try again.';
      }
    })
    .catch(function() {
      msg.style.display = 'block';
      msg.style.color = '#ff6b6b';
      msg.textContent = 'Something went wrong. Try again.';
    })
    .finally(function() {
      btn.disabled = false;
      btn.textContent = originalText;
    });
  });
})();
</script>

<?php wp_footer(); ?>
</body>
</html>
