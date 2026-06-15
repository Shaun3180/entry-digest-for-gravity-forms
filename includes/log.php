<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  Send log — a capped, rolling history of recent digest sends
// ════════════════════════════════════════════════════════════════
/**
 * Recent sends are recorded in their own option (separate from settings, so the
 * settings blob never bloats) and kept to a small rolling cap. The log powers
 * the "Recent sends" table on the digest list screen: it helps debug delivery
 * problems and reassures clients that scheduled digests are actually running.
 */

define( 'DSAGFE_LOG_OPTION_KEY',  'dsagfe_send_log' );
define( 'DSAGFE_LOG_DEFAULT_MAX', 10 );

/**
 * Number of log records to retain (filterable).
 *
 * The free plugin keeps the most recent few sends — enough to confirm digests
 * are running and to debug a recent problem. The retention count is exposed via
 * the 'dsagfe_log_max' filter, so it is a default, not a hard limit: any site
 * owner (or the optional Pro add-on, which offers a configurable history) can
 * raise it.
 */
function dsagfe_log_max(): int {
	$max = (int) apply_filters( 'dsagfe_log_max', DSAGFE_LOG_DEFAULT_MAX );
	return max( 1, $max );
}

/**
 * The send log, newest first.
 *
 * @return array<int,array>
 */
function dsagfe_get_log(): array {
	$log = get_option( DSAGFE_LOG_OPTION_KEY, [] );
	return is_array( $log ) ? $log : [];
}

/**
 * Append a record to the send log and trim to the retention cap.
 *
 * @param array $record {
 *     @type int    $time       Unix timestamp of the send (defaults to now).
 *     @type string $digest_id  Digest id.
 *     @type string $label      Digest label at send time.
 *     @type int    $count      Total entries included in the send.
 *     @type string $recipients Comma-separated recipients actually used.
 *     @type string $status     'sent' | 'failed' | 'skipped' | 'no_recipients'.
 *     @type string $context    'scheduled' | 'one-time' | 'manual' | 'test'.
 * }
 */
function dsagfe_log_record( array $record ): void {
	$record = wp_parse_args( $record, [
		'time'       => time(),
		'digest_id'  => '',
		'label'      => '',
		'count'      => 0,
		'recipients' => '',
		'status'     => 'sent',
		'context'    => 'scheduled',
	] );

	// Coerce to safe, compact scalars before storing.
	$record['time']       = (int) $record['time'];
	$record['digest_id']  = (string) $record['digest_id'];
	$record['label']      = (string) $record['label'];
	$record['count']      = (int) $record['count'];
	$record['recipients'] = (string) $record['recipients'];
	$record['status']     = (string) $record['status'];
	$record['context']    = (string) $record['context'];

	$log = dsagfe_get_log();
	array_unshift( $log, $record );
	$log = array_slice( $log, 0, dsagfe_log_max() );

	// Non-autoloaded: the log is only read on our admin screen.
	update_option( DSAGFE_LOG_OPTION_KEY, $log, false );
}

/**
 * Empty the send log.
 */
function dsagfe_clear_log(): void {
	delete_option( DSAGFE_LOG_OPTION_KEY );
}

/**
 * Display label + color for a log status.
 *
 * @return array{0:string,1:string} [ label, hex color ]
 */
function dsagfe_log_status_meta( string $status ): array {
	switch ( $status ) {
		case 'sent':
			return [ __( 'Sent', 'entry-digest-for-gravity-forms' ), '#008a20' ];
		case 'failed':
			return [ __( 'Failed', 'entry-digest-for-gravity-forms' ), '#d63638' ];
		case 'skipped':
			return [ __( 'Skipped (quiet period)', 'entry-digest-for-gravity-forms' ), '#996800' ];
		case 'no_recipients':
			return [ __( 'No recipients', 'entry-digest-for-gravity-forms' ), '#d63638' ];
		default:
			return [ ucfirst( $status ), '#50575e' ];
	}
}

/**
 * Display label for a send context.
 */
function dsagfe_log_context_label( string $context ): string {
	switch ( $context ) {
		case 'scheduled':
			return __( 'Scheduled', 'entry-digest-for-gravity-forms' );
		case 'one-time':
			return __( 'One-time', 'entry-digest-for-gravity-forms' );
		case 'manual':
			return __( 'Send Now', 'entry-digest-for-gravity-forms' );
		case 'test':
			return __( 'Test', 'entry-digest-for-gravity-forms' );
		default:
			return ucfirst( $context );
	}
}
