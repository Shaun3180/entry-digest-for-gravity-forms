<?php
defined( 'ABSPATH' ) || exit;

/**
 * The digest editor screen (new or edit).
 */
function dsagfe_render_editor( string $action, string $notice ): void {
	$base_url = dsagfe_page_url();
	$gf       = class_exists( 'GFAPI' );

	if ( 'edit' === $action ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- $_GET['digest'] identifies which record to display; it is read-only and not a form submission.
		$id = isset( $_GET['digest'] ) ? sanitize_text_field( wp_unslash( $_GET['digest'] ) ) : '';
		$d  = dsagfe_get_digest( $id );
		if ( ! $d ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Digest not found.', 'entry-digest-for-gravity-forms' ) . '</p></div></div>';
			return;
		}
	} else {
		$d       = dsagfe_normalize_digest( [], '' );
		$d['id'] = '';
	}

	$all_forms = $gf ? GFAPI::get_forms() : [];
	$days      = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];

	// One-time send: datetime-local needs 'Y-m-d\TH:i'; we store 'Y-m-d H:i'.
	$onetime_input = $d['onetime_at'] ? str_replace( ' ', 'T', $d['onetime_at'] ) : '';
	// Sensible minimum for the picker = now (site timezone).
	$onetime_min   = ( new DateTime( 'now', wp_timezone() ) )->format( 'Y-m-d\TH:i' );
	// Default lookback to "days since the (oldest) form was created".
	$forms_age     = dsagfe_forms_age_days( $d['form_ids'] );
	$lookback_val  = (int) $d['onetime_lookback_days'] > 0 ? (int) $d['onetime_lookback_days'] : $forms_age;
	$window_label  = ( 'daily' === $d['frequency'] )
		? __( 'past 24 hours', 'entry-digest-for-gravity-forms' )
		: __( 'past 7 days', 'entry-digest-for-gravity-forms' );

	// Shared inline styles for the section cards.
	$card_style = 'background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:2px 20px 12px;margin:0 0 18px;max-width:900px;';
	$head_style = 'margin:14px 0 0;padding-bottom:8px;border-bottom:1px solid #f0f0f1;font-size:15px;';
	?>
	<div class="wrap">
        <h1><?php echo esc_html( 'new' === $action ? __( 'Add Digest', 'entry-digest-for-gravity-forms' ) : __( 'Edit Digest', 'entry-digest-for-gravity-forms' ) ); ?></h1>
		<p><a href="<?php echo esc_url( $base_url ); ?>">&larr; <?php esc_html_e( 'Back to all digests', 'entry-digest-for-gravity-forms' ); ?></a></p>

        <?php echo wp_kses_post( $notice ); ?>

		<?php if ( ! $gf ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Gravity Forms is not active - the form and field lists below are unavailable.', 'entry-digest-for-gravity-forms' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $base_url . '&action=' . $action . ( $d['id'] ? '&digest=' . rawurlencode( $d['id'] ) : '' ) ); ?>">
			<?php wp_nonce_field( 'dsagfe_save_digest' ); ?>
			<input type="hidden" name="dsagfe_digest[id]" value="<?php echo esc_attr( $d['id'] ); ?>">

			<!-- ── Section: Digest ───────────────────────────────────────── -->
			<div style="<?php echo esc_attr( $card_style ); ?>">
				<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Digest', 'entry-digest-for-gravity-forms' ); ?></h2>
				<table class="form-table" role="presentation"><tbody>

					<tr>
						<th scope="row"><label for="dsagfe_label"><?php esc_html_e( 'Digest name', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td><input type="text" id="dsagfe_label" name="dsagfe_digest[label]" value="<?php echo esc_attr( $d['label'] ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'For your reference (and used as the header on multi-form digests).', 'entry-digest-for-gravity-forms' ); ?></p></td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Form', 'entry-digest-for-gravity-forms' ); ?></th>
						<td>
							<?php if ( $gf && $all_forms ) : ?>
								<?php
								/**
								 * Filter whether the form selector accepts more than one form. Core is
								 * single-form (a radio list); an add-on may return true to allow choosing
								 * several. Core stores and processes whatever forms a digest holds.
								 */
								$multiple = (bool) apply_filters( 'dsagfe_form_selector_multiple', false );
								?>
								<fieldset>
									<p class="description" style="margin-bottom:8px;">
										<?php echo esc_html( $multiple
											? __( 'Select one or more forms for this digest.', 'entry-digest-for-gravity-forms' )
											: __( 'Choose the form this digest covers.', 'entry-digest-for-gravity-forms' ) ); ?>
									</p>
									<?php foreach ( $all_forms as $form ) : ?>
										<?php $fid = (string) $form['id']; $checked = in_array( (int) $fid, $d['form_ids'], true ); ?>
										<label style="display:block;margin-bottom:4px;">
											<input type="<?php echo esc_attr( $multiple ? 'checkbox' : 'radio' ); ?>" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $fid ); ?>" class="dsagfe-form-toggle" data-fid="<?php echo esc_attr( $fid ); ?>" <?php checked( $checked ); ?>>
											<?php echo esc_html( $form['title'] ); ?> <span style="color:#888;"><?php /* translators: %s: numeric form ID. */ printf( esc_html__( '(ID %s)', 'entry-digest-for-gravity-forms' ), esc_html( $fid ) ); ?></span>
										</label>
									<?php endforeach; ?>
								</fieldset>
								<?php if ( ! $multiple ) : ?>
								<p class="description" style="margin-top:6px;">
									<?php
									printf(
										/* translators: 1: opening anchor tag to the Pro page; 2: closing anchor tag. */
										esc_html__( 'Tip: combining several forms into a single digest is available in %1$sEntry Digest Pro%2$s.', 'entry-digest-for-gravity-forms' ),
										'<a href="' . esc_url( dsagfe_pro_url() ) . '" target="_blank" rel="noopener">',
										'</a>'
									); // phpcs:ignore WordPress.Security.EscapeOutput -- format string escaped via esc_html__(); anchor markup is hardcoded.
									?>
								</p>
								<?php endif; ?>
							<?php else : ?>
								<input type="number" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $d['form_ids'][0] ?? 1 ); ?>" min="1" class="small-text">
								<p class="description"><?php esc_html_e( "Enter a form ID (Gravity Forms inactive - can't list forms).", 'entry-digest-for-gravity-forms' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>

				</tbody></table>
			</div>

			<!-- ── Section: Email ────────────────────────────────────────── -->
			<div style="<?php echo esc_attr( $card_style ); ?>">
				<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Email', 'entry-digest-for-gravity-forms' ); ?></h2>
				<table class="form-table" role="presentation"><tbody>

					<tr>
						<th scope="row"><label for="dsagfe_to_email"><?php esc_html_e( 'Recipient email(s)', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td><textarea id="dsagfe_to_email" name="dsagfe_digest[to_email]" rows="2" class="large-text"><?php echo esc_textarea( $d['to_email'] ); ?></textarea>
							<p class="description"><?php
								/* translators: %s: example email addresses wrapped in a code tag. */
								printf( esc_html__( 'Comma-separated, e.g. %s', 'entry-digest-for-gravity-forms' ), '<code>alice@example.com, bob@example.com</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?></p></td>
					</tr>

					<?php
					/**
					 * Fires in the Email section, just after the recipient email row.
					 * Add-ons can render extra recipient controls here (for example,
					 * role-based routing) as additional table rows.
					 *
					 * @param array $d The digest configuration being edited.
					 */
					do_action( 'dsagfe_editor_after_recipients', $d );
					?>

					<tr>
						<th scope="row"><label for="dsagfe_subject"><?php esc_html_e( 'Email subject', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td><input type="text" id="dsagfe_subject" name="dsagfe_digest[email_subject]" value="<?php echo esc_attr( $d['email_subject'] ); ?>" class="regular-text"></td>
					</tr>

					<?php
					/**
					 * Fires in the Email section, after the subject row. Add-ons can
					 * render additional email options here (for example, custom
					 * branding or a file-attachment selector) as table rows.
					 *
					 * @param array $d The digest configuration being edited.
					 */
					do_action( 'dsagfe_editor_email_options', $d );
					?>

				</tbody></table>
			</div>

			<!-- ── Section: Schedule ─────────────────────────────────────── -->
			<div style="<?php echo esc_attr( $card_style ); ?>">
				<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Schedule', 'entry-digest-for-gravity-forms' ); ?></h2>
				<table class="form-table" role="presentation"><tbody>

					<tr>
						<th scope="row"><label for="dsagfe_freq"><?php esc_html_e( 'Recurring frequency', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td>
							<select id="dsagfe_freq" name="dsagfe_digest[frequency]">
								<option value="weekly" <?php selected( $d['frequency'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'entry-digest-for-gravity-forms' ); ?></option>
								<option value="daily"  <?php selected( $d['frequency'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'entry-digest-for-gravity-forms' ); ?></option>
								<option value="none"   <?php selected( $d['frequency'], 'none' ); ?>><?php esc_html_e( 'One-time only (no recurring)', 'entry-digest-for-gravity-forms' ); ?></option>
							</select>
							<p class="description"><?php
								/* translators: %s: the words "One-time only" wrapped in an em tag. */
								printf( esc_html__( 'Weekly covers the past 7 days; daily covers the past 24 hours. Choose %s to send just once on a date you pick below.', 'entry-digest-for-gravity-forms' ), '<em>' . esc_html__( 'One-time only', 'entry-digest-for-gravity-forms' ) . '</em>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?></p>
						</td>
					</tr>

					<tr class="dsagfe-weekly-row">
						<th scope="row"><label for="dsagfe_day"><?php esc_html_e( 'Send day', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td><select id="dsagfe_day" name="dsagfe_digest[send_day]">
							<?php foreach ( $days as $day ) : ?>
								<option value="<?php echo esc_attr( $day ); ?>" <?php selected( $d['send_day'], $day ); ?>><?php echo esc_html( dsagfe_day_label( $day ) ); ?></option>
							<?php endforeach; ?>
						</select> <span class="description"><?php esc_html_e( 'Weekly only.', 'entry-digest-for-gravity-forms' ); ?></span></td>
					</tr>

					<tr class="dsagfe-recurring-row">
						<th scope="row"><label for="dsagfe_time"><?php esc_html_e( 'Send time', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td><input type="time" id="dsagfe_time" name="dsagfe_digest[send_time]" value="<?php echo esc_attr( $d['send_time'] ); ?>">
							<p class="description"><?php
								/* translators: %s: site timezone string, e.g. America/Denver. */
								printf( esc_html__( 'For the recurring send. Site timezone: %s', 'entry-digest-for-gravity-forms' ), '<strong>' . esc_html( wp_timezone_string() ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?></p></td>
					</tr>

					<tr>
						<th scope="row"><label for="dsagfe_onetime"><?php esc_html_e( 'One-time send', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td>
							<input type="datetime-local" id="dsagfe_onetime" name="dsagfe_digest[onetime_at]" value="<?php echo esc_attr( $onetime_input ); ?>" min="<?php echo esc_attr( $onetime_min ); ?>">
							<?php if ( $onetime_input ) : ?>
								<button type="button" class="button button-small" id="dsagfe_onetime_clear"><?php esc_html_e( 'Clear', 'entry-digest-for-gravity-forms' ); ?></button>
							<?php endif; ?>
							<p class="description"><?php
								/* translators: %s: site timezone string, e.g. America/Denver. */
								printf( esc_html__( 'Optional. Pick a future date & time to send this digest once, on top of (or instead of) the recurring schedule. Site timezone: %s. The date clears itself automatically after it sends.', 'entry-digest-for-gravity-forms' ), '<strong>' . esc_html( wp_timezone_string() ) . '</strong>' ); // phpcs:ignore WordPress.Security.EscapeOutput
							?></p>
						</td>
					</tr>

					<tr class="dsagfe-onetime-row">
						<th scope="row"><label for="dsagfe_lookback"><?php esc_html_e( 'One-time lookback (days)', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td>
							<input type="number" id="dsagfe_lookback" name="dsagfe_digest[onetime_lookback_days]" value="<?php echo esc_attr( $lookback_val ); ?>" min="0" step="1" class="small-text">
							<p class="description">
								<?php
								/* translators: %s: the number 0 wrapped in a code tag. */
								printf( esc_html__( 'How far back the one-time send looks for entries. %s = every entry up to the send moment.', 'entry-digest-for-gravity-forms' ), '<code>0</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
								<?php if ( $forms_age > 0 ) : ?>
									<?php
									/* translators: %d: number of days since the form was created. */
									printf( esc_html( _n( 'This form was created about %d day ago - the default covers everything since then.', 'This form was created about %d days ago - the default covers everything since then.', $forms_age, 'entry-digest-for-gravity-forms' ) ), (int) $forms_age );
									?>
								<?php endif; ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="dsagfe_quiet"><?php esc_html_e( 'When there are no new entries', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td>
							<select id="dsagfe_quiet" name="dsagfe_digest[quiet_behavior]">
								<option value="send" <?php selected( $d['quiet_behavior'], 'send' ); ?>><?php esc_html_e( 'Send a "no new entries" note', 'entry-digest-for-gravity-forms' ); ?></option>
								<option value="skip" <?php selected( $d['quiet_behavior'], 'skip' ); ?>><?php esc_html_e( "Don't send anything", 'entry-digest-for-gravity-forms' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'By default a quiet period still gets a tidy "no new entries" email, so recipients are never left wondering. Choose "Don\'t send anything" to stay silent when nothing came in.', 'entry-digest-for-gravity-forms' ); ?></p>
						</td>
					</tr>

				</tbody></table>
			</div>

			<!-- ── Section: Entries & fields ─────────────────────────────── -->
			<div style="<?php echo esc_attr( $card_style ); ?>">
				<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Entries &amp; fields', 'entry-digest-for-gravity-forms' ); ?></h2>
				<table class="form-table" role="presentation"><tbody>

					<tr>
						<th scope="row"><?php esc_html_e( 'Entries right now', 'entry-digest-for-gravity-forms' ); ?></th>
						<td>
							<div id="dsagfe-count-preview" aria-live="polite" style="font-size:14px;line-height:1.5;min-height:22px;">
								<em><?php esc_html_e( 'Calculating…', 'entry-digest-for-gravity-forms' ); ?></em>
							</div>
							<p class="description"><?php
								/* translators: %s: a time window such as "past 7 days". */
								printf( esc_html__( 'How many active entries would be included if this digest ran right now, using your current settings. Updates live as you change them; the real send uses the same rolling window (%s) at send time.', 'entry-digest-for-gravity-forms' ), esc_html( $window_label ) );
							?></p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Entry links', 'entry-digest-for-gravity-forms' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="dsagfe_digest[link_entries]" value="1" <?php checked( ! empty( $d['link_entries'] ) ); ?>>
								<?php esc_html_e( 'Link each row to its entry in the WordPress admin', 'entry-digest-for-gravity-forms' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Adds an admin link on each entry’s date. It only works for recipients who can log in and view Gravity Forms entries, so turn it off when emailing people without admin access.', 'entry-digest-for-gravity-forms' ); ?></p>
						</td>
					</tr>

				</tbody></table>

				<p class="description" style="margin:4px 0 12px;"><?php esc_html_e( 'Choose which fields appear in the entry table for each form.', 'entry-digest-for-gravity-forms' ); ?></p>

				<?php
				// Render a config block for each currently-selected form (shown), plus
				// hidden blocks for the rest so selections persist when toggled on.
				foreach ( $all_forms as $form ) :
					$fid       = (string) $form['id'];
					$selected  = in_array( (int) $fid, $d['form_ids'], true );
					$field_map = dsagfe_build_field_map( $form );
					$sel_fields = (array) ( $d['fields'][ $fid ] ?? [] );
					?>
					<div class="dsagfe-form-block" data-fid="<?php echo esc_attr( $fid ); ?>" style="<?php echo $selected ? '' : 'display:none;'; ?>border:1px solid #dcdcde;border-radius:6px;padding:14px 18px;margin:0 0 14px 0;background:#fbfbfc;">
						<h3 style="margin-top:4px;"><?php echo esc_html( $form['title'] ); ?> <span style="color:#888;font-weight:400;"><?php /* translators: %s: numeric form ID. */ printf( esc_html__( '(ID %s)', 'entry-digest-for-gravity-forms' ), esc_html( $fid ) ); ?></span> <span class="dsagfe-form-count" data-fid="<?php echo esc_attr( $fid ); ?>" style="color:#2271b1;font-weight:400;font-size:13px;"></span></h3>

						<p style="font-weight:600;margin-bottom:6px;"><?php esc_html_e( 'Fields to include', 'entry-digest-for-gravity-forms' ); ?> <span style="font-weight:400;color:#888;"><?php esc_html_e( '(none checked = all)', 'entry-digest-for-gravity-forms' ); ?></span></p>
						<fieldset style="columns:2;max-width:680px;">
							<?php foreach ( $field_map as $key => $label ) : ?>
								<label style="display:block;margin-bottom:3px;">
									<input type="checkbox" name="dsagfe_digest[fields][<?php echo esc_attr( $fid ); ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( (string) $key, array_map( 'strval', $sel_fields ), true ) ); ?>>
									<?php echo esc_html( $label ); ?> <span style="color:#aaa;">(<?php echo esc_html( $key ); ?>)</span>
								</label>
							<?php endforeach; ?>
						</fieldset>

						<?php
						/**
						 * Fires inside each per-form configuration block in the digest
						 * editor. Add-ons can render per-form controls here (for example,
						 * conditional filtering rules).
						 *
						 * @param string $fid       The Gravity Forms form ID (as a string).
						 * @param array  $d         The digest configuration being edited.
						 * @param array  $field_map [ field_key => label ] for this form.
						 */
						do_action( 'dsagfe_editor_form_block', $fid, $d, $field_map );
						?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php submit_button( 'new' === $action ? __( 'Create Digest', 'entry-digest-for-gravity-forms' ) : __( 'Save Digest', 'entry-digest-for-gravity-forms' ), 'primary', 'dsagfe_save_digest' ); ?>
		</form>

		<!-- ── Section: Test send ────────────────────────────────────── -->
		<?php // This is its own form (outside the editor form) so it submits independently of Save. ?>
		<div style="<?php echo esc_attr( $card_style ); ?>">
			<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Test send', 'entry-digest-for-gravity-forms' ); ?></h2>
			<?php if ( $d['id'] ) : ?>
				<table class="form-table" role="presentation"><tbody>
					<tr>
						<th scope="row"><label for="dsagfe_test_email"><?php esc_html_e( 'Send a test to', 'entry-digest-for-gravity-forms' ); ?></label></th>
						<td>
							<form method="post" action="<?php echo esc_url( $base_url . '&action=edit&digest=' . rawurlencode( $d['id'] ) ); ?>" style="margin:0;">
								<?php wp_nonce_field( 'dsagfe_send_test' ); ?>
								<input type="hidden" name="digest_id" value="<?php echo esc_attr( $d['id'] ); ?>">
								<input type="email" id="dsagfe_test_email" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" class="regular-text" placeholder="you@example.com">
								<button type="submit" name="dsagfe_send_test" class="button"><?php esc_html_e( 'Send test', 'entry-digest-for-gravity-forms' ); ?></button>
								<p class="description"><?php esc_html_e( 'Emails this digest - using its current saved settings - to just this address. Your real recipient list is never contacted, and the schedule is unchanged. Save any edits above first. A test always sends, even if there are no new entries.', 'entry-digest-for-gravity-forms' ); ?></p>
							</form>
						</td>
					</tr>
				</tbody></table>
			<?php else : ?>
				<p class="description" style="margin:12px 0;"><?php esc_html_e( 'Save the digest first, then a test-send field appears here so you can preview it to yourself.', 'entry-digest-for-gravity-forms' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
	// The editor's JavaScript (form-block toggling, schedule-row visibility, and
	// the live entry-count preview) is enqueued from admin/enqueue.php as
	// admin/js/editor.js, with its data passed via wp_localize_script().
}
