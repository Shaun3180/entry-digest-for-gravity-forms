<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Admin menu — under Gravity Forms › Forms, just below Entries
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
		'name'       => 'entry-digest',
		'label'      => 'Entry Digest',
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
			'Entry Digest',
			'Entry Digest',
			apply_filters( 'dsagfe_menu_capability', 'manage_options' ),
			'entry-digest',
			'dsagfe_admin_router'
		);
	}
} );

/**
 * Base URL for our admin page — admin.php when under the GF menu, tools.php when
 * on the Tools fallback.
 */
function dsagfe_page_url(): string {
	$parent = class_exists( 'GFForms' ) ? 'admin.php' : 'tools.php';
	return admin_url( $parent . '?page=entry-digest' );
}

/**
 * Route the admin page between the digest list and the editor, handling POSTs.
 */
function dsagfe_admin_router(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions.' );
	}

	// ── Handle POSTs ──────────────────────────────────────────────
	$notice = '';

	if ( isset( $_POST['dsagfe_save_digest'] ) && check_admin_referer( 'dsagfe_save_digest' ) ) {
		$notice = dsagfe_handle_save();
	} elseif ( isset( $_POST['dsagfe_delete_digest'] ) && check_admin_referer( 'dsagfe_delete_digest' ) ) {
		$id      = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		$digests = dsagfe_get_digests();
		unset( $digests[ $id ] );
		dsagfe_save_digests( $digests );
		$notice = dsagfe_notice( 'Digest deleted.' );
	} elseif ( isset( $_POST['dsagfe_send_now'] ) && check_admin_referer( 'dsagfe_send_now' ) ) {
		$id = sanitize_text_field( wp_unslash( $_POST['digest_id'] ?? '' ) );
		dsagfe_run_digest( $id );
		$d      = dsagfe_get_digest( $id );
		$rcpts  = $d ? dsagfe_resolve_recipients( $d ) : [];
		$notice = dsagfe_notice( '&#10003; Digest triggered. Check <strong>' . esc_html( implode( ', ', $rcpts ) ) . '</strong>.' );
	}

	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

	if ( 'edit' === $action || 'new' === $action ) {
		dsagfe_render_editor( $action, $notice );
	} else {
		dsagfe_render_list( $notice );
	}
}

function dsagfe_notice( string $html, string $type = 'success' ): string {
	return '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . $html . '</p></div>';
}
