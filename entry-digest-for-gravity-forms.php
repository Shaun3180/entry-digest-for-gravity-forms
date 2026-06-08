<?php
/**
 * Plugin Name:       Entry Digest for Gravity Forms
 * Plugin URI:        https://addasitebuilders.com/plugins
 * Description:       Sends scheduled, readable email digests of your Gravity Forms entries — a summary block plus an inline table of submissions — on a daily or weekly schedule. Pro adds unlimited digests, multi-form aggregation, role/recipient routing, conditional filtering, and CSV/Excel attachments. Find it under Forms › Entry Digest (or Tools › Entry Digest if Gravity Forms is inactive).
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Add-A-Site Apps
 * Author URI:        https://addasitebuilders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       entry-digest-for-gravity-forms
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'edfgf_fs' ) ) {
    // Create a helper function for easy SDK access.
    function edfgf_fs() {
        global $edfgf_fs;

        if ( ! isset( $edfgf_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

            $edfgf_fs = fs_dynamic_init( array(
                'id'                  => '31492',
                'slug'                => 'entry-digest-for-gravity-forms',
                'type'                => 'plugin',
                'public_key'          => 'pk_a940ad16f1e2c268bf1420e486c87',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
                'is_org_compliant'    => true,
                'menu'                => array(
                    'slug'           => 'entry-digest',
                    'support'        => false,
                    'parent'         => array(
                        'slug' => 'gf_edit_forms',
                    ),
                ),
            ) );
        }

        return $edfgf_fs;
    }

    // Init Freemius.
    edfgf_fs();
    // Signal that SDK was initiated.
    do_action( 'edfgf_fs_loaded' );
}

// ── Constants ────────────────────────────────────────────────────
define( 'DSAGFE_CRON_HOOK',       'dsagfe_run_export' );
define( 'DSAGFE_OPTION_KEY',      'dsagfe_settings'   );
define( 'DSAGFE_SCHEMA_VERSION',  2 );
define( 'DSAGFE_MAX_TABLE_ROWS',  100 ); // Cap inline table; full data goes in the attachment.
define( 'DSAGFE_MAX_CELL_CHARS',  200 ); // Truncate long cell values in the inline table.
define( 'DSAGFE_FREE_DIGEST_LIMIT', 1 ); // Free tier: one digest, one form, no routing/filters/attachments.

// ── Module includes ──────────────────────────────────────────────
define( 'EDFGF_DIR', plugin_dir_path( __FILE__ ) );

require_once EDFGF_DIR . 'includes/pro-gate.php';
require_once EDFGF_DIR . 'includes/settings.php';
require_once EDFGF_DIR . 'includes/scheduling.php';
require_once EDFGF_DIR . 'includes/filters.php';
require_once EDFGF_DIR . 'includes/digest.php';
require_once EDFGF_DIR . 'includes/render-email.php';
require_once EDFGF_DIR . 'includes/export.php';

// Admin-only modules (menu screens + AJAX handler).
if ( is_admin() ) {
	require_once EDFGF_DIR . 'admin/menu.php';
	require_once EDFGF_DIR . 'admin/save.php';
	require_once EDFGF_DIR . 'admin/list.php';
	require_once EDFGF_DIR . 'admin/editor.php';
	require_once EDFGF_DIR . 'admin/ajax.php';
}

// ── Activation / deactivation ────────────────────────────────────
register_activation_hook( __FILE__, function () {
	dsagfe_get_settings(); // triggers migration if needed
	dsagfe_reschedule_all();
} );

register_deactivation_hook( __FILE__, function () {
	dsagfe_unschedule_all();
} );

// ── Uninstall ────────────────────────────────────────────────────────
function edfgf_uninstall_cleanup() {
    // Delete plugin option.
    delete_option( 'dsagfe_settings' );
    // Add any other cleanup here.
}
edfgf_fs()->add_action( 'after_uninstall', 'edfgf_uninstall_cleanup' );
