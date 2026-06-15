<?php
defined( 'ABSPATH' ) || exit;

/**
 * The digest list screen.
 */
function dsagfe_render_list( string $notice ): void {
	$digests   = dsagfe_get_digests();
	$gf_active = class_exists( 'GFAPI' );
	$base_url  = dsagfe_page_url();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Entry Digest for Gravity Forms</h1>
		<?php echo ' <a href="' . esc_url( $base_url . '&action=new' ) . '" class="page-title-action">' . esc_html__( 'Add Digest', 'entry-digest-for-gravity-forms' ) . '</a>'; ?>
		<hr class="wp-header-end">

		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( ! $gf_active ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Gravity Forms is not active. Digests will not send until it is.', 'entry-digest-for-gravity-forms' ); ?></p></div>
		<?php endif; ?>

		<?php
		/**
		 * Fires just below the list-screen header, before the digest table. Add-ons
		 * can use this to render notices (for example, a Pro upsell).
		 */
		do_action( 'dsagfe_list_after_header' );
		?>

		<?php if ( empty( $digests ) ) : ?>
			<div style="max-width:560px;margin:32px auto;padding:40px 32px;text-align:center;background:#fff;border:1px solid #c3c4c7;border-radius:4px;">
				<span class="dashicons dashicons-email-alt" style="font-size:48px;width:48px;height:48px;color:#7c3aed;"></span>
				<h2 style="margin:16px 0 8px;"><?php esc_html_e( 'Set up your first digest', 'entry-digest-for-gravity-forms' ); ?></h2>
				<p style="color:#555;max-width:420px;margin:0 auto 20px;">
					<?php esc_html_e( 'A digest bundles new Gravity Forms entries into one scheduled email — daily or weekly — so you get a clean summary instead of a flood of individual notifications.', 'entry-digest-for-gravity-forms' ); ?>
				</p>
				<a href="<?php echo esc_url( $base_url . '&action=new' ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'Create your first digest', 'entry-digest-for-gravity-forms' ); ?></a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Digest', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Forms', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Recipients', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Schedule', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Next run', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'entry-digest-for-gravity-forms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $digests as $id => $d ) : ?>
						<?php
						$form_names  = [];
						if ( $gf_active ) {
							foreach ( $d['form_ids'] as $fid ) {
								$f            = GFAPI::get_form( (int) $fid );
								/* translators: %d: Gravity Forms form ID. */
								$form_names[] = $f ? $f['title'] : sprintf( __( 'Form %d (missing)', 'entry-digest-for-gravity-forms' ), (int) $fid );
							}
						} else {
							/* translators: %d: Gravity Forms form ID. */
							$form_names = array_map( static fn( $f ) => sprintf( __( 'Form %d', 'entry-digest-for-gravity-forms' ), (int) $f ), $d['form_ids'] );
						}
						$rcpts     = dsagfe_resolve_recipients( $d );
						$is_paused = ! empty( $d['paused'] );

						// Next run = earliest of the recurring and one-time events. A paused
						// digest has no scheduled events, so it simply shows "Paused".
						$next_rec  = wp_next_scheduled( DSAGFE_CRON_HOOK, [ (string) $id ] );
						$next_once = wp_next_scheduled( DSAGFE_CRON_HOOK, [ (string) $id, 'once' ] );
						$candidates = array_filter( [ $next_rec, $next_once ] );
						$next       = $candidates ? min( $candidates ) : 0;
						if ( $is_paused ) {
							$next_fmt = __( 'Paused', 'entry-digest-for-gravity-forms' );
						} else {
							$next_fmt = $next
								? ( new DateTime( '@' . $next ) )->setTimezone( wp_timezone() )->format( 'M j, Y g:i A' )
								: '—';
						}

						// Schedule label: recurring part (if any) plus one-time part (if set).
						$parts = [];
						if ( 'daily' === $d['frequency'] ) {
							/* translators: %s: time of day, e.g. 08:00. */
							$parts[] = sprintf( __( 'Daily %s', 'entry-digest-for-gravity-forms' ), esc_html( $d['send_time'] ) );
						} elseif ( 'weekly' === $d['frequency'] ) {
							/* translators: 1: weekday name; 2: time of day. */
							$parts[] = sprintf( __( 'Weekly %1$s %2$s', 'entry-digest-for-gravity-forms' ), esc_html( dsagfe_day_label( $d['send_day'] ) ), esc_html( $d['send_time'] ) );
						}
						if ( ! empty( $d['onetime_at'] ) ) {
							$once_dt = DateTime::createFromFormat( 'Y-m-d H:i', $d['onetime_at'], wp_timezone() );
							/* translators: %s: a specific date and time. */
							$parts[] = sprintf( __( 'Once: %s', 'entry-digest-for-gravity-forms' ), esc_html( $once_dt ? $once_dt->format( 'M j, Y g:i A' ) : $d['onetime_at'] ) );
						}
						$sched = $parts ? implode( '<br>', $parts ) : '—';
						?>
						<tr<?php echo $is_paused ? ' style="opacity:0.6;"' : ''; ?>>
							<td>
								<strong><?php echo esc_html( $d['label'] ?: __( 'Untitled digest', 'entry-digest-for-gravity-forms' ) ); ?></strong>
								<?php if ( $is_paused ) : ?>
									<span style="display:inline-block;margin-left:6px;padding:1px 7px;border-radius:9px;background:#dba617;color:#fff;font-size:11px;font-weight:600;vertical-align:middle;"><?php esc_html_e( 'Paused', 'entry-digest-for-gravity-forms' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( implode( ', ', $form_names ) ); ?></td>
							<td><?php echo esc_html( $rcpts ? implode( ', ', $rcpts ) : __( '— none —', 'entry-digest-for-gravity-forms' ) ); ?></td>
							<td><?php echo $sched; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td><?php echo esc_html( $next_fmt ); ?></td>
							<td>
								<a href="<?php echo esc_url( $base_url . '&action=edit&digest=' . rawurlencode( $id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'entry-digest-for-gravity-forms' ); ?></a>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'dsagfe_send_now' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_send_now" class="button button-small"><?php esc_html_e( 'Send Now', 'entry-digest-for-gravity-forms' ); ?></button>
								</form>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'dsagfe_toggle_pause' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_toggle_pause" class="button button-small"><?php echo esc_html( $is_paused ? __( 'Resume', 'entry-digest-for-gravity-forms' ) : __( 'Pause', 'entry-digest-for-gravity-forms' ) ); ?></button>
								</form>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this digest?', 'entry-digest-for-gravity-forms' ) ); ?>');">
									<?php wp_nonce_field( 'dsagfe_delete_digest' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_delete_digest" class="button button-small button-link-delete"><?php esc_html_e( 'Delete', 'entry-digest-for-gravity-forms' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<p style="margin-top:16px;color:#666;font-size:12px;">
			<?php
			printf(
				/* translators: %s: site timezone string, e.g. America/Denver. */
				esc_html__( 'Schedules use your site timezone: %s.', 'entry-digest-for-gravity-forms' ),
				'<strong>' . esc_html( wp_timezone_string() ) . '</strong>'
			);
			?>
			<?php esc_html_e( 'Weekly digests cover the past 7 days; daily digests cover the past 24 hours.', 'entry-digest-for-gravity-forms' ); ?>
		</p>

		<?php
		// ── Recent sends (send log) ──────────────────────────────────
		$log = dsagfe_get_log();
		if ( ! empty( $log ) ) :
			?>
			<h2 style="margin-top:30px;"><?php esc_html_e( 'Recent sends', 'entry-digest-for-gravity-forms' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:170px;"><?php esc_html_e( 'When', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Digest', 'entry-digest-for-gravity-forms' ); ?></th>
						<th style="width:80px;"><?php esc_html_e( 'Entries', 'entry-digest-for-gravity-forms' ); ?></th>
						<th><?php esc_html_e( 'Recipients', 'entry-digest-for-gravity-forms' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Type', 'entry-digest-for-gravity-forms' ); ?></th>
						<th style="width:140px;"><?php esc_html_e( 'Status', 'entry-digest-for-gravity-forms' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $log as $row ) : ?>
						<?php
						$when = ( new DateTime( '@' . (int) $row['time'] ) )
							->setTimezone( wp_timezone() )
							->format( 'M j, Y g:i A' );
						list( $st_label, $st_color ) = dsagfe_log_status_meta( (string) $row['status'] );
						?>
						<tr>
							<td><?php echo esc_html( $when ); ?></td>
							<td><?php echo esc_html( $row['label'] ?: __( 'Untitled digest', 'entry-digest-for-gravity-forms' ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row['count'] ) ); ?></td>
							<td><?php echo esc_html( $row['recipients'] ?: '—' ); ?></td>
							<td><?php echo esc_html( dsagfe_log_context_label( (string) $row['context'] ) ); ?></td>
							<td><strong style="color:<?php echo esc_attr( $st_color ); ?>;"><?php echo esc_html( $st_label ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div style="margin-top:8px;">
				<form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Clear the send log?', 'entry-digest-for-gravity-forms' ) ); ?>');">
					<?php wp_nonce_field( 'dsagfe_clear_log' ); ?>
					<button type="submit" name="dsagfe_clear_log" class="button button-small"><?php esc_html_e( 'Clear log', 'entry-digest-for-gravity-forms' ); ?></button>
				</form>
			</div>
			<p class="description" style="font-size:12px;color:#666;">
				<?php esc_html_e( '“Sent” means the email was handed to your site’s mailer, not a guarantee it reached the inbox. Showing the most recent sends.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}
