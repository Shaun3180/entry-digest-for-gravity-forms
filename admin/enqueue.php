<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the plugin's admin scripts on the screens that need them.
 *
 * Everything is registered through the standard wp_enqueue_script() API and
 * loaded only where it is used:
 *
 *   - admin/js/editor.js  : digest editor screen (action=new|edit) - form-block
 *                           toggling, schedule-row visibility, live entry count.
 *   - admin/js/list.js    : digest list screen - destructive-action confirms and
 *                           the optional Pro upsell panel behavior.
 *   - admin/js/notices.js : dashboard + digest list screen - dismiss handling for
 *                           the overdue-cron admin notice.
 *
 * The page lives at admin.php?page=edfgf-entry-digest (or
 * tools.php?page=edfgf-entry-digest when Gravity Forms is inactive).
 *
 * Scripts are registered with the WordPress 6.3 "defer" loading strategy via the
 * $args array; on WordPress 6.1-6.2 the array is treated as "load in footer",
 * which is the safe, equivalent fallback.
 */
add_action( 'admin_enqueue_scripts', 'edfgf_enqueue_admin_assets' );
function edfgf_enqueue_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing, no state change.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing, no state change.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

	$is_our_page  = ( 'edfgf-entry-digest' === $page );
	$is_editor    = $is_our_page && ( 'new' === $action || 'edit' === $action );
	$is_list      = $is_our_page && ! $is_editor;
	$screen       = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_dashboard = $screen && 'dashboard' === $screen->id;

	$footer_defer = [ 'in_footer' => true, 'strategy' => 'defer' ];

	// ── Digest editor screen ─────────────────────────────────────────
	if ( $is_editor ) {
		wp_enqueue_script(
			'edfgf-editor',
			EDFGF_URL . 'admin/js/editor.js',
			[],
			EDFGF_VERSION,
			$footer_defer
		);

		wp_localize_script( 'edfgf-editor', 'EDFGF_I18N', [
			'calculating' => __( 'Calculating…', 'entry-digest-for-gravity-forms' ),
			'unable'      => __( 'Unable to calculate.', 'entry-digest-for-gravity-forms' ),
			'gfInactive'  => __( 'Gravity Forms is not active - counts unavailable.', 'entry-digest-for-gravity-forms' ),
			'selectForm'  => __( 'Select a form to see a count.', 'entry-digest-for-gravity-forms' ),
			'entry'       => __( 'entry', 'entry-digest-for-gravity-forms' ),
			'entries'     => __( 'entries', 'entry-digest-for-gravity-forms' ),
			'inThe'       => _x( 'in the', 'precedes a time window such as "past 7 days"', 'entry-digest-for-gravity-forms' ),
			'inWord'      => _x( 'in', 'precedes a time window in the per-form count badge', 'entry-digest-for-gravity-forms' ),
		] );

		wp_localize_script( 'edfgf-editor', 'EDFGF_COUNT', [
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'edfgf_entry_count' ),
			'gf'    => class_exists( 'GFAPI' ),
		] );
	}

	// ── Digest list screen (confirms + Pro panel) ────────────────────
	if ( $is_list ) {
		wp_enqueue_script(
			'edfgf-list',
			EDFGF_URL . 'admin/js/list.js',
			[],
			EDFGF_VERSION,
			$footer_defer
		);

		// The Pro upsell panel's stylesheet - loaded only when the panel will
		// actually render (free install, capable user, not dismissed). The dismiss
		// nonce and AJAX URL are passed through data attributes on the link itself.
		if ( function_exists( 'edfgf_pro_panel_should_show' ) && edfgf_pro_panel_should_show() ) {
			wp_enqueue_style(
				'edfgf-pro-panel',
				EDFGF_URL . 'admin/css/pro-panel.css',
				[],
				EDFGF_VERSION
			);
		}
	}

	// ── Overdue-cron notice dismissal (dashboard + our list screen) ──
	if ( $is_dashboard || $is_list ) {
		wp_enqueue_script(
			'edfgf-notices',
			EDFGF_URL . 'admin/js/notices.js',
			[],
			EDFGF_VERSION,
			$footer_defer
		);

		wp_localize_script( 'edfgf-notices', 'EDFGF_NOTICE', [
			'url'   => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'edfgf_dismiss_overdue_notice' ),
		] );
	}
}
