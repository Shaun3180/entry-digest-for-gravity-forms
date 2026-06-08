<?php
defined( 'ABSPATH' ) || exit;

// ════════════════════════════════════════════════════════════════
//  HTML digest builder — summary block + per-form entry tables
// ════════════════════════════════════════════════════════════════
function dsagfe_local_datetime( string $utc, string $format = 'M j, Y g:i A' ): string {
	$ts = strtotime( $utc . ' UTC' );
	return $ts ? wp_date( $format, $ts ) : esc_html( $utc );
}

/**
 * Build the full HTML email body for a digest run.
 *
 * @param array  $sections    [ [ form, entries, field_map, count ], ... ]
 * @param array  $d           Digest settings.
 * @param int    $total_count Total entries across all sections.
 * @param string $start_date  UTC 'Y-m-d H:i:s'.
 * @param string $end_date    UTC 'Y-m-d H:i:s'.
 */
function dsagfe_build_digest_html( array $sections, array $d, int $total_count, string $start_date, string $end_date ): string {
	$cadence    = ( 'daily' === $d['frequency'] ) ? 'daily' : 'weekly';
	$period     = dsagfe_local_datetime( $start_date ) . ' &ndash; ' . dsagfe_local_datetime( $end_date );
	$multi_form = count( $sections ) > 1;
	$title      = $multi_form
		? ( ! empty( $d['label'] ) ? $d['label'] : 'Entry digest' )
		: ( $sections[0]['form']['title'] ?? 'Entry digest' );

	$accent = '#2563eb';
	$muted  = '#6b7280';
	$border = '#e5e7eb';

	ob_start();
	?>
	<div style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
		<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid <?php echo $border; ?>;border-radius:10px;overflow:hidden;">

			<!-- Header -->
			<div style="background:<?php echo $accent; ?>;padding:20px 24px;">
				<div style="color:#ffffff;font-size:18px;font-weight:700;">
					<?php echo esc_html( $title ); ?>
				</div>
				<div style="color:rgba(255,255,255,0.85);font-size:13px;margin-top:2px;">
					Entry digest &middot; <?php echo esc_html( ucfirst( $cadence ) ); ?>
				</div>
			</div>

			<!-- Summary block -->
			<div style="padding:24px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
					<tr>
						<td style="padding:0 0 16px 0;">
							<div style="font-size:40px;line-height:1;font-weight:800;color:<?php echo $accent; ?>;">
								<?php echo (int) $total_count; ?>
							</div>
							<div style="font-size:13px;color:<?php echo $muted; ?>;margin-top:4px;">
								new <?php echo ( 1 === $total_count ) ? 'entry' : 'entries'; ?> this <?php echo esc_html( 'daily' === $cadence ? 'day' : 'week' ); ?><?php echo $multi_form ? ' across ' . (int) count( $sections ) . ' forms' : ''; ?>
							</div>
						</td>
					</tr>
					<tr>
						<td style="border-top:1px solid <?php echo $border; ?>;padding-top:14px;font-size:13px;color:<?php echo $muted; ?>;">
							<strong style="color:#111827;">Period:</strong> <?php echo wp_kses_post( $period ); ?>
							<?php if ( $multi_form ) : ?>
								<br><strong style="color:#111827;">Forms:</strong>
								<?php
								$bits = [];
								foreach ( $sections as $sec ) {
									$bits[] = esc_html( $sec['form']['title'] ) . ' (' . (int) $sec['count'] . ')';
								}
								echo wp_kses_post( implode( ', ', $bits ) );
								?>
							<?php else : ?>
								<br><strong style="color:#111827;">Form:</strong> <?php echo esc_html( $sections[0]['form']['title'] ); ?> (ID <?php echo (int) $sections[0]['form']['id']; ?>)
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>

			<?php if ( 0 === $total_count ) : ?>
				<div style="padding:0 24px 28px 24px;color:<?php echo $muted; ?>;font-size:14px;">
					No new entries were submitted during this period.
				</div>
			<?php else : ?>
				<?php foreach ( $sections as $sec ) : ?>
					<?php echo dsagfe_render_section_table( $sec, $d, $multi_form, $accent, $muted, $border ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endforeach; ?>
			<?php endif; ?>

			<!-- Footer -->
			<div style="padding:18px 24px 24px 24px;margin-top:8px;border-top:1px solid <?php echo $border; ?>;font-size:12px;color:<?php echo $muted; ?>;">
				Sent automatically by Entry Digest for Gravity Forms.
				<?php
				if ( 'daily' === $cadence ) {
					echo 'Delivered daily at ' . esc_html( $d['send_time'] ) . '.';
				} else {
					echo 'Delivered every ' . esc_html( ucfirst( $d['send_day'] ) ) . ' at ' . esc_html( $d['send_time'] ) . '.';
				}
				?>
			</div>

		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render one form's table block within the digest.
 */
function dsagfe_render_section_table( array $sec, array $d, bool $multi_form, string $accent, string $muted, string $border ): string {
	$entries   = $sec['entries'];
	$field_map = $sec['field_map'];
	$count     = $sec['count'];

	ob_start();
	?>
	<div style="padding:0 24px 8px 24px;">
		<?php if ( $multi_form ) : ?>
			<div style="margin:6px 0 10px 0;font-size:15px;font-weight:700;color:#111827;">
				<?php echo esc_html( $sec['form']['title'] ); ?>
				<span style="font-weight:500;color:<?php echo $muted; ?>;font-size:13px;">&middot; <?php echo (int) $count; ?> <?php echo ( 1 === $count ) ? 'entry' : 'entries'; ?></span>
			</div>
		<?php endif; ?>

		<?php if ( 0 === $count ) : ?>
			<p style="font-size:13px;color:<?php echo $muted; ?>;margin:0 0 14px 0;">No new entries for this form.</p>
		<?php else : ?>
			<div style="overflow-x:auto;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
					<thead>
						<tr>
							<th style="text-align:left;padding:8px 10px;background:#f9fafb;border:1px solid <?php echo $border; ?>;color:<?php echo $muted; ?>;font-weight:600;white-space:nowrap;">Submitted</th>
							<?php foreach ( $field_map as $label ) : ?>
								<th style="text-align:left;padding:8px 10px;background:#f9fafb;border:1px solid <?php echo $border; ?>;color:<?php echo $muted; ?>;font-weight:600;">
									<?php echo esc_html( $label ); ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php
						$shown = 0;
						foreach ( $entries as $entry ) :
							if ( $shown >= DSAGFE_MAX_TABLE_ROWS ) {
								break;
							}
							$shown++;
							?>
							<tr>
								<td style="padding:8px 10px;border:1px solid <?php echo $border; ?>;white-space:nowrap;color:<?php echo $muted; ?>;">
									<?php echo esc_html( dsagfe_local_datetime( $entry['date_created'] ?? '', 'M j, g:i A' ) ); ?>
								</td>
								<?php foreach ( array_keys( $field_map ) as $fid ) : ?>
									<?php
									$val = (string) ( $entry[ $fid ] ?? '' );
									if ( mb_strlen( $val ) > DSAGFE_MAX_CELL_CHARS ) {
										$val = mb_substr( $val, 0, DSAGFE_MAX_CELL_CHARS ) . '…';
									}
									?>
									<td style="padding:8px 10px;border:1px solid <?php echo $border; ?>;vertical-align:top;">
										<?php echo nl2br( esc_html( $val ) ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $count > DSAGFE_MAX_TABLE_ROWS ) : ?>
				<p style="font-size:12px;color:<?php echo $muted; ?>;margin:12px 0 0 0;">
					Showing the first <?php echo (int) DSAGFE_MAX_TABLE_ROWS; ?> of <?php echo (int) $count; ?> entries<?php echo ( 'none' !== $d['attach_format'] && dsagfe_is_pro() ) ? ' — the complete set is in the attachment.' : '.'; ?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}
