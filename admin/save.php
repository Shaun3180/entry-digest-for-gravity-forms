<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize + save one digest from $_POST.
 *
 * @return array{id:string,is_new:bool} The saved digest id and whether it was newly created.
 */
function dsagfe_handle_save(): array {
	// Nonce already verified by check_admin_referer() in menu.php before this function is called.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked upstream; individual values sanitized below.
	$raw = wp_unslash( (array) ( $_POST['dsagfe_digest'] ?? [] ) );

	$digests = dsagfe_get_digests();
	$id      = sanitize_text_field( $raw['id'] ?? '' );
	$is_new  = ( '' === $id || ! isset( $digests[ $id ] ) );
	if ( $is_new ) {
		$id = dsagfe_new_id();
	}

	$def = dsagfe_digest_defaults();
	$d   = [ 'id' => $id ];

	// Pause state isn't an editor field - it's toggled from the list - so carry the
	// existing value through a save instead of letting it reset to the default.
	$d['paused'] = ! $is_new && ! empty( $digests[ $id ]['paused'] );

	$d['label']         = sanitize_text_field( $raw['label'] ?? $def['label'] ) ?: $def['label'];
	$d['email_subject'] = sanitize_text_field( $raw['email_subject'] ?? $def['email_subject'] ) ?: $def['email_subject'];

	// Recipients.
	$emails = array_values( array_filter(
		array_map( static function ( string $e ): string {
			$e = sanitize_email( trim( $e ) );
			return is_email( $e ) ? $e : '';
		}, explode( ',', (string) ( $raw['to_email'] ?? '' ) ) )
	) );
	$d['to_email'] = implode( ', ', $emails );

	// Forms - one per digest in core; the Pro add-on enables aggregating several.
	$form_ids = array_values( array_unique( array_map( 'intval', (array) ( $raw['form_ids'] ?? [] ) ) ) );
	$form_ids = array_values( array_filter( $form_ids, static fn( $f ) => $f > 0 ) );
	if ( empty( $form_ids ) ) {
		$form_ids = [ 1 ];
	}
	$d['form_ids'] = $form_ids;

	// Schedule.
	$d['frequency'] = in_array( $raw['frequency'] ?? '', [ 'daily', 'weekly', 'none' ], true ) ? $raw['frequency'] : $def['frequency'];
	$valid_days     = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
	$d['send_day']  = in_array( $raw['send_day'] ?? '', $valid_days, true ) ? $raw['send_day'] : $def['send_day'];
	$time           = sanitize_text_field( $raw['send_time'] ?? $def['send_time'] );
	$d['send_time'] = preg_match( '/^\d{2}:\d{2}$/', $time ) ? $time : $def['send_time'];

	// One-time send. The datetime-local field arrives as 'Y-m-d\TH:i'; we store
	// 'Y-m-d H:i'. Only keep it if it's a real, still-future moment.
	$d['onetime_at'] = '';
	$onetime_raw     = str_replace( 'T', ' ', sanitize_text_field( $raw['onetime_at'] ?? '' ) );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $onetime_raw ) ) {
		$dt = DateTime::createFromFormat( 'Y-m-d H:i', $onetime_raw, wp_timezone() );
		if ( $dt && $dt->getTimestamp() > time() ) {
			$d['onetime_at'] = $onetime_raw;
		}
	}
	$d['onetime_lookback_days'] = max( 0, (int) ( $raw['onetime_lookback_days'] ?? 0 ) );

	// A digest must do something: with no recurring schedule and no pending
	// one-time date, fall back to weekly so it doesn't silently never send.
	if ( 'none' === $d['frequency'] && '' === $d['onetime_at'] ) {
		$d['frequency'] = 'weekly';
	}

	// Quiet-period behavior.
	$d['quiet_behavior'] = in_array( $raw['quiet_behavior'] ?? '', [ 'send', 'skip' ], true ) ? $raw['quiet_behavior'] : $def['quiet_behavior'];

	// Whether to link each entry row to the WP admin (checkbox: absent = off).
	$d['link_entries'] = ! empty( $raw['link_entries'] );

	// Per-form fields.
	$fields_in = (array) ( $raw['fields'] ?? [] );
	$fields    = [];
	foreach ( $form_ids as $fid ) {
		$sel = (array) ( $fields_in[ (string) $fid ] ?? [] );
		$sel = array_values( array_filter( array_map( static function ( $k ): string {
			return preg_match( '/^\d+(\.\d+)?$/', (string) $k ) ? (string) $k : '';
		}, $sel ) ) );
		if ( $sel ) {
			$fields[ (string) $fid ] = $sel;
		}
	}
	$d['fields'] = $fields;

	/**
	 * Filter the sanitized digest array before it is saved, giving add-ons a
	 * chance to add their own settings (role recipients, conditional filters,
	 * attachment format) parsed from the submitted form.
	 *
	 * @param array $d        The sanitized digest configuration so far.
	 * @param array $raw      The unslashed, raw submitted dsagfe_digest array.
	 * @param int[] $form_ids The form IDs selected for this digest.
	 */
	$d = (array) apply_filters( 'dsagfe_save_digest', $d, $raw, $form_ids );

	$digests[ $id ] = dsagfe_normalize_digest( $d, $id );
	dsagfe_save_digests( $digests );

	// Return the saved id so the router can redirect to its editor (post/redirect/get).
	return [ 'id' => $id, 'is_new' => $is_new ];
}
