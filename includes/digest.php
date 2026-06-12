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
 * Whole days since the oldest of the given forms was created, per Gravity
 * Forms' own date_created (stored in UTC). Used to prefill a sensible default
 * lookback window for one-time sends. Returns 0 when it can't be determined.
 *
 * @param int[] $form_ids
 */
function dsagfe_forms_age_days( array $form_ids ): int {
	if ( ! class_exists( 'GFAPI' ) ) {
		return 0;
	}
	$oldest = 0; // Unix timestamp of the earliest creation date seen.
	foreach ( $form_ids as $fid ) {
		$created = '';
		$form    = GFAPI::get_form( (int) $fid );
		if ( is_array( $form ) && ! empty( $form['date_created'] ) ) {
			$created = (string) $form['date_created'];
		} elseif ( class_exists( 'GFFormsModel' ) && method_exists( 'GFFormsModel', 'get_form' ) ) {
			$row = GFFormsModel::get_form( (int) $fid );
			if ( $row && ! empty( $row->date_created ) ) {
				$created = (string) $row->date_created;
			}
		}
		if ( '' === $created ) {
			continue;
		}
		$ts = strtotime( $created . ' UTC' );
		if ( $ts && ( 0 === $oldest || $ts < $oldest ) ) {
			$oldest = $ts;
		}
	}
	if ( 0 === $oldest ) {
		return 0;
	}
	return max( 0, (int) floor( ( time() - $oldest ) / DAY_IN_SECONDS ) );
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
 *
 * @param string $digest_id Digest id.
 * @param string $mode      'recurring' (rolling daily/weekly window) or 'once'
 *                          (the digest's configured one-time lookback window).
 */
function dsagfe_run_digest( string $digest_id, string $mode = 'recurring' ): void {
	if ( ! class_exists( 'GFAPI' ) ) {
		return;
	}

	$d = dsagfe_get_digest( $digest_id );
	if ( ! $d ) {
		return;
	}

	$is_pro = dsagfe_is_pro();

	// Enforce free-tier limits at runtime too (belt and suspenders).
	$form_ids = $is_pro ? $d['form_ids'] : array_slice( $d['form_ids'], 0, 1 );

	$recipients = dsagfe_resolve_recipients( $d );
	if ( empty( $recipients ) ) {
		return;
	}
	$to      = implode( ', ', $recipients );
	$subject = ! empty( $d['email_subject'] ) ? $d['email_subject'] : __( 'Your Gravity Forms entry digest', 'entry-digest-for-gravity-forms' );

	// Reporting window. Recurring runs use the rolling daily (1 day) / weekly
	// (7 days) window. A one-time send uses its configured lookback; a lookback
	// of 0 means "everything up to now" (no lower bound).
	$end_date = gmdate( 'Y-m-d H:i:s' );
	if ( 'once' === $mode ) {
		$lookback   = max( 0, (int) ( $d['onetime_lookback_days'] ?? 0 ) );
		$start_date = ( $lookback > 0 )
			? gmdate( 'Y-m-d H:i:s', strtotime( '-' . $lookback . ' days' ) )
			: '2000-01-01 00:00:00';
	} else {
		$days_back  = ( 'daily' === $d['frequency'] ) ? 1 : 7;
		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) );
	}

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
			continue;
		}

		$entries = GFAPI::get_entries( $fid, $search_criteria, null, $paging );
		if ( is_wp_error( $entries ) ) {
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
		return;
	}

	// Graceful when quiet: by default a 0-entry period still sends a tidy
	// "no new entries" note (never radio silence). Sites that prefer silence
	// can opt out per digest by setting quiet_behavior to 'skip'.
	if ( 0 === $total_count && 'skip' === ( $d['quiet_behavior'] ?? 'send' ) ) {
		return;
	}

	// Compose HTML.
	$html = dsagfe_build_digest_html( $sections, $d, $total_count, $start_date, $end_date, $mode );

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
			wp_delete_file( $f );
		}
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
function dsagfe_count_entries_for( array $form_ids, string $frequency, array $filters, bool $is_pro, ?int $days_back_override = null ): array {
	$end_date = gmdate( 'Y-m-d H:i:s' );

	// A non-null override drives a one-time-style window: N days back, or
	// "everything up to now" when 0.
	if ( null !== $days_back_override ) {
		$days_back  = max( 0, $days_back_override );
		$start_date = ( $days_back > 0 )
			? gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) )
			: '2000-01-01 00:00:00';
		$window = ( $days_back > 0 )
			/* translators: %d: number of days in the lookback window. */
			? sprintf( _n( 'past %d day', 'past %d days', $days_back, 'entry-digest-for-gravity-forms' ), $days_back )
			: __( 'whole history', 'entry-digest-for-gravity-forms' );
	} else {
		$days_back  = ( 'daily' === $frequency ) ? 1 : 7;
		$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) );
		$window     = ( 1 === $days_back )
			? __( 'past 24 hours', 'entry-digest-for-gravity-forms' )
			: __( 'past 7 days', 'entry-digest-for-gravity-forms' );
	}
	$search = [ 'status' => 'active', 'start_date' => $start_date, 'end_date' => $end_date ];

	if ( ! $is_pro ) {
		$form_ids = array_slice( $form_ids, 0, 1 );
	}

	$per_form = [];
	$total    = 0;
	foreach ( $form_ids as $fid ) {
		$fid   = (int) $fid;
		$rules = ( $is_pro && ! empty( $filters[ (string) $fid ]['rules'] ) ) ? $filters[ (string) $fid ]['rules'] : [];

		if ( empty( $rules ) ) {
			// get_entry_count() was added in GF 2.8; fall back for older installs.
			if ( method_exists( 'GFAPI', 'get_entry_count' ) ) {
				$count = (int) GFAPI::get_entry_count( $fid, $search );
			} else {
				$entries = GFAPI::get_entries( $fid, $search, null, [ 'offset' => 0, 'page_size' => 2000 ] );
				$count   = is_array( $entries ) ? count( $entries ) : 0;
			}
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
		'window'    => $window,
	];
}
