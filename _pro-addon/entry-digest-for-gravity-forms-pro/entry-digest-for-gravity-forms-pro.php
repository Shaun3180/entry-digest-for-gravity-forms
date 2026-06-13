<?php
/**
 * Plugin Name:       Entry Digest for Gravity Forms — Pro
 * Plugin URI:        https://addasitebuilders.com/plugins/entry-digest-for-gravity-forms/
 * Description:       Pro add-on for Entry Digest for Gravity Forms. Adds role-based recipients, conditional filtering, and CSV/Excel attachments. Requires the free Entry Digest for Gravity Forms plugin.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            Add-A-Site Apps
 * Author URI:        https://addasitebuilders.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       entry-digest-for-gravity-forms-pro
 * Domain Path:       /languages
 *
 * ─────────────────────────────────────────────────────────────────────────
 * IMPORTANT — this is the PAID add-on. It is distributed from
 * addasitebuilders.com, NOT from the WordPress.org plugin directory. It must
 * never be bundled inside the free plugin's WordPress.org zip.
 *
 * It works purely by hooking into the free plugin's documented extension points
 * (the `dsagfe_*` actions/filters), so the free plugin remains fully functional
 * on its own.
 * ─────────────────────────────────────────────────────────────────────────
 */
defined( 'ABSPATH' ) || exit;

define( 'EDFGFP_VERSION', '1.0.0' );
define( 'EDFGFP_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDFGFP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Boot the add-on once all plugins are loaded, but only if the free plugin is
 * active (we depend on its functions and hooks). If it isn't, show an admin
 * notice and stay dormant rather than fataling.
 */
add_action( 'plugins_loaded', 'edfgfp_bootstrap' );
function edfgfp_bootstrap(): void {
	if ( ! function_exists( 'dsagfe_get_digests' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Entry Digest for Gravity Forms — Pro requires the free "Entry Digest for Gravity Forms" plugin to be installed and active.', 'entry-digest-for-gravity-forms-pro' );
			echo '</p></div>';
		} );
		return;
	}

	// Licensing gate. Pro features are delivered only to licensed sites. This is
	// allowed here because this add-on is NOT hosted on WordPress.org. Wire your
	// licensing provider (Freemius, EDD, Lemon Squeezy, etc.) in licensing.php.
	require_once EDFGFP_DIR . 'includes/licensing.php';
	if ( ! edfgfp_is_licensed() ) {
		// Optionally surface an activation prompt; features stay off until valid.
		return;
	}

	// Shared engines (moved out of the free plugin).
	require_once EDFGFP_DIR . 'includes/filters.php';
	require_once EDFGFP_DIR . 'includes/export.php';

	// Feature wiring — each file only registers hooks against the free plugin.
	require_once EDFGFP_DIR . 'includes/recipients.php';
	require_once EDFGFP_DIR . 'includes/run.php';
	if ( is_admin() ) {
		require_once EDFGFP_DIR . 'includes/editor-ui.php';
	}
}
