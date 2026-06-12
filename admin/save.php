<?php
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize + save one digest from $_POST.
 */
function dsagfe_handle_save(): string {
	$is_pro = dsagfe_is_pro();
	// Nonce already verified by check_admin_referer() in menu.php before this function is called.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked upstream; individual values sanitized below.
	$raw = wp_unslash( (array) ( $_POST['dsagfe_digest'] ?? [] ) );

	$digests = dsagfe_get_digests();
	$id      = sanitize_text_field( $raw['id'] ?? '' );
	$is_new  = ( '' === $id || ! isset( $digests[ $id ] ) );
	if ( $is_new ) {
		$id = dsagfe_new_id();
	}

	// Free-tier guard: block creating a 2nd digest.
	if ( $is_new && ! $is_pro && count( $digests ) >= DSAGFE_FREE_DIGEST_LIMIT ) {
		return dsagfe_notice( sprintf(
			/* translators: 1: opening anchor tag to the upgrade page; 2: closing anchor tag. */
			__( 'The free plan includes one digest. %1$sUpgrade to Pro%2$s for unlimited digests.', 'entry-digest-for-gravity-forms' ),
			'<a href="' . esc_url( dsagfe_upgrade_url() ) . '">',
			'</a>'
		), 'warning' );
	}

	$def = dsagfe_digest_defaults();
	$d   = [ 'id' => $id ];

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

	// Roles (Pro only).
	$d['roles'] = ( $is_pro && ! empty( $raw['roles'] ) )
		? array_values( array_filter( array_map( 'sanitize_key', (array) $raw['roles'] ) ) )
		: [];

	// Forms — free is capped to one.
	$form_ids = array_values( array_unique( array_map( 'intval', (array) ( $raw['form_ids'] ?? [] ) ) ) );
	$form_ids = array_values( array_filter( $form_ids, static fn( $f ) => $f > 0 ) );
	if ( empty( $form_ids ) ) {
		$form_ids = [ 1 ];
	}
	if ( ! $is_pro ) {
		$form_ids = array_slice( $form_ids, 0, 1 );
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

	// Per-form filters (Pro only).
	$filters = [];
	if ( $is_pro ) {
		$filters_in = (array) ( $raw['filters'] ?? [] );
		$logic_in   = (array) ( $raw['filter_logic'] ?? [] );
		$ops        = array_keys( dsagfe_filter_operators() );
		foreach ( $form_ids as $fid ) {
			$rules_raw = (array) ( $filters_in[ (string) $fid ] ?? [] );
			$rules     = [];
			foreach ( $rules_raw as $r ) {
				$field = (string) ( $r['field'] ?? '' );
				$op    = (string) ( $r['op'] ?? '' );
				if ( '' === $field || ! in_array( $op, $ops, true ) ) {
					continue;
				}
				$rules[] = [
					'field' => preg_match( '/^\d+(\.\d+)?$/', $field ) ? $field : '',
					'op'    => $op,
					'value' => sanitize_text_field( (string) ( $r['value'] ?? '' ) ),
				];
			}
			$rules = array_values( array_filter( $rules, static fn( $r ) => '' !== $r['field'] ) );
			if ( $rules ) {
				$logic = ( 'any' === ( $logic_in[ (string) $fid ] ?? 'all' ) ) ? 'any' : 'all';
				$filters[ (string) $fid ] = [ 'logic' => $logic, 'rules' => $rules ];
			}
		}
	}
	$d['filters'] = $filters;

	// Attachment (Pro only).
	$fmt = $raw['attach_format'] ?? 'none';
	$d['attach_format'] = ( $is_pro && in_array( $fmt, [ 'none', 'xlsx', 'csv' ], true ) ) ? $fmt : 'none';

	$digests[ $id ] = dsagfe_normalize_digest( $d, $id );
	dsagfe_save_digests( $digests );

	return dsagfe_notice( $is_new
		? __( 'Digest created.', 'entry-digest-for-gravity-forms' )
		: __( 'Digest saved.', 'entry-digest-for-gravity-forms' )
	);
}
