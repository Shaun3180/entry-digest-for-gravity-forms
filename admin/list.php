<?php
defined( 'ABSPATH' ) || exit;

/**
 * The digest list screen.
 */
function dsagfe_render_list( string $notice ): void {
	$digests   = dsagfe_get_digests();
	$is_pro    = dsagfe_is_pro();
	$active    = array_keys( dsagfe_active_digests() );
	$gf_active = class_exists( 'GFAPI' );
	$base_url  = dsagfe_page_url();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Entry Digest for Gravity Forms</h1>
		<?php
		$can_add = $is_pro || count( $digests ) < DSAGFE_FREE_DIGEST_LIMIT;
		if ( $can_add ) {
			echo ' <a href="' . esc_url( $base_url . '&action=new' ) . '" class="page-title-action">' . esc_html__( 'Add Digest', 'entry-digest-for-gravity-forms' ) . '</a>';
		}
		?>
		<hr class="wp-header-end">

		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( ! $gf_active ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Gravity Forms is not active. Digests will not send until it is.', 'entry-digest-for-gravity-forms' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! $is_pro ) : ?>
			<div class="notice notice-info" style="border-left-color:#7c3aed;">
				<p style="margin:.6em 0;">
					<strong><?php esc_html_e( "You're on the free plan.", 'entry-digest-for-gravity-forms' ); ?></strong>
					<?php esc_html_e( 'Pro unlocks unlimited digests, multi-form aggregation, role & recipient routing, conditional filtering, and CSV/Excel attachments.', 'entry-digest-for-gravity-forms' ); ?>
					<a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>"><?php esc_html_e( 'Start a free Pro trial', 'entry-digest-for-gravity-forms' ); ?> &rarr;</a>
				</p>
			</div>
		<?php endif; ?>

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
						$is_active   = in_array( $id, $active, true );
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
						$rcpts    = dsagfe_resolve_recipients( $d );

						// Next run = earliest of the recurring and one-time events.
						$next_rec  = wp_next_scheduled( DSAGFE_CRON_HOOK, [ (string) $id ] );
						$next_once = wp_next_scheduled( DSAGFE_CRON_HOOK, [ (string) $id, 'once' ] );
						$candidates = array_filter( [ $next_rec, $next_once ] );
						$next       = $candidates ? min( $candidates ) : 0;
						$next_fmt   = $next
							? ( new DateTime( '@' . $next ) )->setTimezone( wp_timezone() )->format( 'M j, Y g:i A' )
							: '—';

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
						<tr<?php echo $is_active ? '' : ' style="opacity:.5;"'; ?>>
							<td>
								<strong><?php echo esc_html( $d['label'] ?: __( 'Untitled digest', 'entry-digest-for-gravity-forms' ) ); ?></strong>
								<?php if ( ! $is_active ) : ?>
									<br><span style="color:#b32d2e;font-size:11px;"><?php esc_html_e( 'Inactive (free plan limit — upgrade to enable)', 'entry-digest-for-gravity-forms' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( implode( ', ', $form_names ) ); ?></td>
							<td><?php echo esc_html( $rcpts ? implode( ', ', $rcpts ) : __( '— none —', 'entry-digest-for-gravity-forms' ) ); ?></td>
							<td><?php echo $sched; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td><?php echo esc_html( $is_active ? $next_fmt : '—' ); ?></td>
							<td>
								<a href="<?php echo esc_url( $base_url . '&action=edit&digest=' . rawurlencode( $id ) ); ?>" class="button button-small"><?php esc_html_e( 'Edit', 'entry-digest-for-gravity-forms' ); ?></a>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'dsagfe_send_now' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_send_now" class="button button-small"><?php esc_html_e( 'Send Now', 'entry-digest-for-gravity-forms' ); ?></button>
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
	</div>
	<?php
}
