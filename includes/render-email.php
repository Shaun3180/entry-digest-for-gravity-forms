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
 * @param string $mode        'recurring' or 'once' (a one-time send).
 */
function dsagfe_build_digest_html( array $sections, array $d, int $total_count, string $start_date, string $end_date, string $mode = 'recurring' ): string {
	$is_once    = ( 'once' === $mode );
	$cadence    = $is_once ? 'one-time' : ( ( 'daily' === $d['frequency'] ) ? 'daily' : 'weekly' );
	// A "whole history" one-time send uses a sentinel start date; show it as open-ended.
	$open_ended = $is_once && ( strtotime( $start_date ) <= strtotime( '2000-01-02 00:00:00' ) );
	$period     = $open_ended
		/* translators: %s: an end date/time. */
		? sprintf( __( 'All entries through %s', 'entry-digest-for-gravity-forms' ), dsagfe_local_datetime( $end_date ) )
		: dsagfe_local_datetime( $start_date ) . ' &ndash; ' . dsagfe_local_datetime( $end_date );
	$cadence_label = $is_once
		? __( 'One-time', 'entry-digest-for-gravity-forms' )
		: ( ( 'daily' === $cadence )
			? __( 'Daily', 'entry-digest-for-gravity-forms' )
			: __( 'Weekly', 'entry-digest-for-gravity-forms' ) );
	$multi_form = count( $sections ) > 1;
	$title      = $multi_form
		? ( ! empty( $d['label'] ) ? $d['label'] : __( 'Entry digest', 'entry-digest-for-gravity-forms' ) )
		: ( $sections[0]['form']['title'] ?? __( 'Entry digest', 'entry-digest-for-gravity-forms' ) );

	// Subtitle under the big count number.
	if ( $is_once ) {
		$count_label = _n( 'new entry', 'new entries', $total_count, 'entry-digest-for-gravity-forms' );
	} elseif ( 'daily' === $cadence ) {
		$count_label = _n( 'new entry today', 'new entries today', $total_count, 'entry-digest-for-gravity-forms' );
	} else {
		$count_label = _n( 'new entry this week', 'new entries this week', $total_count, 'entry-digest-for-gravity-forms' );
	}
	if ( $multi_form ) {
		$n_forms = count( $sections );
		/* translators: %d: number of forms. */
		$count_label .= ' ' . sprintf( _n( 'across %d form', 'across %d forms', $n_forms, 'entry-digest-for-gravity-forms' ), $n_forms );
	}

	/**
	 * Filter the digest email's accent color (header background, links). Add-ons
	 * use this for custom branding. Must be a 6-digit hex color; invalid values
	 * fall back to the default.
	 *
	 * @param string $accent Default accent color.
	 * @param array  $d      The digest configuration.
	 */
	$accent = (string) apply_filters( 'dsagfe_email_accent', '#2563eb', $d );
	if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $accent ) ) {
		$accent = '#2563eb';
	}
	$muted  = '#6b7280';
	$border = '#e5e7eb';

	ob_start();
	?>
	<div style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#111827;">
		<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid <?php echo esc_attr( $border ); ?>;border-radius:10px;overflow:hidden;">

			<!-- Header -->
			<div style="background:<?php echo esc_attr( $accent ); ?>;padding:20px 24px;">
				<?php
				/**
				 * Filter an optional logo/header HTML block shown above the digest
				 * title in the email header. Add-ons return an <img> tag (or other
				 * safe HTML) for branding. Core shows nothing.
				 *
				 * @param string $logo_html HTML to render. Default empty.
				 * @param array  $d         The digest configuration.
				 */
				$logo_html = (string) apply_filters( 'dsagfe_email_logo_html', '', $d );
				if ( '' !== $logo_html ) {
					echo '<div style="margin-bottom:10px;">' . wp_kses_post( $logo_html ) . '</div>';
				}
				?>
				<div style="color:#ffffff;font-size:18px;font-weight:700;">
					<?php echo esc_html( $title ); ?>
				</div>
				<div style="color:rgba(255,255,255,0.85);font-size:13px;margin-top:2px;">
					<?php
					/* translators: %s: cadence label such as Daily, Weekly, or One-time. */
					printf( esc_html__( 'Entry digest · %s', 'entry-digest-for-gravity-forms' ), esc_html( $cadence_label ) );
					?>
				</div>
			</div>

			<!-- Summary block -->
			<div style="padding:24px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
					<tr>
						<td style="padding:0 0 16px 0;">
							<div style="font-size:40px;line-height:1;font-weight:800;color:<?php echo esc_attr( $accent ); ?>;">
								<?php echo (int) $total_count; ?>
							</div>
							<div style="font-size:13px;color:<?php echo esc_attr( $muted ); ?>;margin-top:4px;">
								<?php echo esc_html( $count_label ); ?>
							</div>
						</td>
					</tr>
					<tr>
						<td style="border-top:1px solid <?php echo esc_attr( $border ); ?>;padding-top:14px;font-size:13px;color:<?php echo esc_attr( $muted ); ?>;">
							<strong style="color:#111827;"><?php esc_html_e( 'Period:', 'entry-digest-for-gravity-forms' ); ?></strong> <?php echo wp_kses_post( $period ); ?>
							<?php if ( $multi_form ) : ?>
								<br><strong style="color:#111827;"><?php esc_html_e( 'Forms:', 'entry-digest-for-gravity-forms' ); ?></strong>
								<?php
								$bits = [];
								foreach ( $sections as $sec ) {
									$bits[] = esc_html( $sec['form']['title'] ) . ' (' . (int) $sec['count'] . ')';
								}
								echo wp_kses_post( implode( ', ', $bits ) );
								?>
							<?php else : ?>
								<br><strong style="color:#111827;"><?php esc_html_e( 'Form:', 'entry-digest-for-gravity-forms' ); ?></strong> <?php echo esc_html( $sections[0]['form']['title'] ); ?> <?php /* translators: %d: numeric form ID. */ printf( esc_html__( '(ID %d)', 'entry-digest-for-gravity-forms' ), (int) $sections[0]['form']['id'] ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>

			<?php if ( 0 === $total_count ) : ?>
				<div style="padding:0 24px 28px 24px;color:<?php echo esc_attr( $muted ); ?>;font-size:14px;">
					<?php esc_html_e( 'No new entries were submitted during this period.', 'entry-digest-for-gravity-forms' ); ?>
				</div>
			<?php else : ?>
				<?php foreach ( $sections as $sec ) : ?>
					<?php echo dsagfe_render_section_table( $sec, $d, $multi_form, $accent, $muted, $border ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endforeach; ?>
			<?php endif; ?>

			<!-- Footer -->
			<div style="padding:18px 24px 24px 24px;margin-top:8px;border-top:1px solid <?php echo esc_attr( $border ); ?>;font-size:12px;color:<?php echo esc_attr( $muted ); ?>;">
				<?php
				/**
				 * Filter the footer credit line. Add-ons can replace it with custom
				 * text (or an empty string) to white-label the email.
				 *
				 * @param string $credit Default credit text.
				 * @param array  $d      The digest configuration.
				 */
				$footer_credit = (string) apply_filters( 'dsagfe_email_footer_credit', __( 'Sent automatically by Entry Digest for Gravity Forms.', 'entry-digest-for-gravity-forms' ), $d );
				if ( '' !== $footer_credit ) {
					echo esc_html( $footer_credit ) . ' ';
				}
				?>
				<?php
				if ( 'daily' === $cadence ) {
					/* translators: %s: time of day, e.g. 08:00. */
					printf( esc_html__( 'Delivered daily at %s.', 'entry-digest-for-gravity-forms' ), esc_html( $d['send_time'] ) );
				} else {
					/* translators: 1: weekday name; 2: time of day. */
					printf( esc_html__( 'Delivered every %1$s at %2$s.', 'entry-digest-for-gravity-forms' ), esc_html( dsagfe_day_label( $d['send_day'] ) ), esc_html( $d['send_time'] ) );
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
	$form_id   = (int) ( $sec['form']['id'] ?? 0 );

	ob_start();
	?>
	<div style="padding:0 24px 8px 24px;">
		<?php if ( $multi_form ) : ?>
			<div style="margin:6px 0 10px 0;font-size:15px;font-weight:700;color:#111827;">
				<?php echo esc_html( $sec['form']['title'] ); ?>
				<span style="font-weight:500;color:<?php echo esc_attr( $muted ); ?>;font-size:13px;">&middot; <?php
					/* translators: %d: number of entries for one form. */
					printf( esc_html( _n( '%d entry', '%d entries', $count, 'entry-digest-for-gravity-forms' ) ), (int) $count );
				?></span>
			</div>
		<?php endif; ?>

		<?php if ( 0 === $count ) : ?>
			<p style="font-size:13px;color:<?php echo esc_attr( $muted ); ?>;margin:0 0 14px 0;"><?php esc_html_e( 'No new entries for this form.', 'entry-digest-for-gravity-forms' ); ?></p>
		<?php else : ?>
			<div style="overflow-x:auto;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:13px;">
					<thead>
						<tr>
							<th style="text-align:left;padding:8px 10px;background:#f9fafb;border:1px solid <?php echo esc_attr( $border ); ?>;color:<?php echo esc_attr( $muted ); ?>;font-weight:600;white-space:nowrap;"><?php esc_html_e( 'Submitted', 'entry-digest-for-gravity-forms' ); ?></th>
							<?php foreach ( $field_map as $label ) : ?>
								<th style="text-align:left;padding:8px 10px;background:#f9fafb;border:1px solid <?php echo esc_attr( $border ); ?>;color:<?php echo esc_attr( $muted ); ?>;font-weight:600;">
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
							$entry_id   = (int) ( $entry['id'] ?? 0 );
							$date_label = dsagfe_local_datetime( $entry['date_created'] ?? '', 'M j, g:i A' );
							$entry_url  = ( ! empty( $d['link_entries'] ) && $form_id && $entry_id )
								? admin_url( 'admin.php?page=gf_entries&view=entry&id=' . $form_id . '&lid=' . $entry_id )
								: '';
							?>
							<tr>
								<td style="padding:8px 10px;border:1px solid <?php echo esc_attr( $border ); ?>;white-space:nowrap;color:<?php echo esc_attr( $muted ); ?>;">
									<?php if ( $entry_url ) : ?>
										<a href="<?php echo esc_url( $entry_url ); ?>" style="color:<?php echo esc_attr( $accent ); ?>;text-decoration:underline;white-space:nowrap;"><?php echo esc_html( $date_label ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $date_label ); ?>
									<?php endif; ?>
								</td>
								<?php foreach ( array_keys( $field_map ) as $fid ) : ?>
									<?php
									$val = (string) ( $entry[ $fid ] ?? '' );
									if ( mb_strlen( $val ) > DSAGFE_MAX_CELL_CHARS ) {
										$val = mb_substr( $val, 0, DSAGFE_MAX_CELL_CHARS ) . '…';
									}
									?>
									<td style="padding:8px 10px;border:1px solid <?php echo esc_attr( $border ); ?>;vertical-align:top;">
										<?php echo nl2br( esc_html( $val ) ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $count > DSAGFE_MAX_TABLE_ROWS ) : ?>
				<p style="font-size:12px;color:<?php echo esc_attr( $muted ); ?>;margin:12px 0 0 0;">
					<?php
					/**
					 * Filter whether this digest email includes a file attachment,
					 * which controls the "complete set is in the attachment" note.
					 * Add-ons that attach CSV/XLSX exports return true.
					 *
					 * @param bool  $has_attachment Whether an attachment is included.
					 * @param array $d              The digest configuration.
					 * @param array $sec            The current form section.
					 */
					$has_attachment = (bool) apply_filters( 'dsagfe_email_has_attachment', false, $d, $sec );
					$suffix = $has_attachment
						? __( ' — the complete set is in the attachment.', 'entry-digest-for-gravity-forms' )
						: '.';
					/* translators: 1: number of rows shown; 2: total number of entries; 3: trailing clause (a period, or a note about the attachment). */
					printf( esc_html__( 'Showing the first %1$d of %2$d entries%3$s', 'entry-digest-for-gravity-forms' ), (int) DSAGFE_MAX_TABLE_ROWS, (int) $count, esc_html( $suffix ) );
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

// ════════════════════════════════════════════════════════════════
//  Plain-text digest builder — multipart/alternative fallback
// ════════════════════════════════════════════════════════════════
/**
 * Build a readable plain-text version of a digest run, used as the
 * multipart/alternative fallback alongside the HTML body. A text part improves
 * deliverability (spam filters favor multipart messages) and accessibility
 * (text-only clients and screen readers). Mirrors the same data and row cap as
 * the HTML builder; entries are rendered as label/value blocks, which read more
 * cleanly in plain text than fixed-width columns.
 *
 * @param array  $sections    [ [ form, entries, field_map, count ], ... ]
 * @param array  $d           Digest settings.
 * @param int    $total_count Total entries across all sections.
 * @param string $start_date  UTC 'Y-m-d H:i:s'.
 * @param string $end_date    UTC 'Y-m-d H:i:s'.
 * @param string $mode        'recurring' or 'once'.
 */
function dsagfe_build_digest_text( array $sections, array $d, int $total_count, string $start_date, string $end_date, string $mode = 'recurring' ): string {
	$is_once    = ( 'once' === $mode );
	$cadence    = $is_once ? 'one-time' : ( ( 'daily' === ( $d['frequency'] ?? 'weekly' ) ) ? 'daily' : 'weekly' );
	$open_ended = $is_once && ( strtotime( $start_date ) <= strtotime( '2000-01-02 00:00:00' ) );
	$period     = $open_ended
		/* translators: %s: an end date/time. */
		? sprintf( __( 'All entries through %s', 'entry-digest-for-gravity-forms' ), dsagfe_local_datetime( $end_date ) )
		: dsagfe_local_datetime( $start_date ) . ' - ' . dsagfe_local_datetime( $end_date );

	$multi_form = count( $sections ) > 1;
	$title      = $multi_form
		? ( ! empty( $d['label'] ) ? $d['label'] : __( 'Entry digest', 'entry-digest-for-gravity-forms' ) )
		: ( $sections[0]['form']['title'] ?? __( 'Entry digest', 'entry-digest-for-gravity-forms' ) );

	if ( $is_once ) {
		$count_label = _n( 'new entry', 'new entries', $total_count, 'entry-digest-for-gravity-forms' );
	} elseif ( 'daily' === $cadence ) {
		$count_label = _n( 'new entry today', 'new entries today', $total_count, 'entry-digest-for-gravity-forms' );
	} else {
		$count_label = _n( 'new entry this week', 'new entries this week', $total_count, 'entry-digest-for-gravity-forms' );
	}

	$lines   = [];
	$lines[] = $title;
	$lines[] = str_repeat( '=', max( 3, min( 60, mb_strlen( $title ) ) ) );
	$lines[] = '';
	$lines[] = $total_count . ' ' . $count_label;
	$lines[] = __( 'Period:', 'entry-digest-for-gravity-forms' ) . ' ' . wp_strip_all_tags( $period );
	$lines[] = '';

	if ( 0 === $total_count ) {
		$lines[] = __( 'No new entries were submitted during this period.', 'entry-digest-for-gravity-forms' );
	} else {
		foreach ( $sections as $sec ) {
			$entries   = $sec['entries'];
			$field_map = $sec['field_map'];

			if ( $multi_form ) {
				$lines[] = '## ' . $sec['form']['title'] . ' (' . (int) $sec['count'] . ')';
				$lines[] = '';
			}

			if ( empty( $entries ) ) {
				$lines[] = __( 'No new entries for this form.', 'entry-digest-for-gravity-forms' );
				$lines[] = '';
				continue;
			}

			$shown = 0;
			foreach ( $entries as $entry ) {
				if ( $shown >= DSAGFE_MAX_TABLE_ROWS ) {
					break;
				}
				$shown++;

				$when    = dsagfe_local_datetime( $entry['date_created'] ?? '', 'M j, Y g:i A' );
				$lines[] = '- ' . __( 'Submitted:', 'entry-digest-for-gravity-forms' ) . ' ' . $when;

				foreach ( $field_map as $fid => $label ) {
					$val = wp_strip_all_tags( (string) ( $entry[ $fid ] ?? '' ) );
					$val = trim( (string) preg_replace( '/\s+/', ' ', $val ) );
					if ( mb_strlen( $val ) > DSAGFE_MAX_CELL_CHARS ) {
						$val = mb_substr( $val, 0, DSAGFE_MAX_CELL_CHARS ) . '…';
					}
					$lines[] = '    ' . $label . ': ' . $val;
				}
				$lines[] = '';
			}

			if ( (int) $sec['count'] > DSAGFE_MAX_TABLE_ROWS ) {
				/* translators: 1: number of rows shown; 2: total number of entries. */
				$lines[] = sprintf( __( 'Showing the first %1$d of %2$d entries.', 'entry-digest-for-gravity-forms' ), (int) DSAGFE_MAX_TABLE_ROWS, (int) $sec['count'] );
				$lines[] = '';
			}
		}
	}

	$footer_credit = (string) apply_filters( 'dsagfe_email_footer_credit', __( 'Sent automatically by Entry Digest for Gravity Forms.', 'entry-digest-for-gravity-forms' ), $d );
	if ( '' !== $footer_credit ) {
		$lines[] = '--';
		$lines[] = wp_strip_all_tags( $footer_credit );
	}

	return implode( "\n", $lines );
}
