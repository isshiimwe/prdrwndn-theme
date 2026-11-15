<?php
/**
 * PRDRWNDN® Jersey Stock: an admin-only page (Jersey Stock in the WordPress menu).
 *
 *  1. Settings       sizes, designs, channels, defaults
 *  2. Storage        three options, not autoloaded: config, sales, costs
 *  3. Website link   finds the Izuba and Imigongo size variations in the shop
 *  4. Website orders orders that took jersey stock, read straight from WooCommerce
 *  5. Sync           sets each size's website stock to what's free here
 *  6. AJAX           one admin-ajax action, prd_stock, for every change
 *  7. Admin page     menu item, admin bar link, page markup, scripts
 *
 * Free to sell = made - sold - on hold - taken by website orders.
 * With website sync on, every change here (and every page load) sets the
 * stock of each size variation to that number, so the shop can't sell a
 * jersey that was sold in person or is held for pickup. Website orders
 * lower the stock themselves (WooCommerce) and show up here as sales.
 */

defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════════════════
   1. SETTINGS
════════════════════════════════════════════════════ */
function prd_stock_cap()      { return 'manage_options'; }
function prd_stock_sizes()    { return [ 'S', 'M', 'L', 'XL', '2XL', '3XL' ]; }
function prd_stock_designs()  { return [ 'izuba' => 'Izuba', 'imigongo' => 'Imigongo' ]; }
function prd_stock_channels() { return [ 'Presale site', 'TikTok Shop', 'WhatsApp', 'Instagram', 'In person', 'Other' ]; }
function prd_stock_payments() { return [ 'Cash', 'Zelle', 'Apple Pay', 'Cash App', 'Other', 'Card', 'TikTok Shop' ]; }
function prd_stock_statuses() { return [ 'Picked up', 'To ship', 'On hold', 'Shipped' ]; }

function prd_stock_default_config() {
    $run = [ 'S' => 2, 'M' => 5, 'L' => 8, 'XL' => 5, '2XL' => 3, '3XL' => 2 ];
    $deal = function_exists( 'prd_mod' ) ? (int) prd_mod( 'prd_deal_price' ) : 0;
    return [
        'made'   => [ 'izuba' => $run, 'imigongo' => $run ],
        'prices' => [ 'presale' => 45, 'regular' => 50, 'bundle' => $deal > 0 ? $deal : 80 ],
        'fees'   => [
            'Presale site' => [ 'pct' => 2.9, 'fixed' => 0.3 ],
            'TikTok Shop'  => [ 'pct' => 6, 'fixed' => 0 ],
            'Website'      => [ 'pct' => 2.9, 'fixed' => 0.3 ],
        ],
        'note'   => 'First run: 25 of each design',
    ];
}

/* ════════════════════════════════════════════════════
   2. STORAGE
════════════════════════════════════════════════════ */
function prd_stock_get( $key, $default ) {
    wp_cache_delete( 'prd_jersey_' . $key, 'options' );
    $v = get_option( 'prd_jersey_' . $key, null );
    return is_array( $v ) ? $v : $default;
}
function prd_stock_put( $key, $value ) {
    update_option( 'prd_jersey_' . $key, $value, false );
}
function prd_stock_config() {
    $c = prd_stock_get( 'config', [] );
    $d = prd_stock_default_config();
    $c = array_merge( $d, $c );
    $c['prices'] = array_merge( $d['prices'], (array) $c['prices'] );
    $c['fees']   = array_merge( $d['fees'], (array) $c['fees'] );
    return $c;
}

/** One change at a time, so two phones can't both sell the last XL. */
function prd_stock_lock( $on ) {
    global $wpdb;
    if ( ! ( $wpdb instanceof wpdb ) || ( defined( 'DB_ENGINE' ) && DB_ENGINE === 'sqlite' ) ) return;
    $was = $wpdb->suppress_errors( true );
    $wpdb->get_var( $on ? "SELECT GET_LOCK('prd_jersey_stock', 8)" : "SELECT RELEASE_LOCK('prd_jersey_stock')" );
    $wpdb->suppress_errors( $was );
}

function prd_stock_new_id() {
    return strtolower( wp_generate_password( 16, false, false ) );
}

/** Clean one sale or hold. Returns the record or WP_Error. */
function prd_stock_clean_sale( $r ) {
    if ( ! is_array( $r ) ) return new WP_Error( 'bad', 'Something in that sale is missing.' );
    $designs = prd_stock_designs();
    $out = [];
    $out['design'] = (string) ( $r['design'] ?? '' );
    if ( ! isset( $designs[ $out['design'] ] ) ) return new WP_Error( 'bad', 'Pick Izuba or Imigongo.' );
    $out['size'] = (string) ( $r['size'] ?? '' );
    if ( ! in_array( $out['size'], prd_stock_sizes(), true ) ) return new WP_Error( 'bad', 'Pick a size.' );
    $out['qty'] = (int) ( $r['qty'] ?? 0 );
    if ( $out['qty'] < 1 || $out['qty'] > 10 ) return new WP_Error( 'bad', 'How many must be between 1 and 10.' );
    $price = (float) ( $r['price'] ?? -1 );
    if ( $price < 0 || $price > 10000 ) return new WP_Error( 'bad', 'Enter the price in dollars.' );
    $out['price'] = round( $price, 2 );
    $out['channel'] = (string) ( $r['channel'] ?? '' );
    if ( ! in_array( $out['channel'], prd_stock_channels(), true ) ) $out['channel'] = 'Other';
    $pay = (string) ( $r['payment'] ?? '' );
    if ( $pay !== '' && in_array( $pay, prd_stock_payments(), true ) ) $out['payment'] = $pay;
    $out['customer'] = mb_substr( sanitize_text_field( (string) ( $r['customer'] ?? '' ) ), 0, 80 );
    $out['status'] = (string) ( $r['status'] ?? '' );
    if ( ! in_array( $out['status'], prd_stock_statuses(), true ) ) return new WP_Error( 'bad', 'Pick what happened.' );
    if ( $out['status'] === 'On hold' && $out['customer'] === '' ) return new WP_Error( 'bad', "Add who it's held for." );
    foreach ( [ 'date', 'heldDate' ] as $k ) {
        $v = (string) ( $r[ $k ] ?? '' );
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) $out[ $k ] = $v;
    }
    if ( empty( $out['date'] ) ) $out['date'] = current_time( 'Y-m-d' );
    $b = (string) ( $r['bundleId'] ?? '' );
    if ( $b !== '' && preg_match( '/^[A-Za-z0-9_-]{1,40}$/', $b ) ) $out['bundleId'] = $b;
    $at = (float) ( $r['createdAt'] ?? 0 );
    $out['createdAt'] = $at > 0 ? $at : round( microtime( true ) * 1000 );
    return $out;
}

/* ════════════════════════════════════════════════════
   3. WEBSITE LINK
════════════════════════════════════════════════════ */
function prd_stock_norm_size( $v ) {
    $s = strtoupper( preg_replace( '/[^a-z0-9]/i', '', (string) $v ) );
    $alias = [ 'SMALL' => 'S', 'MEDIUM' => 'M', 'LARGE' => 'L', 'XLARGE' => 'XL', 'XXL' => '2XL', '2X' => '2XL', 'XXXL' => '3XL', '3X' => '3XL' ];
    if ( isset( $alias[ $s ] ) ) $s = $alias[ $s ];
    return in_array( $s, prd_stock_sizes(), true ) ? $s : null;
}

/**
 * The jersey products in the shop and their size variations.
 * @return array [ 'map' => [design][size] => variation id, 'products' => [design] => info, 'issues' => string[] ]
 */
function prd_stock_links() {
    static $cache = null;
    if ( $cache !== null ) return $cache;
    $out = [ 'map' => [], 'products' => [], 'issues' => [] ];
    if ( ! function_exists( 'wc_get_products' ) ) {
        $out['issues'][] = 'WooCommerce is switched off, so the website stock is not linked.';
        return $cache = $out;
    }
    $cat  = function_exists( 'prd_mod' ) ? (string) prd_mod( 'prd_jersey_cat' ) : 'jerseys';
    $args = [ 'limit' => 30, 'status' => [ 'publish', 'private', 'draft', 'pending' ], 'type' => 'variable' ];
    if ( $cat !== '' ) $args['category'] = [ $cat ];
    $found = wc_get_products( $args );
    if ( ! $found && $cat !== '' ) { unset( $args['category'] ); $found = wc_get_products( $args ); }
    $designs = prd_stock_designs();
    foreach ( $found as $p ) {
        $hay = strtolower( $p->get_name() . ' ' . $p->get_slug() );
        $design = null;
        foreach ( $designs as $id => $name ) if ( strpos( $hay, $id ) !== false ) { $design = $id; break; }
        if ( ! $design ) continue;
        if ( isset( $out['products'][ $design ] ) ) {
            $out['issues'][] = 'There is more than one ' . $designs[ $design ] . ' jersey product. Using "' . $out['products'][ $design ]['name'] . '".';
            continue;
        }
        $out['products'][ $design ] = [
            'id'   => $p->get_id(),
            'name' => wp_strip_all_tags( $p->get_name() ),
            'url'  => get_permalink( $p->get_id() ),
            'edit' => get_edit_post_link( $p->get_id(), 'raw' ),
        ];
        foreach ( $p->get_children() as $vid ) {
            $v = wc_get_product( $vid );
            if ( ! $v ) continue;
            $size = null;
            foreach ( (array) $v->get_attributes() as $val ) { $size = prd_stock_norm_size( $val ); if ( $size ) break; }
            if ( $size && ! isset( $out['map'][ $design ][ $size ] ) ) $out['map'][ $design ][ $size ] = (int) $vid;
        }
    }
    foreach ( $designs as $id => $name ) {
        if ( empty( $out['products'][ $id ] ) ) { $out['issues'][] = 'No ' . $name . ' jersey product with sizes was found in the shop.'; continue; }
        $missing = array_values( array_diff( prd_stock_sizes(), array_keys( $out['map'][ $id ] ?? [] ) ) );
        if ( $missing ) $out['issues'][] = $name . ' has no ' . implode( ', ', $missing ) . ' option in the shop, so those sizes are tracked here only.';
    }
    return $cache = $out;
}

/** Current website stock per size: int, or null when the size doesn't track stock. */
function prd_stock_store_levels( $links ) {
    $out = [];
    foreach ( $links['map'] as $d => $sizes ) {
        foreach ( $sizes as $s => $vid ) {
            $v = wc_get_product( $vid );
            $out[ $d ][ $s ] = ( $v && $v->get_manage_stock() ) ? (int) $v->get_stock_quantity() : null;
        }
    }
    return $out;
}

/* ════════════════════════════════════════════════════
   4. WEBSITE ORDERS
════════════════════════════════════════════════════ */
/**
 * Every order line that took jersey stock (WooCommerce's own record of what
 * it took, less anything restocked by a refund). Cancelled orders give their
 * stock back and drop out by themselves.
 */
function prd_stock_web_rows( $links ) {
    $rows = [];
    $by_vid = [];
    foreach ( $links['map'] as $d => $sizes ) foreach ( $sizes as $s => $vid ) $by_vid[ $vid ] = [ $d, $s ];
    if ( ! $by_vid || ! function_exists( 'wc_get_orders' ) ) return $rows;
    $config = prd_stock_config();
    $fee    = $config['fees']['Website'] ?? [ 'pct' => 0, 'fixed' => 0 ];

    $orders = wc_get_orders( [
        'limit'   => 300,
        'type'    => 'shop_order',
        'status'  => array_keys( wc_get_order_statuses() ),
        'orderby' => 'date',
        'order'   => 'DESC',
    ] );
    foreach ( $orders as $order ) {
        $lines = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $vid = (int) $item->get_variation_id();
            if ( ! isset( $by_vid[ $vid ] ) ) continue;
            $q = (int) $item->get_meta( '_reduced_stock', true ) - (int) $item->get_meta( '_restock_refunded_items', true );
            if ( $q <= 0 ) continue;
            $qty = max( 1, (int) $item->get_quantity() );
            $lines[] = [ 'id' => $item_id, 'd' => $by_vid[ $vid ][0], 's' => $by_vid[ $vid ][1], 'q' => $q, 'total' => (float) $item->get_total() * min( 1, $q / $qty ) ];
        }
        if ( ! $lines ) continue;

        // The 2-for-$80 deal is a negative fee line on the order; share it across the jerseys.
        $deal = 0.0;
        foreach ( $order->get_fees() as $f ) if ( stripos( $f->get_name(), 'jersey deal' ) !== false ) $deal += (float) $f->get_total();
        $gross = array_sum( array_column( $lines, 'total' ) );
        $net   = max( 0, $gross + $deal );
        $order_total = (float) $order->get_total();
        $order_fee   = $net > 0 ? $net * (float) $fee['pct'] / 100 + (float) $fee['fixed'] * ( $order_total > 0 ? min( 1, $net / $order_total ) : 1 ) : 0;

        $st     = $order->get_status();
        $pickup = false;
        foreach ( $order->get_shipping_methods() as $m ) if ( strpos( (string) $m->get_method_id(), 'pickup' ) !== false ) $pickup = true;
        $paid   = in_array( $st, [ 'processing', 'completed' ], true );
        $status = ! $paid ? 'Awaiting payment' : ( $st === 'completed' ? ( $pickup ? 'Picked up' : 'Shipped' ) : ( $pickup ? 'To pick up' : 'To ship' ) );
        $name   = trim( $order->get_formatted_billing_full_name() ) ?: trim( $order->get_formatted_shipping_full_name() );
        $date   = $order->get_date_created();

        foreach ( $lines as $l ) {
            $share = $gross > 0 ? $l['total'] / $gross : 0;
            $rows[] = [
                'id'        => 'wc' . $l['id'],
                'web'       => true,
                'design'    => $l['d'],
                'size'      => $l['s'],
                'qty'       => $l['q'],
                'price'     => round( ( $l['total'] + $deal * $share ) / $l['q'], 2 ),
                'fee'       => round( $order_fee * $share, 2 ),
                'channel'   => 'Website',
                'payment'   => wp_strip_all_tags( (string) $order->get_payment_method_title() ),
                'customer'  => wp_strip_all_tags( $name ),
                'date'      => $date ? $date->date_i18n( 'Y-m-d' ) : current_time( 'Y-m-d' ),
                'status'    => $status,
                'createdAt' => $date ? $date->getTimestamp() * 1000 : 0,
                'bundleId'  => count( $lines ) > 1 ? 'wc' . $order->get_id() : null,
                'orderNo'   => (string) $order->get_order_number(),
                'orderUrl'  => $order->get_edit_order_url(),
            ];
        }
    }
    return $rows;
}

/* ════════════════════════════════════════════════════
   5. SYNC
════════════════════════════════════════════════════ */
function prd_stock_free( $config, $sales, $web ) {
    $free = [];
    foreach ( array_keys( prd_stock_designs() ) as $d ) {
        foreach ( prd_stock_sizes() as $s ) $free[ $d ][ $s ] = (int) ( $config['made'][ $d ][ $s ] ?? 0 );
    }
    foreach ( array_merge( array_values( $sales ), $web ) as $r ) {
        if ( isset( $free[ $r['design'] ][ $r['size'] ] ) ) $free[ $r['design'] ][ $r['size'] ] -= (int) $r['qty'];
    }
    return $free;
}

function prd_stock_sync_on() {
    return (bool) get_option( 'prd_jersey_sync', false );
}

/** Set each size's website stock to what's free here. Returns what changed. */
function prd_stock_push( $free, $links ) {
    $changed  = [];
    $parents  = [];
    $designs  = prd_stock_designs();
    foreach ( $links['map'] as $d => $sizes ) {
        foreach ( $sizes as $s => $vid ) {
            $v = wc_get_product( $vid );
            if ( ! $v ) continue;
            $target = max( 0, (int) ( $free[ $d ][ $s ] ?? 0 ) );
            $now    = $v->get_manage_stock() ? (int) $v->get_stock_quantity() : null;
            if ( $now === $target && $v->get_backorders() === 'no' ) continue;
            $v->set_manage_stock( true );
            $v->set_backorders( 'no' );
            $v->set_stock_quantity( $target );
            $v->save();
            $changed[] = [ 'design' => $d, 'size' => $s, 'from' => $now, 'to' => $target ];
            $parents[ $v->get_parent_id() ] = true;
        }
    }
    foreach ( array_keys( $parents ) as $pid ) {
        if ( class_exists( 'WC_Product_Variable' ) ) WC_Product_Variable::sync( $pid );
        wc_delete_product_transients( $pid );
        do_action( 'litespeed_purge_post', $pid );
    }
    return $changed;
}

/** Everything the page shows. Syncs the website first when sync is on. */
function prd_stock_state() {
    $config = prd_stock_config();
    $sales  = prd_stock_get( 'sales', [] );
    $costs  = prd_stock_get( 'costs', [] );
    $links  = prd_stock_links();
    $web    = prd_stock_web_rows( $links );
    $free   = prd_stock_free( $config, $sales, $web );
    $sync   = prd_stock_sync_on();
    $changed = $sync ? prd_stock_push( $free, $links ) : [];

    $list = [];
    foreach ( $sales as $id => $r ) { $r['id'] = (string) $id; $list[] = $r; }
    $clist = [];
    foreach ( $costs as $id => $c ) { $c['id'] = (string) $id; $clist[] = $c; }

    $linked = [];
    foreach ( $links['products'] as $d => $p ) $linked[ $d ] = $p + [ 'sizes' => count( $links['map'][ $d ] ?? [] ) ];

    return [
        'config' => $config,
        'sales'  => $list,
        'costs'  => $clist,
        'web'    => $web,
        'store'  => [
            'sync'    => $sync,
            'linked'  => $linked,
            'issues'  => $links['issues'],
            'levels'  => prd_stock_store_levels( $links ),
            'changed' => $changed,
            'shop'    => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
        ],
        'today'  => current_time( 'Y-m-d' ),
    ];
}

/* ════════════════════════════════════════════════════
   6. AJAX
════════════════════════════════════════════════════ */
add_action( 'wp_ajax_prd_stock', function () {
    if ( ! current_user_can( prd_stock_cap() ) ) wp_send_json_error( [ 'message' => 'Only the store admin can use this.' ], 403 );
    if ( ! check_ajax_referer( 'prd_stock', 'nonce', false ) ) wp_send_json_error( [ 'message' => 'This page is out of date. Reload it and try again.', 'code' => 'nonce' ], 403 );
    $op   = sanitize_key( $_POST['op'] ?? 'state' );
    $data = json_decode( wp_unslash( (string) ( $_POST['data'] ?? '' ) ), true );
    if ( ! is_array( $data ) ) $data = [];
    nocache_headers();
    prd_stock_lock( true );
    try {
        $res = prd_stock_apply( $op, $data );
        if ( is_wp_error( $res ) ) wp_send_json_error( [ 'message' => $res->get_error_message() ], 400 );
        wp_send_json_success( prd_stock_state() );
    } finally {
        prd_stock_lock( false );
    }
} );

/** Apply one change. Returns true or WP_Error. */
function prd_stock_apply( $op, $data ) {
    $designs = prd_stock_designs();
    switch ( $op ) {
        case 'state':
            return true;

        case 'add': {
            $recs = isset( $data['recs'] ) && is_array( $data['recs'] ) ? array_values( $data['recs'] ) : [];
            if ( ! $recs || count( $recs ) > 2 ) return new WP_Error( 'bad', 'Nothing to log.' );
            $sales = prd_stock_get( 'sales', [] );
            $clean = [];
            $need  = [];
            foreach ( $recs as $r ) {
                $c = prd_stock_clean_sale( $r );
                if ( is_wp_error( $c ) ) return $c;
                $clean[] = $c;
                $k = $c['design'] . '|' . $c['size'];
                $need[ $k ] = ( $need[ $k ] ?? 0 ) + $c['qty'];
            }
            $links = prd_stock_links();
            $free  = prd_stock_free( prd_stock_config(), $sales, prd_stock_web_rows( $links ) );
            foreach ( $need as $k => $n ) {
                [ $d, $s ] = explode( '|', $k );
                if ( $n > $free[ $d ][ $s ] ) return new WP_Error( 'stock', 'Only ' . max( 0, $free[ $d ][ $s ] ) . ' ' . $designs[ $d ] . ' free in ' . $s . '.' );
            }
            foreach ( $clean as $c ) $sales[ prd_stock_new_id() ] = $c;
            prd_stock_put( 'sales', $sales );
            return true;
        }

        case 'update': {
            $id    = (string) ( $data['id'] ?? '' );
            $patch = isset( $data['patch'] ) && is_array( $data['patch'] ) ? $data['patch'] : [];
            $sales = prd_stock_get( 'sales', [] );
            if ( ! isset( $sales[ $id ] ) ) return new WP_Error( 'gone', 'That entry was already removed. The list is up to date now.' );
            $old = $sales[ $id ];
            $new = $old;
            foreach ( [ 'status', 'date', 'heldDate', 'design', 'size' ] as $k ) {
                if ( ! array_key_exists( $k, $patch ) ) continue;
                if ( $k === 'heldDate' && ( $patch[ $k ] === null || $patch[ $k ] === '' ) ) { unset( $new['heldDate'] ); continue; }
                $new[ $k ] = $patch[ $k ];
            }
            $c = prd_stock_clean_sale( $new );
            if ( is_wp_error( $c ) ) return $c;
            if ( $c['design'] !== $old['design'] || $c['size'] !== $old['size'] ) {
                $rest = $sales;
                unset( $rest[ $id ] );
                $free = prd_stock_free( prd_stock_config(), $rest, prd_stock_web_rows( prd_stock_links() ) );
                if ( $c['qty'] > $free[ $c['design'] ][ $c['size'] ] ) return new WP_Error( 'stock', 'Only ' . max( 0, $free[ $c['design'] ][ $c['size'] ] ) . ' ' . $designs[ $c['design'] ] . ' free in ' . $c['size'] . '.' );
            }
            $sales[ $id ] = $c;
            prd_stock_put( 'sales', $sales );
            return true;
        }

        case 'delete': {
            $id    = (string) ( $data['id'] ?? '' );
            $sales = prd_stock_get( 'sales', [] );
            unset( $sales[ $id ] );
            prd_stock_put( 'sales', $sales );
            return true;
        }

        case 'cost_add': {
            $label  = mb_substr( sanitize_text_field( (string) ( $data['label'] ?? '' ) ), 0, 60 );
            $amount = round( (float) ( $data['amount'] ?? 0 ), 2 );
            if ( $label === '' ) return new WP_Error( 'bad', 'Say what the cost was for.' );
            if ( $amount <= 0 || $amount > 100000 ) return new WP_Error( 'bad', 'Enter the cost in dollars.' );
            $costs = prd_stock_get( 'costs', [] );
            $costs[ prd_stock_new_id() ] = [ 'label' => $label, 'amount' => $amount, 'createdAt' => round( microtime( true ) * 1000 ) ];
            prd_stock_put( 'costs', $costs );
            return true;
        }

        case 'cost_remove': {
            $costs = prd_stock_get( 'costs', [] );
            unset( $costs[ (string) ( $data['id'] ?? '' ) ] );
            prd_stock_put( 'costs', $costs );
            return true;
        }

        case 'sync': {
            update_option( 'prd_jersey_sync', ! empty( $data['on'] ), false );
            return true;
        }

        case 'import': {
            // One-time move from the old tracker. Refuses to overwrite unless asked.
            $have = prd_stock_get( 'sales', [] ) || prd_stock_get( 'costs', [] );
            if ( $have && empty( $data['replace'] ) ) return new WP_Error( 'exists', 'The tracker already has entries.' );
            if ( isset( $data['config'] ) && is_array( $data['config'] ) ) {
                $c = prd_stock_default_config();
                foreach ( array_keys( $designs ) as $d ) foreach ( prd_stock_sizes() as $s ) {
                    if ( isset( $data['config']['made'][ $d ][ $s ] ) ) $c['made'][ $d ][ $s ] = max( 0, (int) $data['config']['made'][ $d ][ $s ] );
                }
                foreach ( [ 'presale', 'regular', 'bundle' ] as $k ) if ( isset( $data['config']['prices'][ $k ] ) && (float) $data['config']['prices'][ $k ] > 0 ) $c['prices'][ $k ] = (float) $data['config']['prices'][ $k ];
                foreach ( (array) ( $data['config']['fees'] ?? [] ) as $ch => $f ) {
                    if ( isset( $c['fees'][ $ch ] ) ) $c['fees'][ $ch ] = [ 'pct' => (float) ( $f['pct'] ?? 0 ), 'fixed' => (float) ( $f['fixed'] ?? 0 ) ];
                }
                if ( isset( $data['config']['note'] ) ) $c['note'] = mb_substr( sanitize_text_field( (string) $data['config']['note'] ), 0, 120 );
                prd_stock_put( 'config', $c );
            }
            $sales = [];
            foreach ( (array) ( $data['sales'] ?? [] ) as $r ) {
                $c = prd_stock_clean_sale( $r );
                if ( is_wp_error( $c ) ) return new WP_Error( 'bad', 'Sale ' . sanitize_text_field( (string) ( $r['id'] ?? '?' ) ) . ': ' . $c->get_error_message() );
                $id = preg_match( '/^[a-z0-9]{6,40}$/', (string) ( $r['id'] ?? '' ) ) ? (string) $r['id'] : prd_stock_new_id();
                $sales[ $id ] = $c;
            }
            $costs = [];
            foreach ( (array) ( $data['costs'] ?? [] ) as $r ) {
                $label = mb_substr( sanitize_text_field( (string) ( $r['label'] ?? '' ) ), 0, 60 );
                $amount = round( (float) ( $r['amount'] ?? 0 ), 2 );
                if ( $label === '' || $amount <= 0 ) continue;
                $id = preg_match( '/^[a-z0-9-]{2,40}$/', (string) ( $r['id'] ?? '' ) ) ? (string) $r['id'] : prd_stock_new_id();
                $costs[ $id ] = [ 'label' => $label, 'amount' => $amount, 'createdAt' => (float) ( $r['createdAt'] ?? 0 ) ];
            }
            prd_stock_put( 'sales', $sales );
            prd_stock_put( 'costs', $costs );
            return true;
        }
    }
    return new WP_Error( 'bad', 'Unknown action.' );
}

/* ════════════════════════════════════════════════════
   7. ADMIN PAGE
════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
    add_menu_page( 'Jersey Stock', 'Jersey Stock', prd_stock_cap(), 'prd-jersey-stock', 'prd_stock_page', 'dashicons-tag', 56 );
} );

add_action( 'admin_bar_menu', function ( $bar ) {
    if ( ! current_user_can( prd_stock_cap() ) ) return;
    $bar->add_node( [ 'id' => 'prd-jersey-stock', 'title' => 'Jersey Stock', 'href' => admin_url( 'admin.php?page=prd-jersey-stock' ) ] );
}, 80 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'toplevel_page_prd-jersey-stock' ) return;
    $base = get_template_directory_uri() . '/assets/stock/';
    $dir  = get_template_directory() . '/assets/stock/';
    wp_enqueue_style( 'prd-stock', $base . 'stock.css', [], (string) filemtime( $dir . 'stock.css' ) );
    wp_enqueue_script( 'prd-stock', $base . 'stock.js', [], (string) filemtime( $dir . 'stock.js' ), true );
    prd_stock_lock( true );
    try { $state = prd_stock_state(); } finally { prd_stock_lock( false ); }
    wp_add_inline_script( 'prd-stock', 'window.PRD_STOCK=' . wp_json_encode( [
        'ajax'  => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'prd_stock' ),
        'state' => $state,
    ] ) . ';', 'before' );
    $fonts = get_template_directory_uri() . '/assets/fonts/';
    wp_add_inline_style( 'prd-stock', "@font-face{font-family:'PRD Bebas';src:url('{$fonts}bebas-neue-latin-400-normal.woff2') format('woff2');font-weight:400;font-display:swap}@font-face{font-family:'PRD Inter';src:url('{$fonts}inter-latin-wght-normal.woff2') format('woff2');font-weight:100 900;font-display:swap}" );
} );

function prd_stock_page() {
    if ( ! current_user_can( prd_stock_cap() ) ) return;
    ?>
<div class="wrap prd-stock-page">
<h1 class="screen-reader-text">Jersey Stock</h1>
<div id="prd-stock">
  <div class="st-wrap">
    <header class="top">
      <div class="brand">
        <span class="eyebrow" id="eyebrow">PRDRWNDN® · Jerseys</span>
        <h2 class="st-title">Jersey Stock</h2>
      </div>
      <dl class="summary" aria-live="polite">
        <div><dt>Sold</dt><dd id="sumSold">0</dd></div>
        <div class="held"><dt>On hold</dt><dd id="sumHeld">0</dd></div>
        <div><dt>Left</dt><dd id="sumLeft">0</dd></div>
        <div><dt>To ship</dt><dd id="sumShip">0</dd></div>
        <div><dt>Paid in</dt><dd id="sumRev">$0</dd></div>
      </dl>
    </header>

    <p id="notice" class="st-notice" role="status" hidden></p>

    <section aria-labelledby="stockH">
      <div class="section-head">
        <h3 class="st-h2" id="stockH">Stock by size</h3>
        <p>Big number = free to sell. Held jerseys are already taken out. Tap a size to log it.</p>
      </div>
      <div id="stock" class="stock"><div class="placeholder">Loading stock for Izuba and Imigongo...</div></div>
      <div class="legend">
        <span><i style="background: var(--green)"></i>Sold</span>
        <span><i style="background: var(--hold)"></i>On hold</span>
        <span><i style="background: var(--hold-bg)"></i>Last one free</span>
        <span><i style="background: var(--bad-bg)"></i>None free</span>
      </div>
    </section>

    <section class="panel site" aria-labelledby="siteH">
      <div class="section-head">
        <h3 class="st-h2" id="siteH">Website stock</h3>
        <p id="siteNote"></p>
      </div>
      <div id="siteBody"></div>
    </section>

    <section class="panel" aria-labelledby="logH">
      <div class="section-head"><h3 class="st-h2" id="logH">Log a sale or a hold</h3></div>
      <form id="saleForm" novalidate>
        <div class="field">
          <span class="lbl" id="modeLbl">How many jerseys</span>
          <div class="seg" id="modeSeg" role="group" aria-labelledby="modeLbl"></div>
        </div>
        <div id="linesBox" class="lines"></div>
        <div class="field">
          <span class="lbl" id="statusLbl">What happened</span>
          <div class="seg" id="statusSeg" role="group" aria-labelledby="statusLbl"></div>
        </div>
        <div class="row">
          <div class="field" id="qtyField"><label for="qty">How many</label><input id="qty" type="number" min="1" max="10" step="1" value="1" inputmode="numeric"></div>
          <div class="field"><label for="channel">Sold through</label>
            <select id="channel">
              <option>Presale site</option>
              <option>TikTok Shop</option>
              <option>WhatsApp</option>
              <option>Instagram</option>
              <option selected>In person</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field" id="payField"><label for="payment">Paid with</label>
            <select id="payment">
              <option>Cash</option>
              <option>Zelle</option>
              <option>Apple Pay</option>
              <option>Cash App</option>
              <option>Other</option>
            </select>
          </div>
          <div class="field"><label for="price" id="priceLbl">Price each ($)</label><input id="price" type="number" min="0" step="1" value="50" inputmode="decimal"></div>
        </div>
        <div class="row">
          <div class="field"><label for="customer" id="customerLbl">Customer (optional)</label><input id="customer" type="text" maxlength="80" autocomplete="off" placeholder="Name or order number"></div>
          <div class="field"><label for="date">Date</label><input id="date" type="date"></div>
        </div>
        <p class="preview" id="preview" aria-live="polite"></p>
        <div class="actions-row">
          <button class="primary" id="submitBtn" type="submit">LOG SALE</button>
          <p class="msg" id="formMsg" role="status" aria-live="polite"></p>
        </div>
      </form>
    </section>

    <section aria-labelledby="moneyH">
      <div class="section-head">
        <h3 class="st-h2" id="moneyH">Money</h3>
        <p id="feeNote">Cash, Zelle, Apple Pay and Cash App are counted as free. Card fees (presale site and website) and TikTok Shop fees are estimates.</p>
      </div>
      <div id="money" class="money"><div class="placeholder">Profit numbers appear once costs and sales load.</div></div>
    </section>

    <section aria-labelledby="salesH">
      <div class="section-head">
        <h3 class="st-h2" id="salesH">Sales and holds</h3>
        <div class="filters" id="filters" role="group" aria-label="Show"></div>
      </div>
      <div id="salesList" class="list"><div class="empty">Sales and holds you log show up here, newest first.</div></div>
    </section>
  </div>
</div>
</div>
    <?php
}
