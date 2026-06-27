<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Admin menu - under Gravity Forms › Forms, just below Entries
// ════════════════════════════════════════════════════════════════
/**
 * Register the page as a sub-item of the Gravity Forms "Forms" menu. GF renders
 * gform_addon_navigation items immediately after Entries, which is where we want
 * it. Routing for these items is handled by GF at admin.php?page=<name>.
 */
add_filter( 'gform_addon_navigation', 'edfgf_register_gf_menu' );
function edfgf_register_gf_menu( $menu_items ) {
	$menu_items   = is_array( $menu_items ) ? $menu_items : [];
	$menu_items[] = [
		'name'       => 'edfgf-entry-digest',
		'label'      => __( 'Entry Digest', 'entry-digest-for-gravity-forms' ),
		'callback'   => 'edfgf_admin_router',
		'permission' => apply_filters( 'edfgf_menu_capability', 'manage_options' ),
	];
	return $menu_items;
}

/**
 * Fallback: if Gravity Forms isn't active there is no Forms menu, so register the
 * page under Tools instead so settings are never orphaned.
 */
add_action( 'admin_menu', function () {
	if ( ! class_exists( 'GFForms' ) ) {
		add_management_page(
			__( 'Entry Digest', 'entry-digest-for-gravity-forms' ),
			__( 'Entry Digest', 'entry-digest-for-gravity-forms' ),
			apply_filters( 'edfgf_menu_capability', 'manage_options' ),
			'edfgf-entry-digest',
			'edfgf_admin_router'
		);
	}
} );

/**
 * Base URL for our admin page - admin.php when under the GF menu, tools.php when
 * on the Tools fallback.
 */
function edfgf_page_url(): string {
	$parent = class_exists( 'GFForms' ) ? 'admin.php' : 'tools.php';
	return admin_url( $parent . '?page=edfgf-entry-digest' );
}

/**
 * Process a digest save early, on admin_init (before any output), so we can then
 * redirect to the saved digest's editor - post/redirect/get. This makes the
 * test-send field (which needs a saved id) available immediately and stops a
 * refresh from resubmitting the form.
 */
add_action( 'admin_init', 'edfgf_handle_save_request' );
function edfgf_handle_save_request(): void {
	if ( ! isset( $_POST['edfgf_save_digest'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'edfgf_save_digest' );

	$saved = edfgf_handle_save();
	wp_safe_redirect( add_query_arg(
		[
			'action'       => 'edit',
			'digest'       => rawurlencode( $saved['id'] ),
			'edfgf_saved' => $saved['is_new'] ? 'created' : 'updated',
		],
		edfgf_page_url()
	) );
	exit;
}

/**
 * Handle a duplicate-digest request early on admin_init so we can redirect to
 * the new digest's editor immediately (post/redirect/get). The copy gets a fresh
 * id and label suffixed with " (copy)"; paused state and one-time date are
 * cleared so the duplicate starts active and schedule-clean.
 */
add_action( 'admin_init', 'edfgf_handle_duplicate_request' );
function edfgf_handle_duplicate_request(): void {
	if ( ! isset( $_POST['edfgf_duplicate_digest'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'edfgf_duplicate_digest' );

	$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
	$source  = edfgf_get_digest( $id );
	if ( ! $source ) {
		return;
	}

	$digests = edfgf_get_digests();
	$new_id  = edfgf_new_id();

	$copy              = $source;
	$copy['id']        = $new_id;
	/* translators: %s: the original digest's name. */
	$copy['label']     = sprintf( __( '%s (copy)', 'entry-digest-for-gravity-forms' ), $source['label'] ?: __( 'Untitled digest', 'entry-digest-for-gravity-forms' ) );
	$copy['paused']    = false; // always start active
	$copy['onetime_at'] = '';   // don't inherit a one-time date that may have already passed

	$digests[ $new_id ] = edfgf_normalize_digest( $copy, $new_id );
	edfgf_save_digests( $digests );

	wp_safe_redirect( add_query_arg(
		[
			'action'       => 'edit',
			'digest'       => rawurlencode( $new_id ),
			'edfgf_saved'  => 'created',
		],
		edfgf_page_url()
	) );
	exit;
}


function edfgf_admin_router(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'entry-digest-for-gravity-forms' ) );
	}

	// ── Handle POSTs ──────────────────────────────────────────────
	$notice = '';

	// Success notice carried over from the post-save redirect below (display only).
	if ( isset( $_GET['edfgf_saved'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own redirect; performs no action.
		$saved_flag = sanitize_key( wp_unslash( $_GET['edfgf_saved'] ) );
		$notice     = edfgf_notice( 'created' === $saved_flag
			? __( 'Digest created.', 'entry-digest-for-gravity-forms' )
			: __( 'Digest saved.', 'entry-digest-for-gravity-forms' )
		);
	}

	if ( isset( $_POST['edfgf_delete_digest'] ) && check_admin_referer( 'edfgf_delete_digest' ) ) {
		$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$digests = edfgf_get_digests();
		unset( $digests[ $id ] );
		edfgf_save_digests( $digests );
		$notice = edfgf_notice( __( 'Digest deleted.', 'entry-digest-for-gravity-forms' ) );
	} elseif ( isset( $_POST['edfgf_send_now'] ) && check_admin_referer( 'edfgf_send_now' ) ) {
		$id = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		// For a one-time-only digest, send a test using its one-time window; the
		// stored date is left intact (manual sends never clear the schedule).
		$d_now = edfgf_get_digest( $id );
		$mode  = ( $d_now && 'none' === ( $d_now['frequency'] ?? 'weekly' ) ) ? 'once' : 'recurring';
		edfgf_run_digest( $id, $mode, [ 'context' => 'manual' ] );
		$d      = edfgf_get_digest( $id );
		$rcpts  = $d ? edfgf_resolve_recipients( $d ) : [];
		$notice = edfgf_notice( sprintf(
			/* translators: %s: comma-separated list of recipient email addresses. */
			__( '&#10003; Digest triggered. Check %s.', 'entry-digest-for-gravity-forms' ),
			'<strong>' . esc_html( implode( ', ', $rcpts ) ) . '</strong>'
		) );
	} elseif ( isset( $_POST['edfgf_send_test'] ) && check_admin_referer( 'edfgf_send_test' ) ) {
		// Test send: deliver the saved digest to one ad-hoc address only. The real
		// recipient list is never contacted, and no schedule is changed.
		$id    = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
		$d_t   = edfgf_get_digest( $id );
		if ( ! $d_t ) {
			$notice = edfgf_notice( __( 'Digest not found.', 'entry-digest-for-gravity-forms' ), 'error' );
		} elseif ( ! is_email( $email ) ) {
			$notice = edfgf_notice( __( 'Enter a valid email address for the test send.', 'entry-digest-for-gravity-forms' ), 'error' );
		} else {
			$mode = ( 'none' === ( $d_t['frequency'] ?? 'weekly' ) ) ? 'once' : 'recurring';
			edfgf_run_digest( $id, $mode, [ 'override_to' => [ $email ], 'context' => 'test' ] );
			$notice = edfgf_notice( sprintf(
				/* translators: %s: the email address the test was sent to. */
				__( '&#10003; Test digest sent to %s. Your real recipient list was not contacted.', 'entry-digest-for-gravity-forms' ),
				'<strong>' . esc_html( $email ) . '</strong>'
			) );
		}
	} elseif ( isset( $_POST['edfgf_toggle_pause'] ) && check_admin_referer( 'edfgf_toggle_pause' ) ) {
		// Flip a digest's paused state. Saving re-syncs cron, so a paused digest
		// loses its scheduled events and a resumed one gets them back.
		$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$digests = edfgf_get_digests();
		if ( isset( $digests[ $id ] ) ) {
			$now_paused             = empty( $digests[ $id ]['paused'] );
			$digests[ $id ]['paused'] = $now_paused;
			edfgf_save_digests( $digests );
			$notice = edfgf_notice( $now_paused
				? __( 'Digest paused - scheduled sends are stopped until you resume it.', 'entry-digest-for-gravity-forms' )
				: __( 'Digest resumed - its schedule is active again.', 'entry-digest-for-gravity-forms' )
			);
		}
	} elseif ( isset( $_POST['edfgf_clear_log'] ) && check_admin_referer( 'edfgf_clear_log' ) ) {
		edfgf_clear_log();
		$notice = edfgf_notice( __( 'Send log cleared.', 'entry-digest-for-gravity-forms' ) );
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

	if ( 'edit' === $action || 'new' === $action ) {
		edfgf_render_editor( $action, $notice );
	} else {
		edfgf_render_list( $notice );
	}
}

function edfgf_notice( string $html, string $type = 'success' ): string {
	return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . $html . '</p></div>';
}
