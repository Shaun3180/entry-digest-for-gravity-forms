<?php
defined( 'ABSPATH' ) || exit;

// ── Scheduling helpers ───────────────────────────────────────────
/**
 * Timestamp for the next occurrence of $day at $time (site timezone).
 */
function edfgf_next_weekly_timestamp( string $day, string $time ): int {
	$tz  = wp_timezone();
	$now = new DateTime( 'now', $tz );
	if ( strtolower( $now->format( 'l' ) ) === strtolower( $day ) ) {
		$today_target = new DateTime( 'today ' . $time, $tz );
		if ( $now < $today_target ) {
			return $today_target->getTimestamp();
		}
	}
	return ( new DateTime( 'next ' . $day . ' ' . $time, $tz ) )->getTimestamp();
}

/**
 * Timestamp for the next occurrence of $time today/tomorrow (site timezone).
 */
function edfgf_next_daily_timestamp( string $time ): int {
	$tz    = wp_timezone();
	$now   = new DateTime( 'now', $tz );
	$today = new DateTime( 'today ' . $time, $tz );
	return ( $now < $today )
		? $today->getTimestamp()
		: ( new DateTime( 'tomorrow ' . $time, $tz ) )->getTimestamp();
}

/**
 * Timestamp for a one-time send stored as 'Y-m-d H:i' in the site timezone.
 * Returns 0 when unset or unparseable.
 */
function edfgf_onetime_timestamp( string $onetime_at ): int {
	$onetime_at = trim( $onetime_at );
	if ( '' === $onetime_at ) {
		return 0;
	}
	$dt = DateTime::createFromFormat( 'Y-m-d H:i', $onetime_at, wp_timezone() );
	return $dt ? $dt->getTimestamp() : 0;
}

/**
 * Compute the next recurring run timestamp + WP recurrence slug for a digest.
 * Only meaningful when frequency is 'daily' or 'weekly'.
 *
 * @return array{0:int,1:string} [ timestamp, recurrence ]
 */
function edfgf_next_run( array $d ): array {
	if ( 'daily' === ( $d['frequency'] ?? 'weekly' ) ) {
		return [ edfgf_next_daily_timestamp( $d['send_time'] ), 'daily' ];
	}
	return [ edfgf_next_weekly_timestamp( $d['send_day'], $d['send_time'] ), 'weekly' ];
}

/**
 * Remove every scheduled instance of our cron hook, regardless of args.
 */
function edfgf_unschedule_all(): void {
	$crons = _get_cron_array();
	if ( empty( $crons ) ) {
		return;
	}
	foreach ( $crons as $ts => $hooks ) {
		if ( isset( $hooks[ EDFGF_CRON_HOOK ] ) ) {
			foreach ( $hooks[ EDFGF_CRON_HOOK ] as $event ) {
				wp_unschedule_event( $ts, EDFGF_CRON_HOOK, $event['args'] );
			}
		}
	}
}

/**
 * Re-sync all cron events. Each active digest may have up to two events:
 *   • a recurring event (when frequency is daily/weekly), args [ id ]
 *   • a one-time event (when onetime_at is set and still in the future),
 *     args [ id, 'once' ] so the handler can tell them apart.
 */
function edfgf_reschedule_all(): void {
	edfgf_unschedule_all();
	foreach ( edfgf_active_digests() as $id => $d ) {
		$id = (string) $id;

		// Recurring schedule.
		if ( in_array( $d['frequency'] ?? 'weekly', [ 'daily', 'weekly' ], true ) ) {
			[ $ts, $recurrence ] = edfgf_next_run( $d );
			wp_schedule_event( $ts, $recurrence, EDFGF_CRON_HOOK, [ $id ] );
		}

		// One-time schedule (only if it hasn't already passed).
		$once_ts = edfgf_onetime_timestamp( (string) ( $d['onetime_at'] ?? '' ) );
		if ( $once_ts > time() ) {
			wp_schedule_single_event( $once_ts, EDFGF_CRON_HOOK, [ $id, 'once' ] );
		}
	}
}
// ── Wire cron hook ───────────────────────────────────────────────
add_action( EDFGF_CRON_HOOK, 'edfgf_cron_handler', 10, 2 );

/**
 * Cron entry point. Legacy (no-arg) events run every active digest; per-digest
 * recurring events run just their digest; one-time events ($mode === 'once')
 * run their digest with the one-time window and then clear the stored date so
 * the send doesn't repeat.
 */
function edfgf_cron_handler( $digest_id = null, $mode = 'recurring' ): void {
	if ( null === $digest_id || '' === $digest_id ) {
		edfgf_run_all_active();
		return;
	}

	$digest_id = (string) $digest_id;
	$mode      = ( 'once' === $mode ) ? 'once' : 'recurring';

	edfgf_run_digest( $digest_id, $mode );

	// After a one-time send, clear the date (saving re-syncs cron). Any
	// recurring schedule on the same digest is left untouched.
	if ( 'once' === $mode ) {
		edfgf_clear_onetime( $digest_id );
	}
}

/**
 * Clear a digest's one-time date and re-sync the schedule.
 */
function edfgf_clear_onetime( string $digest_id ): void {
	$digests = edfgf_get_digests();
	if ( ! isset( $digests[ $digest_id ] ) ) {
		return;
	}
	if ( '' === ( $digests[ $digest_id ]['onetime_at'] ?? '' ) ) {
		return; // Nothing to clear.
	}
	$digests[ $digest_id ]['onetime_at'] = '';
	edfgf_save_digests( $digests );
}
