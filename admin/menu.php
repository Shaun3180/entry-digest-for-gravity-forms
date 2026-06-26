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
add_filter( 'gform_addon_navigation', 'dsagfe_register_gf_menu' );
function dsagfe_register_gf_menu( $menu_items ) {
	$menu_items   = is_array( $menu_items ) ? $menu_items : [];
	$menu_items[] = [
		'name'       => 'dsagfe-entry-digest',
		'label'      => __( 'Entry Digest', 'entry-digest-for-gravity-forms' ),
		'callback'   => 'dsagfe_admin_router',
		'permission' => apply_filters( 'dsagfe_menu_capability', 'manage_options' ),
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
			apply_filters( 'dsagfe_menu_capability', 'manage_options' ),
			'dsagfe-entry-digest',
			'dsagfe_admin_router'
		);
	}
} );

/**
 * Base URL for our admin page - admin.php when under the GF menu, tools.php when
 * on the Tools fallback.
 */
function dsagfe_page_url(): string {
	$parent = class_exists( 'GFForms' ) ? 'admin.php' : 'tools.php';
	return admin_url( $parent . '?page=dsagfe-entry-digest' );
}

/**
 * Process a digest save early, on admin_init (before any output), so we can then
 * redirect to the saved digest's editor - post/redirect/get. This makes the
 * test-send field (which needs a saved id) available immediately and stops a
 * refresh from resubmitting the form.
 */
add_action( 'admin_init', 'dsagfe_handle_save_request' );
function dsagfe_handle_save_request(): void {
	if ( ! isset( $_POST['dsagfe_save_digest'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'dsagfe_save_digest' );

	$saved = dsagfe_handle_save();
	wp_safe_redirect( add_query_arg(
		[
			'action'       => 'edit',
			'digest'       => rawurlencode( $saved['id'] ),
			'dsagfe_saved' => $saved['is_new'] ? 'created' : 'updated',
		],
		dsagfe_page_url()
	) );
	exit;
}

/**
 * Route the admin page between the digest list and the editor, handling POSTs.
 */
function dsagfe_admin_router(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'entry-digest-for-gravity-forms' ) );
	}

	// ── Handle POSTs ──────────────────────────────────────────────
	$notice = '';

	// Success notice carried over from the post-save redirect below (display only).
	if ( isset( $_GET['dsagfe_saved'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag set by our own redirect; performs no action.
		$saved_flag = sanitize_key( wp_unslash( $_GET['dsagfe_saved'] ) );
		$notice     = dsagfe_notice( 'created' === $saved_flag
			? __( 'Digest created.', 'entry-digest-for-gravity-forms' )
			: __( 'Digest saved.', 'entry-digest-for-gravity-forms' )
		);
	}

	if ( isset( $_POST['dsagfe_delete_digest'] ) && check_admin_referer( 'dsagfe_delete_digest' ) ) {
		$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$digests = dsagfe_get_digests();
		unset( $digests[ $id ] );
		dsagfe_save_digests( $digests );
		$notice = dsagfe_notice( __( 'Digest deleted.', 'entry-digest-for-gravity-forms' ) );
	} elseif ( isset( $_POST['dsagfe_send_now'] ) && check_admin_referer( 'dsagfe_send_now' ) ) {
		$id = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		// For a one-time-only digest, send a test using its one-time window; the
		// stored date is left intact (manual sends never clear the schedule).
		$d_now = dsagfe_get_digest( $id );
		$mode  = ( $d_now && 'none' === ( $d_now['frequency'] ?? 'weekly' ) ) ? 'once' : 'recurring';
		dsagfe_run_digest( $id, $mode, [ 'context' => 'manual' ] );
		$d      = dsagfe_get_digest( $id );
		$rcpts  = $d ? dsagfe_resolve_recipients( $d ) : [];
		$notice = dsagfe_notice( sprintf(
			/* translators: %s: comma-separated list of recipient email addresses. */
			__( '&#10003; Digest triggered. Check %s.', 'entry-digest-for-gravity-forms' ),
			'<strong>' . esc_html( implode( ', ', $rcpts ) ) . '</strong>'
		) );
	} elseif ( isset( $_POST['dsagfe_send_test'] ) && check_admin_referer( 'dsagfe_send_test' ) ) {
		// Test send: deliver the saved digest to one ad-hoc address only. The real
		// recipient list is never contacted, and no schedule is changed.
		$id    = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['test_email'] ?? '' ) );
		$d_t   = dsagfe_get_digest( $id );
		if ( ! $d_t ) {
			$notice = dsagfe_notice( __( 'Digest not found.', 'entry-digest-for-gravity-forms' ), 'error' );
		} elseif ( ! is_email( $email ) ) {
			$notice = dsagfe_notice( __( 'Enter a valid email address for the test send.', 'entry-digest-for-gravity-forms' ), 'error' );
		} else {
			$mode = ( 'none' === ( $d_t['frequency'] ?? 'weekly' ) ) ? 'once' : 'recurring';
			dsagfe_run_digest( $id, $mode, [ 'override_to' => [ $email ], 'context' => 'test' ] );
			$notice = dsagfe_notice( sprintf(
				/* translators: %s: the email address the test was sent to. */
				__( '&#10003; Test digest sent to %s. Your real recipient list was not contacted.', 'entry-digest-for-gravity-forms' ),
				'<strong>' . esc_html( $email ) . '</strong>'
			) );
		}
	} elseif ( isset( $_POST['dsagfe_toggle_pause'] ) && check_admin_referer( 'dsagfe_toggle_pause' ) ) {
		// Flip a digest's paused state. Saving re-syncs cron, so a paused digest
		// loses its scheduled events and a resumed one gets them back.
		$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$digests = dsagfe_get_digests();
		if ( isset( $digests[ $id ] ) ) {
			$now_paused             = empty( $digests[ $id ]['paused'] );
			$digests[ $id ]['paused'] = $now_paused;
			dsagfe_save_digests( $digests );
			$notice = dsagfe_notice( $now_paused
				? __( 'Digest paused - scheduled sends are stopped until you resume it.', 'entry-digest-for-gravity-forms' )
				: __( 'Digest resumed - its schedule is active again.', 'entry-digest-for-gravity-forms' )
			);
		}
	} elseif ( isset( $_POST['dsagfe_clear_log'] ) && check_admin_referer( 'dsagfe_clear_log' ) ) {
		dsagfe_clear_log();
		$notice = dsagfe_notice( __( 'Send log cleared.', 'entry-digest-for-gravity-forms' ) );
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

	if ( 'edit' === $action || 'new' === $action ) {
		dsagfe_render_editor( $action, $notice );
	} else {
		dsagfe_render_list( $notice );
	}
}

function dsagfe_notice( string $html, string $type = 'success' ): string {
	return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . $html . '</p></div>';
}
