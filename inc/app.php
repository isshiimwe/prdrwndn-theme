<?php
/**
 * PRDRWNDN® app — the home-screen app at /app/.
 *
 *  1. Route          /app/ serves app/shell.php (the whole app is one page)
 *  2. Config         what the app needs to know (categories, deal, contact)
 *  3. Session        who is signed in (admin-ajax, works for guests too)
 *  4. REST API       /wp-json/prdrwndn/v1/…  catalog, login, orders, wishlist, passkeys
 *  5. Passkeys       Face ID / Touch ID sign-in (WebAuthn), verified here
 *  6. Tracking       tracking number box on orders, shown in the app, account and emails
 *  7. App mode       checkout opened from the app: slim app bar instead of the site header
 *
 * Products and the cart use WooCommerce's own Store API, so the app's cart is
 * the website's cart and the 2-for-$80 deal applies the same way.
 * Payment happens on the site's checkout (WooPayments, Apple Pay / Google Pay).
 */

defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════════════════
   1. ROUTE
════════════════════════════════════════════════════ */
function prdrwndn_app_path() {
    $path = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base !== '/' && strpos( $path, $base ) === 0 ) $path = '/' . substr( $path, strlen( $base ) );
    return trim( $path, '/' );
}

add_action( 'template_redirect', function () {
    if ( 'app' !== prdrwndn_app_path() ) return;
    global $wp_query;
    if ( $wp_query ) { $wp_query->is_404 = false; }
    status_header( 200 );
    header( 'X-Robots-Tag: noindex' );
    // The app ships its own small CSS/JS; LiteSpeed must not combine, minify or lazy-load it.
    if ( ! defined( 'LITESPEED_NO_OPTM' ) ) define( 'LITESPEED_NO_OPTM', true );
    if ( ! defined( 'LITESPEED_NO_LAZY' ) ) define( 'LITESPEED_NO_LAZY', true );
    do_action( 'litespeed_control_set_nocache', 'PRDRWNDN app shell' );
    include get_template_directory() . '/app/shell.php';
    exit;
}, 0 );

/* ════════════════════════════════════════════════════
   2. CONFIG
════════════════════════════════════════════════════ */
function prdrwndn_app_config() {
    $cats = [];
    // key = what the app's links use (#shop/jerseys, #shop/fresh …)
    foreach ( [ 'jerseys' => [ 'prd_jersey_cat', 'Jerseys' ], 'fresh' => [ 'prd_fresh_cat', 'Car fresheners' ], 'flags' => [ 'prd_flag_cat', 'Flags' ], 'tees' => [ 'prd_tee_cat', 'T-shirts' ] ] as $key => $c ) {
        $t = prdrwndn_cat( $c[0] );
        if ( $t ) $cats[] = [ 'key' => $key, 'slug' => $t->slug, 'name' => $c[1] ];
    }
    $wc = class_exists( 'WooCommerce' );
    $account = $wc ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
    return [
        'version'   => wp_get_theme( get_template() )->get( 'Version' ),
        'home'      => home_url( '/' ),
        'appUrl'    => home_url( '/app/' ),
        'ajax'      => admin_url( 'admin-ajax.php' ),
        'rest'      => esc_url_raw( rest_url( 'prdrwndn/v1/' ) ),
        'store'     => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
        'checkout'  => $wc ? wc_get_checkout_url() : home_url( '/checkout/' ),
        'account'   => $account,
        'lostPass'  => $wc ? wc_lostpassword_url() : wp_lostpassword_url(),
        'privacy'   => get_privacy_policy_url(),
        'canRegister' => $wc && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ),
        'cats'      => $cats,
        'deal'      => [ 'on' => prdrwndn_deal_on(), 'price' => (int) prd_mod( 'prd_deal_price' ), 'label' => prdrwndn_deal_label() ],
        'pickup'    => (bool) prd_mod( 'prd_pickup_on' ) ? (string) prd_mod( 'prd_pickup_text' ) : '',
        'whatsapp'  => prdrwndn_whatsapp_number(),
        'email'     => 'info@proudrwandan.com',
        'phone'     => '+14803827077',
        'sizeChart' => prdrwndn_app_size_chart(),
        'hero'      => [
            'eyebrow' => (string) prd_mod( 'prd_hero_eyebrow' ),
            'title'   => (string) prd_mod( 'prd_hero_title' ),
        ],
        'img'       => get_template_directory_uri() . '/assets/img/',
        'currency'  => $wc ? html_entity_decode( get_woocommerce_currency_symbol() ) : '$',
    ];
}

function prdrwndn_app_size_chart() {
    $raw  = trim( (string) prd_mod( 'prd_size_chart' ) );
    $rows = [];
    if ( $raw === '' ) return $rows;
    foreach ( preg_split( '/\r?\n/', $raw ) as $line ) {
        if ( trim( $line ) !== '' ) $rows[] = array_map( 'trim', explode( '|', $line ) );
    }
    return $rows;
}

/**
 * iPhone launch pictures (apple-touch-startup-image). iOS shows these while the
 * home-screen app starts, instead of a blank black/white screen. They match the
 * app's own launch screen, so the two read as one. Files: assets/img/splash/.
 */
function prdrwndn_app_splash_links() {
    $base = get_template_directory_uri() . '/assets/img/splash/';
    $sizes = [ [ 440, 956, 3 ], [ 430, 932, 3 ], [ 420, 912, 3 ], [ 402, 874, 3 ], [ 393, 852, 3 ], [ 390, 844, 3 ], [ 428, 926, 3 ], [ 375, 812, 3 ], [ 414, 896, 3 ], [ 414, 896, 2 ], [ 414, 736, 3 ], [ 375, 667, 2 ] ];
    foreach ( $sizes as $z ) {
        printf(
            '<link rel="apple-touch-startup-image" media="screen and (device-width: %1$dpx) and (device-height: %2$dpx) and (-webkit-device-pixel-ratio: %3$d) and (orientation: portrait)" href="%4$s"/>' . "\n",
            $z[0], $z[1], $z[2], esc_url( $base . sprintf( 'splash-%dx%d@%dx.png', $z[0], $z[1], $z[2] ) )
        );
    }
}

/* ════════════════════════════════════════════════════
   3. SESSION — who is signed in
════════════════════════════════════════════════════ */
function prdrwndn_app_session_data() {
    $u = wp_get_current_user();
    $version = wp_get_theme( get_template() )->get( 'Version' );
    if ( ! $u || ! $u->ID ) {
        return [ 'user' => null, 'nonce' => '', 'passkeys' => 0, 'wishlist' => [], 'version' => $version ];
    }
    $first = trim( (string) $u->first_name );
    $name  = $first !== '' ? $first : $u->display_name;
    $parts = preg_split( '/\s+/', trim( $u->first_name . ' ' . $u->last_name ) ?: $u->display_name );
    $init  = strtoupper( mb_substr( $parts[0] ?? '', 0, 1 ) . mb_substr( $parts[1] ?? '', 0, 1 ) );
    return [
        'user'     => [ 'id' => $u->ID, 'name' => $name, 'email' => $u->user_email, 'initials' => $init ?: 'P' ],
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'passkeys' => count( prdrwndn_passkeys( $u->ID ) ),
        'wishlist' => prdrwndn_wishlist( $u->ID ),
        'version'  => $version,
    ];
}

function prdrwndn_app_session_ajax() {
    nocache_headers();
    wp_send_json( prdrwndn_app_session_data() );
}
add_action( 'wp_ajax_prdrwndn_app_session', 'prdrwndn_app_session_ajax' );
add_action( 'wp_ajax_nopriv_prdrwndn_app_session', 'prdrwndn_app_session_ajax' );

/**
 * Sign a user in during this request and make nonces created afterwards
 * valid for the cookies the browser is about to receive.
 */
function prdrwndn_app_sign_in( $user_id ) {
    add_action( 'set_logged_in_cookie', function ( $cookie ) { $_COOKIE[ LOGGED_IN_COOKIE ] = $cookie; } );
    wp_set_auth_cookie( $user_id, true, is_ssl() );
    wp_set_current_user( $user_id );
    do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );
    return prdrwndn_app_session_data();
}

/** Simple per-IP limit for sign-in attempts. */
function prdrwndn_app_throttle( $bucket, $max = 10, $window = 900 ) {
    $ip  = preg_replace( '/[^0-9a-f:.]/i', '', $_SERVER['REMOTE_ADDR'] ?? '' );
    $key = 'prd_thr_' . $bucket . '_' . md5( $ip );
    $n   = (int) get_transient( $key );
    if ( $n >= $max ) return false;
    set_transient( $key, $n + 1, $window );
    return true;
}

/* ════════════════════════════════════════════════════
   4. REST API
════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function () {
    $ns     = 'prdrwndn/v1';
    $public = '__return_true';
    $signed = function () { return is_user_logged_in(); };

    register_rest_route( $ns, '/catalog', [ 'methods' => 'GET', 'permission_callback' => $public, 'callback' => 'prdrwndn_app_catalog' ] );
    register_rest_route( $ns, '/login', [ 'methods' => 'POST', 'permission_callback' => $public, 'callback' => 'prdrwndn_app_rest_login' ] );
    register_rest_route( $ns, '/logout', [ 'methods' => 'POST', 'permission_callback' => $signed, 'callback' => function () {
        wp_logout();
        return [ 'ok' => true ];
    } ] );
    register_rest_route( $ns, '/orders', [ 'methods' => 'GET', 'permission_callback' => $signed, 'callback' => 'prdrwndn_app_orders' ] );
    register_rest_route( $ns, '/orders/(?P<id>\d+)', [ 'methods' => 'GET', 'permission_callback' => $signed, 'callback' => 'prdrwndn_app_order' ] );
    register_rest_route( $ns, '/wishlist', [
        [ 'methods' => 'GET', 'permission_callback' => $signed, 'callback' => function () { return prdrwndn_wishlist( get_current_user_id() ); } ],
        [ 'methods' => 'POST', 'permission_callback' => $signed, 'callback' => function ( WP_REST_Request $r ) {
            $ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $r->get_param( 'ids' ) ) ) ) );
            $ids = array_slice( array_values( array_filter( $ids, function ( $id ) { return 'product' === get_post_type( $id ); } ) ), 0, 200 );
            update_user_meta( get_current_user_id(), '_prd_wishlist', $ids );
            return $ids;
        } ],
    ] );
    register_rest_route( $ns, '/passkey/register-options', [ 'methods' => 'POST', 'permission_callback' => $signed, 'callback' => 'prdrwndn_passkey_register_options' ] );
    register_rest_route( $ns, '/passkey/register', [ 'methods' => 'POST', 'permission_callback' => $signed, 'callback' => 'prdrwndn_passkey_register' ] );
    register_rest_route( $ns, '/passkey/login-options', [ 'methods' => 'POST', 'permission_callback' => $public, 'callback' => 'prdrwndn_passkey_login_options' ] );
    register_rest_route( $ns, '/passkey/login', [ 'methods' => 'POST', 'permission_callback' => $public, 'callback' => 'prdrwndn_passkey_login' ] );
    register_rest_route( $ns, '/passkeys', [ 'methods' => 'DELETE', 'permission_callback' => $signed, 'callback' => function () {
        delete_user_meta( get_current_user_id(), '_prd_passkeys' );
        return [ 'ok' => true ];
    } ] );
} );

/* ── Catalog: every visible product with sizes/scents and stock, in one request ── */
function prdrwndn_app_catalog() {
    if ( ! class_exists( 'WooCommerce' ) ) return [];
    $products = wc_get_products( [ 'status' => 'publish', 'visibility' => 'catalog', 'limit' => 200, 'orderby' => [ 'menu_order' => 'ASC', 'date' => 'DESC' ] ] );
    $out = [];
    foreach ( $products as $p ) $out[] = prdrwndn_app_product( $p );
    // Stock must be current: keep LiteSpeed and the CDN from caching this.
    do_action( 'litespeed_control_set_nocache', 'PRDRWNDN app catalog' );
    $res = rest_ensure_response( $out );
    $res->header( 'Cache-Control', 'no-store, max-age=0' );
    return $res;
}

function prdrwndn_app_img( $id, $size ) {
    $u = $id ? wp_get_attachment_image_url( $id, $size ) : '';
    return $u ? $u : '';
}

function prdrwndn_app_product( WC_Product $p ) {
    $ids    = array_values( array_filter( array_merge( [ $p->get_image_id() ], $p->get_gallery_image_ids() ) ) );
    $images = [];
    foreach ( $ids as $iid ) {
        $images[] = [ 'src' => prdrwndn_app_img( $iid, 'woocommerce_single' ), 'thumb' => prdrwndn_app_img( $iid, 'woocommerce_thumbnail' ), 'alt' => (string) get_post_meta( $iid, '_wp_attachment_image_alt', true ) ];
    }
    $cats = wp_get_post_terms( $p->get_id(), 'product_cat', [ 'fields' => 'slugs' ] );
    $allowed = [ 'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'ul' => [], 'ol' => [], 'li' => [], 'h3' => [], 'h4' => [] ];
    $desc = wp_kses( wpautop( strip_shortcodes( $p->get_description() ) ), $allowed );

    $item = [
        'id'       => $p->get_id(),
        'slug'     => $p->get_slug(),
        'name'     => html_entity_decode( $p->get_name() ),
        'type'     => $p->get_type(),
        'url'      => get_permalink( $p->get_id() ),
        'cats'     => is_wp_error( $cats ) ? [] : $cats,
        'jersey'   => prdrwndn_is_jersey( $p->get_id() ),
        'price'    => (float) $p->get_price(),
        'regular'  => (float) $p->get_regular_price(),
        'onSale'   => $p->is_on_sale(),
        'inStock'  => $p->is_in_stock(),
        'stock'    => $p->managing_stock() ? (int) $p->get_stock_quantity() : null,
        'short'    => wp_strip_all_tags( html_entity_decode( $p->get_short_description() ) ),
        'desc'     => $desc,
        'images'   => $images,
        'options'  => [],
        'variants' => [],
    ];

    if ( $p->is_type( 'variable' ) ) {
        $prices = $p->get_variation_prices( true );
        $item['price']   = (float) ( $prices['price'] ? min( $prices['price'] ) : 0 );
        $item['priceMax'] = (float) ( $prices['price'] ? max( $prices['price'] ) : 0 );
        $labels = [];
        foreach ( $p->get_variation_attributes() as $attr => $values ) {
            $tax    = taxonomy_exists( $attr );
            $opts   = [];
            if ( $tax ) {
                foreach ( wc_get_product_terms( $p->get_id(), $attr, [ 'fields' => 'all' ] ) as $term ) {
                    if ( in_array( $term->slug, $values, true ) ) { $opts[] = [ 'value' => $term->slug, 'label' => $term->name ]; $labels[ $attr ][ $term->slug ] = $term->name; }
                }
            } else {
                foreach ( $values as $v ) { $opts[] = [ 'value' => $v, 'label' => $v ]; $labels[ $attr ][ $v ] = $v; }
            }
            $item['options'][] = [ 'key' => 'attribute_' . sanitize_title( $attr ), 'name' => wc_attribute_label( $attr, $p ), 'values' => $opts ];
        }
        foreach ( $p->get_children() as $vid ) {
            $v = wc_get_product( $vid );
            if ( ! $v || 'publish' !== $v->get_status() ) continue;
            $item['variants'][] = [
                'id'      => $v->get_id(),
                'attrs'   => $v->get_variation_attributes(),
                'price'   => (float) $v->get_price(),
                'inStock' => $v->is_in_stock(),
                'stock'   => $v->managing_stock() ? (int) $v->get_stock_quantity() : null,
                'image'   => $v->get_image_id() && $v->get_image_id() !== $p->get_image_id() ? prdrwndn_app_img( $v->get_image_id(), 'woocommerce_single' ) : '',
            ];
        }
    }
    return $item;
}

/* ── Password sign-in ── */
function prdrwndn_app_rest_login( WP_REST_Request $r ) {
    if ( ! prdrwndn_app_throttle( 'login' ) ) {
        return new WP_Error( 'prd_slow_down', 'Too many tries. Wait a few minutes, then try again.', [ 'status' => 429 ] );
    }
    $login = trim( (string) $r->get_param( 'login' ) );
    $pass  = (string) $r->get_param( 'password' );
    if ( $login === '' || $pass === '' ) {
        return new WP_Error( 'prd_missing', 'Enter your email and password.', [ 'status' => 400 ] );
    }
    if ( is_email( $login ) ) {
        $u = get_user_by( 'email', $login );
        if ( $u ) $login = $u->user_login;
    }
    $user = wp_authenticate( $login, $pass );
    if ( is_wp_error( $user ) ) {
        return new WP_Error( 'prd_bad_login', 'That email and password don’t match an account.', [ 'status' => 401 ] );
    }
    return prdrwndn_app_sign_in( $user->ID );
}

/* ── Orders ── */
function prdrwndn_app_status_label( $status ) {
    $map = [ 'pending' => 'Awaiting payment', 'on-hold' => 'On hold', 'processing' => 'Preparing', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded', 'failed' => 'Payment failed' ];
    return $map[ $status ] ?? wc_get_order_status_name( $status );
}

function prdrwndn_app_orders() {
    $orders = wc_get_orders( [ 'customer_id' => get_current_user_id(), 'limit' => 50, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ] );
    $out = [];
    foreach ( $orders as $o ) {
        $first = null; $count = 0;
        foreach ( $o->get_items() as $it ) {
            $count += $it->get_quantity();
            if ( ! $first ) $first = $it;
        }
        $img = '';
        if ( $first && ( $prod = $first->get_product() ) ) $img = prdrwndn_app_img( $prod->get_image_id() ?: ( $prod->get_parent_id() ? wc_get_product( $prod->get_parent_id() )->get_image_id() : 0 ), 'woocommerce_thumbnail' );
        $out[] = [
            'id'     => $o->get_id(),
            'number' => $o->get_order_number(),
            'date'   => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'M j, Y' ) : '',
            'status' => $o->get_status(),
            'label'  => prdrwndn_app_status_label( $o->get_status() ),
            'total'  => html_entity_decode( wp_strip_all_tags( wc_price( $o->get_total(), [ 'currency' => $o->get_currency() ] ) ) ),
            'items'  => $count,
            'image'  => $img,
            'tracking' => (bool) prdrwndn_tracking( $o ),
        ];
    }
    return $out;
}

function prdrwndn_app_order( WP_REST_Request $r ) {
    $o = wc_get_order( (int) $r['id'] );
    if ( ! $o || (int) $o->get_customer_id() !== get_current_user_id() ) {
        return new WP_Error( 'prd_not_found', 'Order not found.', [ 'status' => 404 ] );
    }
    $money = function ( $v ) use ( $o ) { return html_entity_decode( wp_strip_all_tags( wc_price( $v, [ 'currency' => $o->get_currency() ] ) ) ); };
    $items = [];
    foreach ( $o->get_items() as $it ) {
        $prod = $it->get_product();
        $img  = '';
        if ( $prod ) {
            $iid = $prod->get_image_id();
            if ( ! $iid && $prod->get_parent_id() ) { $par = wc_get_product( $prod->get_parent_id() ); $iid = $par ? $par->get_image_id() : 0; }
            $img = prdrwndn_app_img( $iid, 'woocommerce_thumbnail' );
        }
        $meta = [];
        foreach ( $it->get_formatted_meta_data( '_', true ) as $m ) $meta[] = wp_strip_all_tags( $m->display_key . ': ' . $m->display_value );
        $name = $it->get_name();
        if ( $it->get_variation_id() && ( $parent = wc_get_product( $it->get_product_id() ) ) ) $name = $parent->get_name();
        $items[] = [ 'name' => html_entity_decode( $name ), 'qty' => $it->get_quantity(), 'total' => $money( $it->get_total() ), 'image' => $img, 'meta' => $meta ];
    }
    $totals = [];
    foreach ( $o->get_order_item_totals() as $row ) {
        $totals[] = [ 'label' => rtrim( wp_strip_all_tags( $row['label'] ), ':' ), 'value' => html_entity_decode( wp_strip_all_tags( $row['value'] ) ) ];
    }
    $status = $o->get_status();
    $track  = prdrwndn_tracking( $o );
    $is_pickup = false;
    foreach ( $o->get_shipping_methods() as $sm ) { if ( false !== strpos( $sm->get_method_id(), 'pickup' ) ) $is_pickup = true; }
    $step = in_array( $status, [ 'completed' ], true ) ? 3 : ( $track ? 2 : ( in_array( $status, [ 'processing', 'on-hold' ], true ) ? 1 : 0 ) );
    $steps = $is_pickup ? [ 'Order placed', 'Preparing', 'Ready for pickup', 'Picked up' ] : [ 'Order placed', 'Preparing', 'Shipped', 'Delivered' ];
    return [
        'id'       => $o->get_id(),
        'number'   => $o->get_order_number(),
        'date'     => $o->get_date_created() ? $o->get_date_created()->date_i18n( 'M j, Y' ) : '',
        'status'   => $status,
        'label'    => prdrwndn_app_status_label( $status ),
        'items'    => $items,
        'totals'   => $totals,
        'address'  => trim( preg_replace( '/\s*,\s*/', ', ', wp_strip_all_tags( str_replace( [ '<br/>', '<br>' ], ', ', $o->get_formatted_shipping_address() ?: $o->get_formatted_billing_address() ) ) ), ', ' ),
        'pickup'   => $is_pickup,
        'tracking' => $track,
        'steps'    => $steps,
        'step'     => in_array( $status, [ 'cancelled', 'refunded', 'failed', 'pending' ], true ) ? -1 : $step,
        'payUrl'   => 'pending' === $status ? $o->get_checkout_payment_url() : '',
        'viewUrl'  => $o->get_view_order_url(),
    ];
}

/* ── Wishlist ── */
function prdrwndn_wishlist( $user_id ) {
    $ids = get_user_meta( $user_id, '_prd_wishlist', true );
    return is_array( $ids ) ? array_values( array_map( 'intval', $ids ) ) : [];
}

/* ════════════════════════════════════════════════════
   5. PASSKEYS — Face ID / Touch ID (WebAuthn)
   Credentials are stored per user in user meta "_prd_passkeys":
   [ { id (base64url), pk (PEM public key), count, created, name } ]
════════════════════════════════════════════════════ */
function prdrwndn_b64u_enc( $bin ) { return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' ); }
function prdrwndn_b64u_dec( $s ) { $s = strtr( (string) $s, '-_', '+/' ); return (string) base64_decode( $s . str_repeat( '=', ( 4 - strlen( $s ) % 4 ) % 4 ) ); }

function prdrwndn_passkeys( $user_id ) {
    $list = get_user_meta( $user_id, '_prd_passkeys', true );
    return is_array( $list ) ? $list : [];
}
function prdrwndn_rp_id() { return (string) wp_parse_url( home_url(), PHP_URL_HOST ); }
function prdrwndn_rp_origin() {
    $p = wp_parse_url( home_url() );
    return $p['scheme'] . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
}
/** Stable, non-guessable user handle stored on the passkey. */
function prdrwndn_user_handle( $user_id ) {
    $h = get_user_meta( $user_id, '_prd_passkey_handle', true );
    if ( ! $h ) { $h = prdrwndn_b64u_enc( random_bytes( 32 ) ); update_user_meta( $user_id, '_prd_passkey_handle', $h ); }
    return $h;
}

function prdrwndn_passkey_register_options() {
    $u = wp_get_current_user();
    $challenge = random_bytes( 32 );
    set_transient( 'prd_pk_reg_' . $u->ID, prdrwndn_b64u_enc( $challenge ), 300 );
    $exclude = [];
    foreach ( prdrwndn_passkeys( $u->ID ) as $k ) $exclude[] = [ 'type' => 'public-key', 'id' => $k['id'] ];
    return [
        'challenge' => prdrwndn_b64u_enc( $challenge ),
        'rp'        => [ 'name' => 'PRDRWNDN', 'id' => prdrwndn_rp_id() ],
        'user'      => [ 'id' => prdrwndn_user_handle( $u->ID ), 'name' => $u->user_email ?: $u->user_login, 'displayName' => $u->display_name ],
        'pubKeyCredParams' => [ [ 'type' => 'public-key', 'alg' => -7 ], [ 'type' => 'public-key', 'alg' => -257 ] ],
        'authenticatorSelection' => [ 'residentKey' => 'required', 'requireResidentKey' => true, 'userVerification' => 'required' ],
        'attestation' => 'none',
        'timeout'   => 60000,
        'excludeCredentials' => $exclude,
    ];
}

function prdrwndn_passkey_register( WP_REST_Request $r ) {
    $uid       = get_current_user_id();
    $challenge = get_transient( 'prd_pk_reg_' . $uid );
    delete_transient( 'prd_pk_reg_' . $uid );
    if ( ! $challenge ) return new WP_Error( 'prd_pk_expired', 'That took too long. Try again.', [ 'status' => 400 ] );

    $client = json_decode( prdrwndn_b64u_dec( $r->get_param( 'clientDataJSON' ) ), true );
    if ( ! is_array( $client ) || ( $client['type'] ?? '' ) !== 'webauthn.create' || ! hash_equals( $challenge, (string) ( $client['challenge'] ?? '' ) ) || ( $client['origin'] ?? '' ) !== prdrwndn_rp_origin() ) {
        return new WP_Error( 'prd_pk_bad', 'Face ID setup could not be verified.', [ 'status' => 400 ] );
    }
    try {
        $att  = prdrwndn_cbor_decode( prdrwndn_b64u_dec( $r->get_param( 'attestationObject' ) ) );
        $auth = prdrwndn_parse_auth_data( (string) ( $att['authData'] ?? '' ), true );
    } catch ( Exception $e ) {
        return new WP_Error( 'prd_pk_bad', 'Face ID setup could not be read.', [ 'status' => 400 ] );
    }
    if ( ! hash_equals( hash( 'sha256', prdrwndn_rp_id(), true ), $auth['rpIdHash'] ) || ! ( $auth['flags'] & 0x01 ) || ! ( $auth['flags'] & 0x04 ) || empty( $auth['pem'] ) ) {
        return new WP_Error( 'prd_pk_bad', 'Face ID setup could not be verified.', [ 'status' => 400 ] );
    }
    $list   = prdrwndn_passkeys( $uid );
    $list[] = [ 'id' => prdrwndn_b64u_enc( $auth['credId'] ), 'pk' => $auth['pem'], 'count' => $auth['signCount'], 'created' => time(), 'name' => substr( sanitize_text_field( (string) $r->get_param( 'device' ) ), 0, 60 ) ];
    update_user_meta( $uid, '_prd_passkeys', array_slice( $list, -10 ) );
    return [ 'ok' => true, 'passkeys' => count( $list ) ];
}

function prdrwndn_passkey_login_options() {
    if ( ! prdrwndn_app_throttle( 'pkopt', 30 ) ) return new WP_Error( 'prd_slow_down', 'Too many tries. Wait a few minutes.', [ 'status' => 429 ] );
    $challenge = random_bytes( 32 );
    $token     = prdrwndn_b64u_enc( random_bytes( 18 ) );
    set_transient( 'prd_pk_login_' . $token, prdrwndn_b64u_enc( $challenge ), 300 );
    return [ 'token' => $token, 'options' => [ 'challenge' => prdrwndn_b64u_enc( $challenge ), 'rpId' => prdrwndn_rp_id(), 'userVerification' => 'required', 'timeout' => 60000, 'allowCredentials' => [] ] ];
}

function prdrwndn_passkey_login( WP_REST_Request $r ) {
    if ( ! prdrwndn_app_throttle( 'pklogin', 20 ) ) return new WP_Error( 'prd_slow_down', 'Too many tries. Wait a few minutes.', [ 'status' => 429 ] );
    $fail  = new WP_Error( 'prd_pk_fail', 'Face ID sign-in didn’t work. Sign in with your password instead.', [ 'status' => 401 ] );
    $token = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $r->get_param( 'token' ) );
    $challenge = $token ? get_transient( 'prd_pk_login_' . $token ) : false;
    if ( $token ) delete_transient( 'prd_pk_login_' . $token );
    if ( ! $challenge ) return $fail;

    $client_raw = prdrwndn_b64u_dec( $r->get_param( 'clientDataJSON' ) );
    $client     = json_decode( $client_raw, true );
    if ( ! is_array( $client ) || ( $client['type'] ?? '' ) !== 'webauthn.get' || ! hash_equals( $challenge, (string) ( $client['challenge'] ?? '' ) ) || ( $client['origin'] ?? '' ) !== prdrwndn_rp_origin() ) return $fail;

    // Find the user by the handle stored on the passkey.
    // The browser returns the handle we set at setup (base64url, compared as stored).
    $handle = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $r->get_param( 'userHandle' ) );
    $users  = $handle !== '' ? get_users( [ 'meta_key' => '_prd_passkey_handle', 'meta_value' => $handle, 'number' => 1, 'fields' => 'ID' ] ) : [];
    if ( ! $users ) return $fail;
    $uid  = (int) $users[0];
    $cid  = (string) $r->get_param( 'id' );
    $list = prdrwndn_passkeys( $uid );
    $idx  = null;
    foreach ( $list as $i => $k ) { if ( hash_equals( $k['id'], $cid ) ) { $idx = $i; break; } }
    if ( null === $idx ) return $fail;

    $auth_raw = prdrwndn_b64u_dec( $r->get_param( 'authenticatorData' ) );
    try { $auth = prdrwndn_parse_auth_data( $auth_raw, false ); } catch ( Exception $e ) { return $fail; }
    if ( ! hash_equals( hash( 'sha256', prdrwndn_rp_id(), true ), $auth['rpIdHash'] ) || ! ( $auth['flags'] & 0x01 ) || ! ( $auth['flags'] & 0x04 ) ) return $fail;

    $sig = prdrwndn_b64u_dec( $r->get_param( 'signature' ) );
    $ok  = openssl_verify( $auth_raw . hash( 'sha256', $client_raw, true ), $sig, $list[ $idx ]['pk'], OPENSSL_ALGO_SHA256 );
    if ( 1 !== $ok ) return $fail;
    if ( $auth['signCount'] > 0 && $auth['signCount'] <= (int) $list[ $idx ]['count'] ) return $fail; // cloned key guard
    $list[ $idx ]['count'] = $auth['signCount'];
    update_user_meta( $uid, '_prd_passkeys', $list );
    return prdrwndn_app_sign_in( $uid );
}

/** Authenticator data → rpIdHash, flags, signCount and (on setup) credential id + PEM key. */
function prdrwndn_parse_auth_data( $bin, $with_cred ) {
    if ( strlen( $bin ) < 37 ) throw new Exception( 'short' );
    $out = [ 'rpIdHash' => substr( $bin, 0, 32 ), 'flags' => ord( $bin[32] ), 'signCount' => unpack( 'N', substr( $bin, 33, 4 ) )[1] ];
    if ( $with_cred ) {
        if ( ! ( $out['flags'] & 0x40 ) || strlen( $bin ) < 55 ) throw new Exception( 'no cred' );
        $len = unpack( 'n', substr( $bin, 53, 2 ) )[1];
        $out['credId'] = substr( $bin, 55, $len );
        $off = 55 + $len;
        $cose = prdrwndn_cbor_decode( substr( $bin, $off ) );
        $out['pem'] = prdrwndn_cose_to_pem( $cose );
    }
    return $out;
}

/** COSE public key (ES256 or RS256) → PEM. */
function prdrwndn_cose_to_pem( $k ) {
    $kty = $k[1] ?? null;
    if ( 2 === $kty && isset( $k[-2], $k[-3] ) && strlen( $k[-2] ) === 32 && strlen( $k[-3] ) === 32 ) {
        $der = hex2bin( '3059301306072a8648ce3d020106082a8648ce3d030107034200' ) . "\x04" . $k[-2] . $k[-3];
    } elseif ( 3 === $kty && isset( $k[-1], $k[-2] ) ) {
        $int = function ( $b ) { if ( ord( $b[0] ) > 0x7f ) $b = "\x00" . $b; return "\x02" . prdrwndn_der_len( strlen( $b ) ) . $b; };
        $seq = function ( $b ) { return "\x30" . prdrwndn_der_len( strlen( $b ) ) . $b; };
        $rsa = $seq( $int( $k[-1] ) . $int( $k[-2] ) );
        $bit = "\x03" . prdrwndn_der_len( strlen( $rsa ) + 1 ) . "\x00" . $rsa;
        $der = $seq( hex2bin( '300d06092a864886f70d0101010500' ) . $bit );
    } else {
        return '';
    }
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $der ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
}
function prdrwndn_der_len( $n ) {
    if ( $n < 128 ) return chr( $n );
    $b = ltrim( pack( 'N', $n ), "\x00" );
    return chr( 0x80 | strlen( $b ) ) . $b;
}

/** Minimal CBOR decoder (enough for WebAuthn): decodes the first item. */
function prdrwndn_cbor_decode( $bin ) {
    $pos = 0;
    return prdrwndn_cbor_item( $bin, $pos );
}
function prdrwndn_cbor_item( $b, &$p ) {
    if ( $p >= strlen( $b ) ) throw new Exception( 'eof' );
    $ib = ord( $b[ $p++ ] ); $mt = $ib >> 5; $ai = $ib & 0x1f;
    if ( $ai < 24 ) { $len = $ai; }
    elseif ( $ai === 24 ) { $len = ord( $b[ $p ] ); $p += 1; }
    elseif ( $ai === 25 ) { $len = unpack( 'n', substr( $b, $p, 2 ) )[1]; $p += 2; }
    elseif ( $ai === 26 ) { $len = unpack( 'N', substr( $b, $p, 4 ) )[1]; $p += 4; }
    elseif ( $ai === 27 ) { $len = unpack( 'J', substr( $b, $p, 8 ) )[1]; $p += 8; }
    else throw new Exception( 'unsupported' );
    switch ( $mt ) {
        case 0: return $len;
        case 1: return -1 - $len;
        case 2: case 3: $v = substr( $b, $p, $len ); $p += $len; return $v;
        case 4: $a = []; for ( $i = 0; $i < $len; $i++ ) $a[] = prdrwndn_cbor_item( $b, $p ); return $a;
        case 5: $m = []; for ( $i = 0; $i < $len; $i++ ) { $k = prdrwndn_cbor_item( $b, $p ); $m[ $k ] = prdrwndn_cbor_item( $b, $p ); } return $m;
        case 7: return $ai === 20 ? false : ( $ai === 21 ? true : null );
    }
    throw new Exception( 'type' );
}

/* ════════════════════════════════════════════════════
   6. TRACKING NUMBERS
════════════════════════════════════════════════════ */
function prdrwndn_tracking_carriers() {
    return [
        'usps'  => [ 'USPS', 'https://tools.usps.com/go/TrackConfirmAction?tLabels=%s' ],
        'ups'   => [ 'UPS', 'https://www.ups.com/track?tracknum=%s' ],
        'fedex' => [ 'FedEx', 'https://www.fedex.com/fedextrack/?trknbr=%s' ],
        'dhl'   => [ 'DHL', 'https://www.dhl.com/us-en/home/tracking/tracking-express.html?tracking-id=%s' ],
        'other' => [ 'Carrier', '' ],
    ];
}

/** [ 'carrier' => 'USPS', 'number' => '…', 'url' => '…' ] or null. */
function prdrwndn_tracking( $order ) {
    if ( ! $order ) return null;
    $num = trim( (string) $order->get_meta( '_prd_tracking_number' ) );
    if ( $num === '' ) {
        // WooCommerce Shipment Tracking extension, if it is ever used.
        $items = $order->get_meta( '_wc_shipment_tracking_items' );
        if ( is_array( $items ) && ! empty( $items[0]['tracking_number'] ) ) {
            return [ 'carrier' => (string) ( $items[0]['tracking_provider'] ?: $items[0]['custom_tracking_provider'] ?? 'Carrier' ), 'number' => (string) $items[0]['tracking_number'], 'url' => (string) ( $items[0]['custom_tracking_link'] ?? '' ) ];
        }
        return null;
    }
    $key = (string) $order->get_meta( '_prd_tracking_carrier' ) ?: 'usps';
    $c   = prdrwndn_tracking_carriers()[ $key ] ?? prdrwndn_tracking_carriers()['other'];
    return [ 'carrier' => $c[0], 'number' => $num, 'url' => $c[1] ? sprintf( $c[1], rawurlencode( $num ) ) : '' ];
}

add_action( 'add_meta_boxes', function () {
    $screens = [ 'shop_order' ];
    if ( function_exists( 'wc_get_page_screen_id' ) ) $screens[] = wc_get_page_screen_id( 'shop-order' );
    foreach ( array_unique( $screens ) as $screen ) {
        add_meta_box( 'prd-tracking', 'Tracking number', 'prdrwndn_tracking_box', $screen, 'side', 'high' );
    }
} );

function prdrwndn_tracking_box( $post_or_order ) {
    $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
    if ( ! $order ) return;
    $cur = (string) $order->get_meta( '_prd_tracking_carrier' ) ?: 'usps';
    wp_nonce_field( 'prd_tracking', 'prd_tracking_nonce' );
    echo '<p><label for="prd_tracking_carrier">Carrier</label><br/><select name="prd_tracking_carrier" id="prd_tracking_carrier" style="width:100%">';
    foreach ( prdrwndn_tracking_carriers() as $k => $c ) echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( 'other' === $k ? 'Other' : $c[0] ) . '</option>';
    echo '</select></p><p><label for="prd_tracking_number">Tracking number</label><br/><input type="text" name="prd_tracking_number" id="prd_tracking_number" style="width:100%" value="' . esc_attr( $order->get_meta( '_prd_tracking_number' ) ) . '"/></p>';
    echo '<p class="description">Shows in the customer’s app, their account and the “completed” email.</p>';
}

add_action( 'woocommerce_process_shop_order_meta', function ( $order_id ) {
    if ( ! isset( $_POST['prd_tracking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prd_tracking_nonce'] ) ), 'prd_tracking' ) ) return;
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    $carrier = sanitize_key( wp_unslash( $_POST['prd_tracking_carrier'] ?? 'usps' ) );
    $order->update_meta_data( '_prd_tracking_carrier', array_key_exists( $carrier, prdrwndn_tracking_carriers() ) ? $carrier : 'usps' );
    $order->update_meta_data( '_prd_tracking_number', preg_replace( '/[^A-Za-z0-9 -]/', '', sanitize_text_field( wp_unslash( $_POST['prd_tracking_number'] ?? '' ) ) ) );
    $order->save();
}, 20 );

function prdrwndn_tracking_html( $order, $plain = false ) {
    $t = prdrwndn_tracking( $order );
    if ( ! $t ) return '';
    if ( $plain ) return "\nTracking: " . $t['carrier'] . ' ' . $t['number'] . ( $t['url'] ? ' — ' . $t['url'] : '' ) . "\n";
    $num = esc_html( $t['number'] );
    if ( $t['url'] ) $num = '<a href="' . esc_url( $t['url'] ) . '">' . $num . '</a>';
    return '<p><strong>Tracking:</strong> ' . esc_html( $t['carrier'] ) . ' ' . $num . '</p>';
}
add_action( 'woocommerce_order_details_after_order_table', function ( $order ) { echo prdrwndn_tracking_html( $order ); // phpcs:ignore
} );
add_action( 'woocommerce_email_order_meta', function ( $order, $sent_to_admin, $plain_text ) {
    echo $plain_text ? esc_html( prdrwndn_tracking_html( $order, true ) ) : prdrwndn_tracking_html( $order ); // phpcs:ignore
}, 20, 3 );

/* ════════════════════════════════════════════════════
   7. APP MODE — checkout opened from the app
   The app sets sessionStorage "prdInApp" before opening checkout. While it is set,
   the site's header and footer are swapped for a slim bar that leads back to the app.
════════════════════════════════════════════════════ */
function prdrwndn_is_app_checkout() {
    return function_exists( 'is_checkout' ) && is_checkout();
}
add_action( 'wp_head', function () {
    if ( ! prdrwndn_is_app_checkout() ) return; ?>
<script>try{if(sessionStorage.getItem('prdInApp')==='1')document.documentElement.classList.add('prd-in-app')}catch(e){}</script>
<?php }, 1 );

add_action( 'wp_body_open', function () {
    if ( ! prdrwndn_is_app_checkout() ) return;
    $order_id = 0;
    if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) $order_id = absint( get_query_var( 'order-received' ) );
    ?>
<div class="prd-appbar" id="prd-appbar" data-order="<?php echo (int) $order_id; ?>">
  <a class="prd-appbar-back" href="<?php echo esc_url( home_url( $order_id ? '/app/#thanks/' . $order_id : '/app/#cart' ) ); ?>"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg><span><?php echo $order_id ? 'Done' : 'Back to cart'; ?></span></a>
  <span class="prd-appbar-title"><?php echo $order_id ? 'Order placed' : 'Checkout'; ?></span>
  <span class="prd-appbar-lock"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Secure</span>
</div>
    <?php
}, 1 );

/** In app mode, keep the floating chat button out of the way of the pay buttons. */
add_action( 'wp_footer', function () {
    if ( ! prdrwndn_is_app_checkout() ) return; ?>
<script>
(function () {
  if (!document.documentElement.classList.contains('prd-in-app')) return;
  function hideChat() {
    [].forEach.call(document.body.children, function (el) {
      if (el.shadowRoot && el.shadowRoot.querySelector('button.fab')) el.style.display = 'none';
    });
  }
  hideChat();
  new MutationObserver(hideChat).observe(document.body, { childList: true });
})();
</script>
<?php }, 99 );
