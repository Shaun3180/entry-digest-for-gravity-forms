<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  WP-Cron health check
// ════════════════════════════════════════════════════════════════
//
//  The reliable, host-agnostic signal that scheduled sends are broken is an
//  *overdue event*: if one of our digest events is scheduled in the past by
//  more than a grace period, the task scheduler is not firing it. That happens
//  in two situations:
//
//    • Low-traffic site — WP-Cron only runs on page visits, so a site that is
//      rarely visited may never trigger it.
//    • WP-Cron disabled (DISABLE_WP_CRON) without a working replacement — a
//      real system cron is supposed to call wp-cron.php, but isn't.
//
//  We deliberately do NOT warn merely because DISABLE_WP_CRON is set: disabling
//  WP-Cron and triggering it from a real cron job is a recommended, healthy
//  setup. We only warn when an event is actually overdue, which catches a
//  misconfigured real-cron just as well as a stalled WP-Cron. The wording then
//  adapts to whether WP-Cron is disabled, so the fix points the right way.

/**
 * Inspect our scheduled events and assess whether they are running on time.
 *
 * @return array{ok:bool,overdue_by:int,next:int,disabled:bool}|null
 *   Null when there is nothing to assess (no scheduled digest events at all —
 *   e.g. all digests are paused or none are configured).
 */
function dsagfe_cron_health(): ?array {
	$now   = time();
	$grace = (int) apply_filters( 'dsagfe_cron_overdue_grace', HOUR_IN_SECONDS );

	// Find the earliest scheduled run across every instance of our hook
	// (recurring, one-time, and any legacy no-arg events).
	$earliest = 0;
	$crons    = _get_cron_array();
	if ( ! empty( $crons ) ) {
		foreach ( $crons as $ts => $hooks ) {
			if ( isset( $hooks[ DSAGFE_CRON_HOOK ] ) && ( 0 === $earliest || (int) $ts < $earliest ) ) {
				$earliest = (int) $ts;
			}
		}
	}

	if ( 0 === $earliest ) {
		return null; // Nothing scheduled — nothing to assess.
	}

	$overdue_by = $now - $earliest;

	return [
		'ok'         => $overdue_by <= $grace,
		'overdue_by' => max( 0, $overdue_by ),
		'next'       => $earliest,
		'disabled'   => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
	];
}

/**
 * Build the admin-notice HTML for an unhealthy scheduler, or '' when healthy.
 *
 * Returned markup is fully escaped and safe to echo directly.
 */
function dsagfe_cron_health_notice_html(): string {
	$health = dsagfe_cron_health();
	if ( null === $health || $health['ok'] ) {
		return '';
	}

	$ago = human_time_diff( $health['next'], time() );

	$intro = sprintf(
		/* translators: %s: human-readable duration, e.g. "3 hours". */
		__( 'A scheduled digest was due %s ago but hasn’t run. This usually means WordPress’s task scheduler (WP-Cron) isn’t firing, so your digests may not send on time.', 'entry-digest-for-gravity-forms' ),
		esc_html( $ago )
	);

	if ( $health['disabled'] ) {
		$fix = __( 'WP-Cron is turned off on this site (DISABLE_WP_CRON), so a real cron job must call wp-cron.php. Check that your server cron or host scheduler is configured and actually running.', 'entry-digest-for-gravity-forms' );
	} else {
		$fix = __( 'On low-traffic sites WP-Cron may rarely run, because it only fires when someone visits the site. A real server cron job that calls wp-cron.php on a fixed schedule makes sends reliable.', 'entry-digest-for-gravity-forms' );
	}

	$learn = sprintf(
		' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
		esc_url( 'https://developer.wordpress.org/plugins/cron/' ),
		esc_html__( 'Learn about WP-Cron', 'entry-digest-for-gravity-forms' )
	);

	return '<div class="notice notice-warning"><p><strong>'
		. esc_html__( 'Entry Digest: scheduled sends may not be running', 'entry-digest-for-gravity-forms' )
		. '</strong></p><p>' . esc_html( $intro ) . '</p><p>' . esc_html( $fix ) . $learn . '</p></div>';
}
