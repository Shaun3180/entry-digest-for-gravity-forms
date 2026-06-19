<?php
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the digest editor's script on the editor screen only.
 *
 * The page lives at admin.php?page=entry-digest (or tools.php?page=entry-digest
 * when Gravity Forms is inactive); the editor is shown for action=new|edit.
 */
add_action( 'admin_enqueue_scripts', 'dsagfe_enqueue_admin_assets' );
function dsagfe_enqueue_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing, no state change.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'entry-digest' !== $page ) {
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing, no state change.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
	if ( 'new' !== $action && 'edit' !== $action ) {
		return;
	}

	wp_enqueue_script(
		'dsagfe-editor',
		DSAGFE_URL . 'admin/js/editor.js',
		[],
		DSAGFE_VERSION,
		true
	);

	wp_localize_script( 'dsagfe-editor', 'DSAGFE_I18N', [
		'calculating' => __( 'Calculating…', 'entry-digest-for-gravity-forms' ),
		'unable'      => __( 'Unable to calculate.', 'entry-digest-for-gravity-forms' ),
		'gfInactive'  => __( 'Gravity Forms is not active - counts unavailable.', 'entry-digest-for-gravity-forms' ),
		'selectForm'  => __( 'Select a form to see a count.', 'entry-digest-for-gravity-forms' ),
		'entry'       => __( 'entry', 'entry-digest-for-gravity-forms' ),
		'entries'     => __( 'entries', 'entry-digest-for-gravity-forms' ),
		'inThe'       => _x( 'in the', 'precedes a time window such as "past 7 days"', 'entry-digest-for-gravity-forms' ),
		'inWord'      => _x( 'in', 'precedes a time window in the per-form count badge', 'entry-digest-for-gravity-forms' ),
	] );

	wp_localize_script( 'dsagfe-editor', 'DSAGFE_COUNT', [
		'url'   => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'dsagfe_entry_count' ),
		'gf'    => class_exists( 'GFAPI' ),
	] );
}
