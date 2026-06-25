<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'edfgfp_fs' ) ) {
    function edfgfp_fs() {
        global $edfgfp_fs;

        if ( ! isset( $edfgfp_fs ) ) {
            require_once EDFGFP_DIR . 'freemius/start.php';

            $edfgfp_fs = fs_dynamic_init( array(
                'id'              => '31492',
                'slug'            => 'entry-digest-for-gravity-forms',
                'premium_slug'    => 'entry-digest-for-gravity-forms-pro',
                'type'            => 'plugin',
                'public_key'      => 'pk_a940ad16f1e2c268bf1420e486c87',
                'is_premium'      => true,
                'is_premium_only' => true,
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'is_org_compliant'=> false,
                'menu'            => array(
                    'slug'    => 'entry-digest',
                    'support' => false,
                    'parent'  => array( 'slug' => 'gf_edit_forms' ),
                ),
            ) );
        }

        return $edfgfp_fs;
    }

    edfgfp_fs();
    do_action( 'edfgfp_fs_loaded' );
}