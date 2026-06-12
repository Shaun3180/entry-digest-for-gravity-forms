<?php
defined( 'ABSPATH' ) || exit;

/**
 * AJAX: return a live entry count for the digest currently being edited.
 * Reads the same POST shape as the editor form (dsagfe_digest[...]).
 */
add_action( 'wp_ajax_dsagfe_entry_count', 'dsagfe_ajax_entry_count' );
function dsagfe_ajax_entry_count(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'entry-digest-for-gravity-forms' ) ], 403 );
	}
	if ( ! check_ajax_referer( 'dsagfe_entry_count', 'nonce', false ) ) {
		wp_send_json_error( [ 'message' => __( 'Security check failed — reload the page.', 'entry-digest-for-gravity-forms' ) ], 400 );
	}
	if ( ! class_exists( 'GFAPI' ) ) {
		wp_send_json_error( [ 'message' => __( 'Gravity Forms is not active.', 'entry-digest-for-gravity-forms' ) ] );
	}

	$is_pro = dsagfe_is_pro();
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_unslash() applied; individual values are sanitized below via sanitize_text_field/intval/etc.
	$raw = wp_unslash( (array) ( $_POST['dsagfe_digest'] ?? [] ) );

	$form_ids = array_values( array_unique( array_map( 'intval', (array) ( $raw['form_ids'] ?? [] ) ) ) );
	$form_ids = array_values( array_filter( $form_ids, static fn( $f ) => $f > 0 ) );
	if ( ! $is_pro ) {
		$form_ids = array_slice( $form_ids, 0, 1 );
	}

	$frequency = in_array( $raw['frequency'] ?? '', [ 'daily', 'weekly', 'none' ], true ) ? $raw['frequency'] : 'weekly';
	$filters   = dsagfe_parse_posted_filters( $raw, $form_ids, $is_pro );

	// With no recurring schedule (one-time only), preview the one-time lookback
	// window instead of a rolling daily/weekly one.
	$override = ( 'none' === $frequency ) ? max( 0, (int) ( $raw['onetime_lookback_days'] ?? 0 ) ) : null;

	$result = dsagfe_count_entries_for( $form_ids, $frequency, $filters, $is_pro, $override );

	$titles = [];
	foreach ( array_keys( $result['per_form'] ) as $fid ) {
		$f = GFAPI::get_form( (int) $fid );
		/* translators: %d: Gravity Forms form ID. */
		$titles[ (string) $fid ] = $f ? $f['title'] : sprintf( __( 'Form %d', 'entry-digest-for-gravity-forms' ), (int) $fid );
	}
	$result['titles'] = $titles;

	wp_send_json_success( $result );
}
