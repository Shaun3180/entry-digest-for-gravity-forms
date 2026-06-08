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
			echo ' <a href="' . esc_url( $base_url . '&action=new' ) . '" class="page-title-action">Add Digest</a>';
		}
		?>
		<hr class="wp-header-end">

		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( ! $gf_active ) : ?>
			<div class="notice notice-error"><p>Gravity Forms is not active. Digests will not send until it is.</p></div>
		<?php endif; ?>

		<?php if ( ! $is_pro ) : ?>
			<div class="notice notice-info" style="border-left-color:#7c3aed;">
				<p style="margin:.6em 0;">
					<strong>You're on the free plan.</strong>
					Pro unlocks unlimited digests, multi-form aggregation, role &amp; recipient routing, conditional filtering, and CSV/Excel attachments.
					<a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>">Start a free Pro trial &rarr;</a>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $digests ) ) : ?>
			<div style="max-width:560px;margin:32px auto;padding:40px 32px;text-align:center;background:#fff;border:1px solid #c3c4c7;border-radius:4px;">
				<span class="dashicons dashicons-email-alt" style="font-size:48px;width:48px;height:48px;color:#7c3aed;"></span>
				<h2 style="margin:16px 0 8px;">Set up your first digest</h2>
				<p style="color:#555;max-width:420px;margin:0 auto 20px;">
					A digest bundles new Gravity Forms entries into one scheduled email — daily or weekly — so you get a clean summary instead of a flood of individual notifications.
				</p>
				<a href="<?php echo esc_url( $base_url . '&action=new' ); ?>" class="button button-primary button-hero">Create your first digest</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Digest</th>
						<th>Forms</th>
						<th>Recipients</th>
						<th>Schedule</th>
						<th>Next run</th>
						<th>Actions</th>
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
								$form_names[] = $f ? $f['title'] : ( 'Form ' . (int) $fid . ' (missing)' );
							}
						} else {
							$form_names = array_map( static fn( $f ) => 'Form ' . (int) $f, $d['form_ids'] );
						}
						$rcpts    = dsagfe_resolve_recipients( $d );
						$next     = wp_next_scheduled( DSAGFE_CRON_HOOK, [ (string) $id ] );
						$next_fmt = $next
							? ( new DateTime( '@' . $next ) )->setTimezone( wp_timezone() )->format( 'M j, Y g:i A' )
							: '—';
						$sched    = ( 'daily' === $d['frequency'] )
							? 'Daily ' . esc_html( $d['send_time'] )
							: 'Weekly ' . esc_html( ucfirst( $d['send_day'] ) ) . ' ' . esc_html( $d['send_time'] );
						?>
						<tr<?php echo $is_active ? '' : ' style="opacity:.5;"'; ?>>
							<td>
								<strong><?php echo esc_html( $d['label'] ?: 'Untitled digest' ); ?></strong>
								<?php if ( ! $is_active ) : ?>
									<br><span style="color:#b32d2e;font-size:11px;">Inactive (free plan limit — upgrade to enable)</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( implode( ', ', $form_names ) ); ?></td>
							<td><?php echo esc_html( $rcpts ? implode( ', ', $rcpts ) : '— none —' ); ?></td>
							<td><?php echo $sched; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td><?php echo esc_html( $is_active ? $next_fmt : '—' ); ?></td>
							<td>
								<a href="<?php echo esc_url( $base_url . '&action=edit&digest=' . rawurlencode( $id ) ); ?>" class="button button-small">Edit</a>
								<form method="post" style="display:inline;">
									<?php wp_nonce_field( 'dsagfe_send_now' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_send_now" class="button button-small">Send Now</button>
								</form>
								<form method="post" style="display:inline;" onsubmit="return confirm('Delete this digest?');">
									<?php wp_nonce_field( 'dsagfe_delete_digest' ); ?>
									<input type="hidden" name="digest_id" value="<?php echo esc_attr( $id ); ?>">
									<button type="submit" name="dsagfe_delete_digest" class="button button-small button-link-delete">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<p style="margin-top:16px;color:#666;font-size:12px;">
			Schedules use your site timezone: <strong><?php echo esc_html( wp_timezone_string() ); ?></strong>.
			Weekly digests cover the past 7 days; daily digests cover the past 24 hours.
		</p>
	</div>
	<?php
}
