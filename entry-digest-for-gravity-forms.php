<?php
/**
 * Plugin Name:       Entry Digest for Gravity Forms
 * Plugin URI:        https://addasitebuilders.com/plugins
 * Description:       Sends scheduled, readable email digests of your Gravity Forms entries - a summary block plus an inline table of submissions - on a daily, weekly, or one-time schedule. Unlimited digests, each covering one or more forms. Find it under Forms › Entry Digest (or Tools › Entry Digest if Gravity Forms is inactive).
 * Version:           2.9.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            Add-A-Site Apps
 * Author URI:        https://addasitebuilders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       entry-digest-for-gravity-forms
 * Domain Path:        /languages
 */
defined( 'ABSPATH' ) || exit;

// ── Constants ────────────────────────────────────────────────────
define( 'EDFGF_VERSION',         '2.9.0' );
define( 'EDFGF_CRON_HOOK',       'edfgf_run_export' );
define( 'EDFGF_OPTION_KEY',      'edfgf_settings'   );
define( 'EDFGF_SCHEMA_VERSION',  2 );
define( 'EDFGF_MAX_TABLE_ROWS',  100 ); // Cap inline table; full data goes in any attachment.
define( 'EDFGF_MAX_CELL_CHARS',  200 ); // Truncate long cell values in the inline table.

// Translations load automatically: WordPress reads the Text Domain and Domain
// Path headers and loads translations just-in-time, so no load_plugin_textdomain()
// call is needed. Locale translations are provided via translate.wordpress.org;
// the bundled /languages folder ships only the .pot template for translators.

// ── Module includes ──────────────────────────────────────────────
define( 'EDFGF_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDFGF_URL', plugin_dir_url( __FILE__ ) );

require_once EDFGF_DIR . 'includes/settings.php';
require_once EDFGF_DIR . 'includes/scheduling.php';
require_once EDFGF_DIR . 'includes/log.php';
require_once EDFGF_DIR . 'includes/digest.php';
require_once EDFGF_DIR . 'includes/render-email.php';

// Admin-only modules (menu screens + AJAX handler).
if ( is_admin() ) {
	require_once EDFGF_DIR . 'includes/cron-health.php';
	require_once EDFGF_DIR . 'admin/menu.php';
	require_once EDFGF_DIR . 'admin/enqueue.php';
	require_once EDFGF_DIR . 'admin/save.php';
	require_once EDFGF_DIR . 'admin/list.php';
	require_once EDFGF_DIR . 'admin/editor.php';
	require_once EDFGF_DIR . 'admin/pro-page.php';
	require_once EDFGF_DIR . 'admin/ajax.php';
	require_once EDFGF_DIR . 'admin/help.php';
}

// ── Activation / deactivation ────────────────────────────────────
register_activation_hook( __FILE__, function () {
	edfgf_get_settings(); // triggers migration if needed
	edfgf_reschedule_all();
} );

register_deactivation_hook( __FILE__, function () {
	edfgf_unschedule_all();
} );

// Uninstall cleanup is handled by uninstall.php, which WordPress runs only when
// the plugin is deleted (option removal + cron clearing, multisite-aware).
