<?php
/**
 * Plugin Name:       Entry Digest for Gravity Forms - Pro
 * Plugin URI:        https://addasitebuilders.com/plugins/entry-digest-for-gravity-forms/
 * Description:       Pro add-on for Entry Digest for Gravity Forms. Adds multi-form aggregation, conditional filtering, CSV/Excel attachments, role-based recipients, custom email branding, and in-editor Gravity Forms notification controls. Requires the free Entry Digest for Gravity Forms plugin.
 * Version:           1.4.0
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
 * IMPORTANT - this is the PAID add-on. It is distributed from
 * addasitebuilders.com, NOT from the WordPress.org plugin directory. It must
 * never be bundled inside the free plugin's WordPress.org zip.
 *
 * It works purely by hooking into the free plugin's documented extension points
 * (the `edfgf_*` actions/filters), so the free plugin remains fully functional
 * on its own.
 * ─────────────────────────────────────────────────────────────────────────
 */
defined( 'ABSPATH' ) || exit;

define( 'EDFGFP_VERSION', '1.4.0' );
define( 'EDFGFP_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDFGFP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Local development override. Define this in wp-config.php:
 *
 *     define( 'EDFGFP_DEV_LICENSE', true );
 *
 * When set, the add-on loads all Pro features without a Freemius licence and
 * skips Freemius initialization entirely - so the "enter your license key"
 * activation prompt never appears. Intended for local/staging only; NEVER
 * define this on a production site.
 */
function edfgfp_dev_license(): bool {
	return defined( 'EDFGFP_DEV_LICENSE' ) && EDFGFP_DEV_LICENSE;
}

// Pro-specific uninstall cleanup. Registered unconditionally - it must not be
// gated behind the license check, otherwise cleanup is skipped when a license
// has expired.
function edfgfp_pro_cleanup(): void {
	// Add any pro-specific option keys here.
	delete_option( 'edfgfp_settings' );
	delete_option( 'edfgfp_log_max' );
}

if ( edfgfp_dev_license() ) {
	// Dev mode: no Freemius, no activation screen. Use WordPress's own uninstall
	// hook for cleanup instead of Freemius's after_uninstall.
	register_uninstall_hook( __FILE__, 'edfgfp_pro_cleanup' );
} else {
	// Production: initialize Freemius at plugin-load time (not inside a hook) so
	// it can register its own early actions/filters correctly.
	require_once EDFGFP_DIR . 'includes/freemius-init.php';
	edfgfp_fs()->add_action( 'after_uninstall', 'edfgfp_pro_cleanup' );
}

/**
 * Boot the add-on once all plugins are loaded, but only if the free plugin is
 * active (we depend on its functions and hooks). If it isn't, show an admin
 * notice and stay dormant rather than fataling.
 */
add_action( 'plugins_loaded', 'edfgfp_bootstrap' );
function edfgfp_bootstrap(): void {
	if ( ! function_exists( 'edfgf_get_digests' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'Entry Digest for Gravity Forms - Pro requires the free "Entry Digest for Gravity Forms" plugin to be installed and active.', 'entry-digest-for-gravity-forms-pro' );
			echo '</p></div>';
		} );
		return;
	}

	// License gate - Pro features load on sites with a valid Freemius licence, or
	// when the local dev override is set. The dev check short-circuits, so
	// edfgfp_fs() is never called when Freemius wasn't initialized.
	if ( ! edfgfp_dev_license() && ! edfgfp_fs()->can_use_premium_code() ) {
		return;
	}

	// Shared engines (moved out of the free plugin).
	require_once EDFGFP_DIR . 'includes/filters.php';
	require_once EDFGFP_DIR . 'includes/export.php';

	// Feature wiring - each file only registers hooks against the free plugin.
	require_once EDFGFP_DIR . 'includes/recipients.php';
	require_once EDFGFP_DIR . 'includes/run.php';
	require_once EDFGFP_DIR . 'includes/branding.php';
	if ( is_admin() ) {
		require_once EDFGFP_DIR . 'includes/editor-ui.php';
		require_once EDFGFP_DIR . 'includes/ordering.php';
		require_once EDFGFP_DIR . 'includes/email-preview.php';
		require_once EDFGFP_DIR . 'includes/notifications.php';
		require_once EDFGFP_DIR . 'includes/log-settings.php';
	}
}
