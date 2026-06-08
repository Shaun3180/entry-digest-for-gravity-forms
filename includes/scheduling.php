<?php
defined( 'ABSPATH' ) || exit;

// ── Scheduling helpers ───────────────────────────────────────────
/**
 * Timestamp for the next occurrence of $day at $time (site timezone).
 */
function dsagfe_next_weekly_timestamp( string $day, string $time ): int {
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
function dsagfe_next_daily_timestamp( string $time ): int {
	$tz    = wp_timezone();
	$now   = new DateTime( 'now', $tz );
	$today = new DateTime( 'today ' . $time, $tz );
	return ( $now < $today )
		? $today->getTimestamp()
		: ( new DateTime( 'tomorrow ' . $time, $tz ) )->getTimestamp();
}

/**
 * Compute the next run timestamp + WP recurrence slug for a digest.
 *
 * @return array{0:int,1:string} [ timestamp, recurrence ]
 */
function dsagfe_next_run( array $d ): array {
	if ( 'daily' === ( $d['frequency'] ?? 'weekly' ) ) {
		return [ dsagfe_next_daily_timestamp( $d['send_time'] ), 'daily' ];
	}
	return [ dsagfe_next_weekly_timestamp( $d['send_day'], $d['send_time'] ), 'weekly' ];
}

/**
 * Remove every scheduled instance of our cron hook, regardless of args.
 */
function dsagfe_unschedule_all(): void {
	$crons = _get_cron_array();
	if ( empty( $crons ) ) {
		return;
	}
	foreach ( $crons as $ts => $hooks ) {
		if ( isset( $hooks[ DSAGFE_CRON_HOOK ] ) ) {
			foreach ( $hooks[ DSAGFE_CRON_HOOK ] as $event ) {
				wp_unschedule_event( $ts, DSAGFE_CRON_HOOK, $event['args'] );
			}
		}
	}
}

/**
 * Re-sync all cron events: one recurring event per active digest, keyed by id.
 */
function dsagfe_reschedule_all(): void {
	dsagfe_unschedule_all();
	foreach ( dsagfe_active_digests() as $id => $d ) {
		[ $ts, $recurrence ] = dsagfe_next_run( $d );
		wp_schedule_event( $ts, $recurrence, DSAGFE_CRON_HOOK, [ (string) $id ] );
	}
}
// ── Wire cron hook ───────────────────────────────────────────────
add_action( DSAGFE_CRON_HOOK, 'dsagfe_cron_handler', 10, 1 );

/**
 * Cron entry point. Legacy (no-arg) events run every active digest; new
 * per-digest events run just their own digest.
 */
function dsagfe_cron_handler( $digest_id = null ): void {
	if ( null === $digest_id || '' === $digest_id ) {
		dsagfe_run_all_active();
		return;
	}
	dsagfe_run_digest( (string) $digest_id );
}
