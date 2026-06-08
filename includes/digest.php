<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Field map — shared by the digest builder and the admin field picker
// ════════════════════════════════════════════════════════════════
/**
 * Build an ordered [ key => label ] map of exportable fields for a form.
 */
function dsagfe_build_field_map( array $form ): array {
	$single_value_types = [ 'date', 'time', 'list', 'fileupload', 'post_image', 'signature' ];
	$field_map          = [];

	foreach ( $form['fields'] as $field ) {
		if ( in_array( $field->type, [ 'html', 'section', 'page', 'captcha' ], true ) ) {
			continue;
		}
		if ( ! empty( $field->inputs ) && ! in_array( $field->type, $single_value_types, true ) ) {
			foreach ( $field->inputs as $input ) {
				if ( ! empty( $input['isHidden'] ) ) {
					continue;
				}
				$field_map[ (string) $input['id'] ] = trim( $field->label . ' — ' . $input['label'] );
			}
		} else {
			$field_map[ (string) $field->id ] = (string) $field->label;
		}
	}
	return $field_map;
}

/**
 * Filter a field map down to the selected keys (empty selection = all).
 */
function dsagfe_filter_field_map( array $field_map, array $include_fields ): array {
	if ( empty( $include_fields ) ) {
		return $field_map;
	}
	$filtered = [];
	foreach ( $field_map as $key => $label ) {
		if ( in_array( (string) $key, array_map( 'strval', $include_fields ), true ) ) {
			$filtered[ $key ] = $label;
		}
	}
	return $filtered ?: $field_map;
}
// ════════════════════════════════════════════════════════════════
//  Recipient routing (Pro adds role-based recipients)
// ════════════════════════════════════════════════════════════════
/**
 * Resolve the final recipient list for a digest: explicit emails plus, on Pro,
 * the account emails of all users in the selected roles.
 *
 * @return string[] De-duplicated, validated email addresses.
 */
function dsagfe_resolve_recipients( array $d ): array {
	$emails = array_map( 'trim', explode( ',', (string) ( $d['to_email'] ?? '' ) ) );

	if ( dsagfe_is_pro() && ! empty( $d['roles'] ) ) {
		$users = get_users( [
			'role__in' => $d['roles'],
			'fields'   => [ 'user_email' ],
		] );
		foreach ( $users as $u ) {
			$emails[] = $u->user_email;
		}
	}

	$emails = array_values( array_unique( array_filter(
		array_map( 'trim', $emails ),
		static fn( $e ) => is_email( $e )
	) ) );

	return $emails;
}
// ════════════════════════════════════════════════════════════════
//  Main run
// ════════════════════════════════════════════════════════════════
function dsagfe_run_all_active(): void {
	foreach ( array_keys( dsagfe_active_digests() ) as $id ) {
		dsagfe_run_digest( (string) $id );
	}
}

/**
 * Build and send one digest.
 */
function dsagfe_run_digest( string $digest_id ): void {
	if ( ! class_exists( 'GFAPI' ) ) {
		error_log( 'Entry Digest: Gravity Forms is not active.' );
		return;
	}

	$d = dsagfe_get_digest( $digest_id );
	if ( ! $d ) {
		error_log( 'Entry Digest: digest "' . $digest_id . '" not found.' );
		return;
	}

	$is_pro = dsagfe_is_pro();

	// Enforce free-tier limits at runtime too (belt and suspenders).
	$form_ids = $is_pro ? $d['form_ids'] : array_slice( $d['form_ids'], 0, 1 );

	$recipients = dsagfe_resolve_recipients( $d );
	if ( empty( $recipients ) ) {
		error_log( 'Entry Digest: no valid recipients for digest "' . $digest_id . '".' );
		return;
	}
	$to      = implode( ', ', $recipients );
	$subject = ! empty( $d['email_subject'] ) ? $d['email_subject'] : 'Your Gravity Forms entry digest';

	// Reporting window (daily = 1 day, weekly = 7 days).
	$days_back  = ( 'daily' === $d['frequency'] ) ? 1 : 7;
	$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) );
	$end_date   = gmdate( 'Y-m-d H:i:s' );

	$search_criteria = [
		'status'     => 'active',
		'start_date' => $start_date,
		'end_date'   => $end_date,
	];
	$paging = [ 'offset' => 0, 'page_size' => 2000 ];

	// Build per-form sections.
	$sections    = [];
	$total_count = 0;
	foreach ( $form_ids as $fid ) {
		$fid  = (int) $fid;
		$form = GFAPI::get_form( $fid );
		if ( ! $form ) {
			error_log( 'Entry Digest: form ID ' . $fid . ' not found (digest ' . $digest_id . ').' );
			continue;
		}

		$entries = GFAPI::get_entries( $fid, $search_criteria, null, $paging );
		if ( is_wp_error( $entries ) ) {
			error_log( 'Entry Digest: ' . $entries->get_error_message() );
			continue;
		}
		$entries = is_array( $entries ) ? $entries : [];

		// Conditional filtering (Pro).
		if ( $is_pro && ! empty( $d['filters'][ (string) $fid ]['rules'] ) ) {
			$logic   = ( 'any' === ( $d['filters'][ (string) $fid ]['logic'] ?? 'all' ) ) ? 'any' : 'all';
			$rules   = $d['filters'][ (string) $fid ]['rules'];
			$entries = array_values( array_filter(
				$entries,
				static fn( $e ) => dsagfe_entry_matches( $e, $rules, $logic )
			) );
		}

		$field_map = dsagfe_filter_field_map(
			dsagfe_build_field_map( $form ),
			(array) ( $d['fields'][ (string) $fid ] ?? [] )
		);

		$sections[] = [
			'form'      => $form,
			'entries'   => $entries,
			'field_map' => $field_map,
			'count'     => count( $entries ),
		];
		$total_count += count( $entries );
	}

	if ( empty( $sections ) ) {
		error_log( 'Entry Digest: no valid forms for digest "' . $digest_id . '".' );
		return;
	}

	// Compose HTML.
	$html = dsagfe_build_digest_html( $sections, $d, $total_count, $start_date, $end_date );

	// Attachments (Pro).
	$attachments = [];
	$tmp_files   = [];
	$format      = $is_pro ? $d['attach_format'] : 'none';
	if ( 'none' !== $format && $total_count > 0 ) {
		$tmp_files   = dsagfe_build_attachments( $format, $sections );
		$attachments = $tmp_files;
	}

	$sent = wp_mail(
		$to,
		$subject,
		$html,
		[ 'Content-Type: text/html; charset=UTF-8' ],
		$attachments
	);

	foreach ( $tmp_files as $f ) {
		if ( $f && file_exists( $f ) ) {
			@unlink( $f );
		}
	}
	if ( ! $sent ) {
		error_log( 'Entry Digest: wp_mail() failed for digest "' . $digest_id . '". Check site mail configuration.' );
	}
}
// ════════════════════════════════════════════════════════════════
//  Live entry-count preview (admin editor)
// ════════════════════════════════════════════════════════════════
/**
 * Count active entries that would currently be included, per form, using the
 * same rolling window as a real run (daily = 24h, weekly = 7 days).
 *
 * @param int[]  $form_ids  Selected form IDs.
 * @param string $frequency 'daily' | 'weekly'.
 * @param array  $filters   Pro filter map: form_id => [ 'logic' => .., 'rules' => [..] ].
 * @param bool   $is_pro    Whether Pro features apply.
 *
 * @return array{per_form: array<string,int>, total: int, days_back: int, window: string}
 */
function dsagfe_count_entries_for( array $form_ids, string $frequency, array $filters, bool $is_pro ): array {
	$days_back  = ( 'daily' === $frequency ) ? 1 : 7;
	$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) );
	$end_date   = gmdate( 'Y-m-d H:i:s' );
	$search     = [ 'status' => 'active', 'start_date' => $start_date, 'end_date' => $end_date ];

	if ( ! $is_pro ) {
		$form_ids = array_slice( $form_ids, 0, 1 );
	}

	$per_form = [];
	$total    = 0;
	foreach ( $form_ids as $fid ) {
		$fid   = (int) $fid;
		$rules = ( $is_pro && ! empty( $filters[ (string) $fid ]['rules'] ) ) ? $filters[ (string) $fid ]['rules'] : [];

		if ( empty( $rules ) ) {
			$count = (int) GFAPI::get_entry_count( $fid, $search );
		} else {
			$logic   = ( 'any' === ( $filters[ (string) $fid ]['logic'] ?? 'all' ) ) ? 'any' : 'all';
			$entries = GFAPI::get_entries( $fid, $search, null, [ 'offset' => 0, 'page_size' => 2000 ] );
			$entries = is_array( $entries ) ? $entries : [];
			$count   = 0;
			foreach ( $entries as $e ) {
				if ( dsagfe_entry_matches( $e, $rules, $logic ) ) {
					$count++;
				}
			}
		}

		$per_form[ (string) $fid ] = $count;
		$total                    += $count;
	}

	return [
		'per_form'  => $per_form,
		'total'     => $total,
		'days_back' => $days_back,
		'window'    => ( 1 === $days_back ) ? 'past 24 hours' : 'past 7 days',
	];
}
