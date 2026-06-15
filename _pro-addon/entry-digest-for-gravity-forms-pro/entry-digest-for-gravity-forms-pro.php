<?php
/**
 * Plugin Name:       Entry Digest for Gravity Forms — Pro
 * Plugin URI:        https://addasitebuilders.com/plugins/entry-digest-for-gravity-forms/
 * Description:       Pro add-on for Entry Digest for Gravity Forms. Adds multi-form aggregation, conditional filtering, CSV/Excel attachments, role-based recipients, and custom email branding. Requires the free Entry Digest for Gravity Forms plugin.
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

// Initialize Freemius at plugin-load time (not inside a hook) so it can
// register its own early actions/filters correctly.
require_once EDFGFP_DIR . 'includes/freemius-init.php';

// Register uninstall cleanup unconditionally — must not be gated behind the
// license check, otherwise cleanup is skipped when the license has expired.
function edfgfp_pro_cleanup(): void {
	// Add any pro-specific option keys here.
	delete_option( 'edfgfp_settings' );
}
edfgfp_fs()->add_action( 'after_uninstall', 'edfgfp_pro_cleanup' );

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

	// License gate — pro features only load on sites with a valid Freemius licence.
	if ( ! edfgfp_fs()->can_use_premium_code() ) {
		return;
	}

	// Shared engines (moved out of the free plugin).
	require_once EDFGFP_DIR . 'includes/filters.php';
	require_once EDFGFP_DIR . 'includes/export.php';

	// Feature wiring — each file only registers hooks against the free plugin.
	require_once EDFGFP_DIR . 'includes/recipients.php';
	require_once EDFGFP_DIR . 'includes/run.php';
	require_once EDFGFP_DIR . 'includes/branding.php';
	if ( is_admin() ) {
		require_once EDFGFP_DIR . 'includes/editor-ui.php';
		require_once EDFGFP_DIR . 'includes/log-settings.php';
	}
}
