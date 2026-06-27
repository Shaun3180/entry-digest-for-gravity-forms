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
//    • Low-traffic site - WP-Cron only runs on page visits, so a site that is
//      rarely visited may never trigger it.
//    • WP-Cron disabled (DISABLE_WP_CRON) without a working replacement - a
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
 *   Null when there is nothing to assess (no scheduled digest events at all -
 *   e.g. all digests are paused or none are configured).
 */
function edfgf_cron_health(): ?array {
	$now   = time();
	$grace = (int) apply_filters( 'edfgf_cron_overdue_grace', HOUR_IN_SECONDS );

	// Find the earliest scheduled run across every instance of our hook
	// (recurring, one-time, and any legacy no-arg events).
	$earliest = 0;
	$crons    = _get_cron_array();
	if ( ! empty( $crons ) ) {
		foreach ( $crons as $ts => $hooks ) {
			if ( isset( $hooks[ EDFGF_CRON_HOOK ] ) && ( 0 === $earliest || (int) $ts < $earliest ) ) {
				$earliest = (int) $ts;
			}
		}
	}

	if ( 0 === $earliest ) {
		return null; // Nothing scheduled - nothing to assess.
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
function edfgf_cron_health_notice_html(): string {
	$health = edfgf_cron_health();
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

// ── Dismissible overdue notice (dashboard + digest list) ─────────

add_action( 'admin_notices', 'edfgf_maybe_show_overdue_notice' );
add_action( 'wp_ajax_edfgf_dismiss_overdue_notice', 'edfgf_ajax_dismiss_overdue_notice' );

/**
 * Render a dismissible admin notice when the scheduler is overdue.
 *
 * Shown only to admins, only on the main dashboard and the digest list page.
 * Once dismissed, the notice is suppressed for 7 days; after that it re-surfaces
 * if the problem still exists.  A new overdue event (different timestamp) always
 * resets the dismissal regardless of the 7-day window.
 */
function edfgf_maybe_show_overdue_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}

	$is_dashboard = 'dashboard' === $screen->id;

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$page   = isset( $_GET['page'] )   ? sanitize_key( wp_unslash( $_GET['page'] ) )   : '';
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	// phpcs:enable
	$is_list_page = ( 'edfgf-entry-digest' === $page ) && ( '' === $action || 'list' === $action );

	if ( ! $is_dashboard && ! $is_list_page ) {
		return;
	}

	$health = edfgf_cron_health();
	if ( null === $health || $health['ok'] ) {
		return;
	}

	// Suppress if this exact overdue event was dismissed within the past 7 days.
	$raw       = get_user_meta( get_current_user_id(), 'edfgf_cron_notice_dismissed', true );
	$dismissed = $raw ? json_decode( $raw, true ) : null;
	if ( is_array( $dismissed )
		&& isset( $dismissed['ts'], $dismissed['until'] )
		&& (int) $dismissed['ts']    === (int) $health['next']
		&& (int) $dismissed['until'] > time()
	) {
		return;
	}

	$ago      = human_time_diff( $health['next'], time() );
	$earliest = (int) $health['next'];

	$fix = $health['disabled']
		? esc_html__( 'WP-Cron is turned off on this site (DISABLE_WP_CRON), so a real cron job must call wp-cron.php. Check that your server cron or host scheduler is configured and running.', 'entry-digest-for-gravity-forms' )
		: esc_html__( 'On low-traffic sites WP-Cron may rarely fire, because it only runs when someone visits the site. A server cron job calling wp-cron.php on a fixed schedule makes sends reliable.', 'entry-digest-for-gravity-forms' );

	$learn_url = 'https://developer.wordpress.org/plugins/cron/';
	$list_url  = class_exists( 'GFForms' )
		? admin_url( 'admin.php?page=edfgf-entry-digest' )
		: admin_url( 'tools.php?page=edfgf-entry-digest' );

	// The dismissal AJAX call is wired up by admin/js/notices.js, which is enqueued
	// on this screen from admin/enqueue.php. The overdue event timestamp is passed
	// to that script via the data-earliest attribute below; the nonce and AJAX URL
	// are provided through wp_localize_script() as window.EDFGF_NOTICE.
	?>
	<div class="notice notice-warning is-dismissible" id="edfgf-overdue-notice" data-earliest="<?php echo esc_attr( (string) $earliest ); ?>">
		<p><strong><?php esc_html_e( 'Entry Digest: scheduled sends may not be running', 'entry-digest-for-gravity-forms' ); ?></strong></p>
		<p>
			<?php
			printf(
				wp_kses(
					/* translators: %s: human-readable duration, e.g. "3 hours". */
					__( 'A scheduled digest was due <strong>%s ago</strong> but hasn\'t run. Your digests may not be sending on time.', 'entry-digest-for-gravity-forms' ),
					[ 'strong' => [] ]
				),
				esc_html( $ago )
			);
			?>
			<?php if ( $is_dashboard ) : ?>
				&nbsp;<a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'View digest list →', 'entry-digest-for-gravity-forms' ); ?></a>
			<?php endif; ?>
		</p>
		<p><?php echo $fix; // phpcs:ignore WordPress.Security.EscapeOutput -- already escaped above. ?> <a href="<?php echo esc_url( $learn_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about WP-Cron', 'entry-digest-for-gravity-forms' ); ?></a></p>
	</div>
	<?php
}

/**
 * AJAX handler: persist the dismiss state in user meta.
 *
 * Stores the overdue event timestamp alongside a 7-day expiry so the notice
 * reappears automatically if the scheduler is still broken after that window.
 */
function edfgf_ajax_dismiss_overdue_notice(): void {
	check_ajax_referer( 'edfgf_dismiss_overdue_notice', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', '', [ 'response' => 403 ] );
	}

	$earliest = isset( $_POST['earliest'] ) ? absint( wp_unslash( $_POST['earliest'] ) ) : 0;
	if ( $earliest > 0 ) {
		update_user_meta(
			get_current_user_id(),
			'edfgf_cron_notice_dismissed',
			wp_json_encode( [
				'ts'    => $earliest,
				'until' => time() + WEEK_IN_SECONDS,
			] )
		);
	}

	wp_die();
}
