<?php
defined( 'ABSPATH' ) || exit;

// includes/freemius-init.php
function edfgfp_fs() {
    global $edfgfp_fs;
    if ( ! isset( $edfgfp_fs ) ) {
        require_once EDFGFP_DIR . 'freemius/start.php';
        $edfgfp_fs = fs_dynamic_init( [
            'id'                => '31492',   // from Freemius dashboard
            'slug'              => 'entry-digest-for-gravity-forms-pro',
            'type'              => 'plugin',
            'public_key'        => 'pk_a940ad16f1e2c268bf1420e486c87',      // from Freemius dashboard
            'is_premium'        => true,
            'is_premium_only'   => true,
            'has_paid_plans'    => true,
            'menu'              => [ 'slug' => 'entry-digest-for-gravity-forms' ],
        ] );
    }
    return $edfgfp_fs;
}
edfgfp_fs();  // call immediately so Freemius loads