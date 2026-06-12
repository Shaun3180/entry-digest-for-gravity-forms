<?php
defined( 'ABSPATH' ) || exit;

/**
 * The digest editor screen (new or edit).
 */
function dsagfe_render_editor( string $action, string $notice ): void {
	$is_pro   = dsagfe_is_pro();
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
	$lock      = $is_pro ? '' : 'disabled';
	$ops       = dsagfe_filter_operators();

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
	?>
	<div class="wrap">
        <h1><?php echo esc_html( 'new' === $action ? __( 'Add Digest', 'entry-digest-for-gravity-forms' ) : __( 'Edit Digest', 'entry-digest-for-gravity-forms' ) ); ?></h1>
		<p><a href="<?php echo esc_url( $base_url ); ?>">&larr; <?php esc_html_e( 'Back to all digests', 'entry-digest-for-gravity-forms' ); ?></a></p>

        <?php echo esc_html( $notice ); ?>

		<?php if ( ! $gf ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'Gravity Forms is not active — the form and field lists below are unavailable.', 'entry-digest-for-gravity-forms' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $base_url . '&action=' . $action . ( $d['id'] ? '&digest=' . rawurlencode( $d['id'] ) : '' ) ); ?>">
			<?php wp_nonce_field( 'dsagfe_save_digest' ); ?>
			<input type="hidden" name="dsagfe_digest[id]" value="<?php echo esc_attr( $d['id'] ); ?>">

			<table class="form-table" role="presentation"><tbody>

				<tr>
					<th scope="row"><label for="dsagfe_label"><?php esc_html_e( 'Digest name', 'entry-digest-for-gravity-forms' ); ?></label></th>
					<td><input type="text" id="dsagfe_label" name="dsagfe_digest[label]" value="<?php echo esc_attr( $d['label'] ); ?>" class="regular-text">
						<p class="description"><?php esc_html_e( 'For your reference (and used as the header on multi-form digests).', 'entry-digest-for-gravity-forms' ); ?></p></td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Forms', 'entry-digest-for-gravity-forms' ); ?> <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<?php if ( $gf && $all_forms ) : ?>
							<fieldset>
								<p class="description" style="margin-bottom:8px;">
									<?php echo $is_pro ? esc_html__( 'Select one or more forms to aggregate into this digest.', 'entry-digest-for-gravity-forms' ) : esc_html__( 'Free plan: one form per digest. Multi-form aggregation is a Pro feature.', 'entry-digest-for-gravity-forms' ); ?>
								</p>
								<?php foreach ( $all_forms as $form ) : ?>
									<?php $fid = (string) $form['id']; $checked = in_array( (int) $fid, $d['form_ids'], true ); ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="<?php echo $is_pro ? 'checkbox' : 'radio'; ?>" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $fid ); ?>" class="dsagfe-form-toggle" data-fid="<?php echo esc_attr( $fid ); ?>" <?php checked( $checked ); ?>>
										<?php echo esc_html( $form['title'] ); ?> <span style="color:#888;"><?php /* translators: %s: numeric form ID. */ printf( esc_html__( '(ID %s)', 'entry-digest-for-gravity-forms' ), esc_html( $fid ) ); ?></span>
									</label>
								<?php endforeach; ?>
							</fieldset>
						<?php else : ?>
							<input type="number" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $d['form_ids'][0] ?? 1 ); ?>" min="1" class="small-text">
							<p class="description"><?php esc_html_e( "Enter a form ID (Gravity Forms inactive — can't list forms).", 'entry-digest-for-gravity-forms' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_to_email"><?php esc_html_e( 'Recipient email(s)', 'entry-digest-for-gravity-forms' ); ?></label></th>
					<td><textarea id="dsagfe_to_email" name="dsagfe_digest[to_email]" rows="2" class="large-text"><?php echo esc_textarea( $d['to_email'] ); ?></textarea>
						<p class="description"><?php
							/* translators: %s: example email addresses wrapped in a code tag. */
							printf( esc_html__( 'Comma-separated, e.g. %s', 'entry-digest-for-gravity-forms' ), '<code>alice@example.com, bob@example.com</code>' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?></p></td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Send to roles', 'entry-digest-for-gravity-forms' ); ?> <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<fieldset>
							<p class="description" style="margin-bottom:8px;"><?php esc_html_e( 'Also deliver to every user in the selected role(s).', 'entry-digest-for-gravity-forms' ); ?></p>
							<?php foreach ( wp_roles()->get_names() as $role_key => $role_name ) : ?>
								<label style="display:inline-block;margin:0 14px 4px 0;">
                                    <input type="checkbox" name="dsagfe_digest[roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $d['roles'], true ) ); ?> <?php echo esc_attr( $lock ); ?>>
									<?php echo esc_html( translate_user_role( $role_name ) ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<?php if ( ! $is_pro ) : ?><p class="description"><?php
							/* translators: 1: opening anchor tag; 2: closing anchor tag. */
							printf( esc_html__( '%1$sUpgrade to Pro%2$s to route by role.', 'entry-digest-for-gravity-forms' ), '<a href="' . esc_url( dsagfe_upgrade_url() ) . '">', '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?></p><?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_subject"><?php esc_html_e( 'Email subject', 'entry-digest-for-gravity-forms' ); ?></label></th>
					<td><input type="text" id="dsagfe_subject" name="dsagfe_digest[email_subject]" value="<?php echo esc_attr( $d['email_subject'] ); ?>" class="regular-text"></td>
				</tr>

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
								printf( esc_html( _n( 'This form was created about %d day ago — the default covers everything since then.', 'This form was created about %d days ago — the default covers everything since then.', $forms_age, 'entry-digest-for-gravity-forms' ) ), (int) $forms_age );
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
					<th scope="row"><?php esc_html_e( 'Attachment', 'entry-digest-for-gravity-forms' ); ?> <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<select name="dsagfe_digest[attach_format]" <?php echo esc_attr( $lock ); ?>>
							<option value="none" <?php selected( $d['attach_format'], 'none' ); ?>><?php esc_html_e( 'None', 'entry-digest-for-gravity-forms' ); ?></option>
							<option value="xlsx" <?php selected( $d['attach_format'], 'xlsx' ); ?>><?php esc_html_e( 'Excel (.xlsx)', 'entry-digest-for-gravity-forms' ); ?></option>
							<option value="csv"  <?php selected( $d['attach_format'], 'csv' ); ?>><?php esc_html_e( 'CSV', 'entry-digest-for-gravity-forms' ); ?></option>
						</select>
						<p class="description">
							<?php esc_html_e( "Attach the full period's entries. Excel produces one sheet per form; CSV produces one file per form.", 'entry-digest-for-gravity-forms' ); ?>
							<?php if ( ! $is_pro ) : ?><a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>"><?php esc_html_e( 'Pro feature', 'entry-digest-for-gravity-forms' ); ?></a>.<?php endif; ?>
						</p>
					</td>
				</tr>

			</tbody></table>

			<h2><?php esc_html_e( 'Per-form fields & filters', 'entry-digest-for-gravity-forms' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Choose which fields appear, and (Pro) which entries to include.', 'entry-digest-for-gravity-forms' ); ?></p>

			<?php
			$form_lookup = [];
			foreach ( $all_forms as $f ) {
				$form_lookup[ (string) $f['id'] ] = $f;
			}
			// Render a config block for each currently-selected form (shown), plus
			// hidden blocks for the rest so selections persist when toggled on.
			foreach ( $all_forms as $form ) :
				$fid       = (string) $form['id'];
				$selected  = in_array( (int) $fid, $d['form_ids'], true );
				$field_map = dsagfe_build_field_map( $form );
				$sel_fields = (array) ( $d['fields'][ $fid ] ?? [] );
				$f_filters  = (array) ( $d['filters'][ $fid ]['rules'] ?? [] );
				$f_logic    = $d['filters'][ $fid ]['logic'] ?? 'all';
				?>
				<div class="dsagfe-form-block" data-fid="<?php echo esc_attr( $fid ); ?>" style="<?php echo $selected ? '' : 'display:none;'; ?>border:1px solid #dcdcde;border-radius:6px;padding:14px 18px;margin:0 0 14px 0;background:#fff;">
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

					<p style="font-weight:600;margin:14px 0 6px;"><?php esc_html_e( 'Conditional filtering', 'entry-digest-for-gravity-forms' ); ?> <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></p>
					<?php if ( $is_pro ) : ?>
						<p class="description" style="margin-bottom:6px;">
							<?php esc_html_e( 'Match', 'entry-digest-for-gravity-forms' ); ?>
							<select name="dsagfe_digest[filter_logic][<?php echo esc_attr( $fid ); ?>]">
								<option value="all" <?php selected( $f_logic, 'all' ); ?>><?php esc_html_e( 'all', 'entry-digest-for-gravity-forms' ); ?></option>
								<option value="any" <?php selected( $f_logic, 'any' ); ?>><?php esc_html_e( 'any', 'entry-digest-for-gravity-forms' ); ?></option>
							</select>
							<?php esc_html_e( 'of these rules:', 'entry-digest-for-gravity-forms' ); ?>
						</p>
						<table class="dsagfe-filters" data-fid="<?php echo esc_attr( $fid ); ?>" style="margin-bottom:8px;">
							<tbody>
								<?php
								$render_rule = static function ( $i, $rule, $field_map, $ops, $fid ) {
									$rf = $rule['field'] ?? '';
									$ro = $rule['op'] ?? 'is';
									$rv = $rule['value'] ?? '';
									?>
									<tr>
										<td>
											<select name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][field]">
												<option value="">— <?php esc_html_e( 'field', 'entry-digest-for-gravity-forms' ); ?> —</option>
												<?php foreach ( $field_map as $k => $lab ) : ?>
													<option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $rf, (string) $k ); ?>><?php echo esc_html( $lab ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
										<td>
											<select name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][op]">
												<?php foreach ( $ops as $ok => $olabel ) : ?>
													<option value="<?php echo esc_attr( $ok ); ?>" <?php selected( $ro, $ok ); ?>><?php echo esc_html( $olabel ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
										<td><input type="text" name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $rv ); ?>" placeholder="<?php esc_attr_e( 'value', 'entry-digest-for-gravity-forms' ); ?>"></td>
									</tr>
									<?php
								};
								$existing = $f_filters ?: [ [ 'field' => '', 'op' => 'is', 'value' => '' ] ];
								foreach ( $existing as $i => $rule ) {
									$render_rule( $i, $rule, $field_map, $ops, $fid );
								}
								// One spare blank row for adding a rule.
								$render_rule( count( $existing ), [ 'field' => '', 'op' => 'is', 'value' => '' ], $field_map, $ops, $fid );
								?>
							</tbody>
						</table>
						<p class="description"><?php esc_html_e( 'Leave fields blank to ignore a row. Add more rules by saving and reopening.', 'entry-digest-for-gravity-forms' ); ?></p>
					<?php else : ?>
						<p class="description"><?php
							/* translators: 1: an example condition in em tags; 2: opening anchor tag; 3: closing anchor tag. */
							printf( esc_html__( 'Send only entries matching conditions (e.g. %1$s). %2$sUpgrade to Pro%3$s.', 'entry-digest-for-gravity-forms' ), '<em>' . esc_html__( 'Status is Complete', 'entry-digest-for-gravity-forms' ) . '</em>', '<a href="' . esc_url( dsagfe_upgrade_url() ) . '">', '</a>' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php submit_button( 'new' === $action ? __( 'Create Digest', 'entry-digest-for-gravity-forms' ) : __( 'Save Digest', 'entry-digest-for-gravity-forms' ), 'primary', 'dsagfe_save_digest' ); ?>
		</form>
	</div>

	<script>
	var DSAGFE_I18N = {
		calculating: <?php echo wp_json_encode( __( 'Calculating…', 'entry-digest-for-gravity-forms' ) ); ?>,
		unable:      <?php echo wp_json_encode( __( 'Unable to calculate.', 'entry-digest-for-gravity-forms' ) ); ?>,
		gfInactive:  <?php echo wp_json_encode( __( 'Gravity Forms is not active — counts unavailable.', 'entry-digest-for-gravity-forms' ) ); ?>,
		selectForm:  <?php echo wp_json_encode( __( 'Select a form to see a count.', 'entry-digest-for-gravity-forms' ) ); ?>,
		entry:       <?php echo wp_json_encode( __( 'entry', 'entry-digest-for-gravity-forms' ) ); ?>,
		entries:     <?php echo wp_json_encode( __( 'entries', 'entry-digest-for-gravity-forms' ) ); ?>,
		inThe:       <?php echo wp_json_encode( _x( 'in the', 'precedes a time window such as "past 7 days"', 'entry-digest-for-gravity-forms' ) ); ?>,
		inWord:      <?php echo wp_json_encode( _x( 'in', 'precedes a time window in the per-form count badge', 'entry-digest-for-gravity-forms' ) ); ?>
	};
	</script>

	<script>
	( function () {
		function sync() {
			var checked = {};
			document.querySelectorAll( '.dsagfe-form-toggle' ).forEach( function ( el ) {
				if ( el.checked ) { checked[ el.dataset.fid ] = true; }
			} );
			document.querySelectorAll( '.dsagfe-form-block' ).forEach( function ( block ) {
				block.style.display = checked[ block.dataset.fid ] ? '' : 'none';
			} );
		}
		document.querySelectorAll( '.dsagfe-form-toggle' ).forEach( function ( el ) {
			el.addEventListener( 'change', sync );
		} );
		sync();
	} )();
	</script>

	<script>
	( function () {
		var freq     = document.getElementById( 'dsagfe_freq' );
		var onetime  = document.getElementById( 'dsagfe_onetime' );
		var clearBtn = document.getElementById( 'dsagfe_onetime_clear' );
		if ( ! freq ) { return; }

		function show( sel, on ) {
			document.querySelectorAll( sel ).forEach( function ( el ) {
				el.style.display = on ? '' : 'none';
			} );
		}
		function sync() {
			var f = freq.value;
			show( '.dsagfe-weekly-row', f === 'weekly' );           // send day: weekly only
			show( '.dsagfe-recurring-row', f === 'daily' || f === 'weekly' ); // recurring send time
			show( '.dsagfe-onetime-row', !! ( onetime && onetime.value ) );   // lookback: only with a date
		}
		freq.addEventListener( 'change', sync );
		if ( onetime ) { onetime.addEventListener( 'input', sync ); }
		if ( clearBtn && onetime ) {
			clearBtn.addEventListener( 'click', function () {
				onetime.value = '';
				sync();
				onetime.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		}
		sync();
	} )();
	</script>

	<script>
	var DSAGFE_COUNT = {
		url:   <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'dsagfe_entry_count' ) ); ?>,
		gf:    <?php echo $gf ? 'true' : 'false'; ?>
	};
	( function () {
		var preview = document.getElementById( 'dsagfe-count-preview' );
		if ( ! preview || typeof DSAGFE_COUNT === 'undefined' ) { return; }
		if ( ! DSAGFE_COUNT.gf ) {
			preview.innerHTML = '<em>' + esc( DSAGFE_I18N.gfInactive ) + '</em>';
			return;
		}
		var theForm = preview.closest( 'form' );
		var badges  = document.querySelectorAll( '.dsagfe-form-count' );
		var timer   = null;
		var seq     = 0;

		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s; return d.innerHTML; }

		function render( data ) {
			var total = data.total | 0;
			var word  = ( total === 1 ) ? DSAGFE_I18N.entry : DSAGFE_I18N.entries;
			var html  = '<strong>' + total + '</strong> ' + esc( word ) + ' ' + esc( DSAGFE_I18N.inThe ) + ' ' + esc( data.window );
			var fids  = Object.keys( data.per_form || {} );
			if ( fids.length > 1 ) {
				html += '<ul style="margin:6px 0 0 18px;list-style:disc;">';
				fids.forEach( function ( fid ) {
					var t = ( data.titles && data.titles[ fid ] ) ? data.titles[ fid ] : ( 'Form ' + fid );
					html += '<li>' + esc( t ) + ': <strong>' + ( data.per_form[ fid ] | 0 ) + '</strong></li>';
				} );
				html += '</ul>';
			} else if ( fids.length === 0 ) {
				html = '<em>' + esc( DSAGFE_I18N.selectForm ) + '</em>';
			}
			preview.innerHTML = html;

			badges.forEach( function ( b ) {
				var fid = b.dataset.fid;
				if ( data.per_form && Object.prototype.hasOwnProperty.call( data.per_form, fid ) ) {
					var n = data.per_form[ fid ] | 0;
					var w = ( n === 1 ) ? DSAGFE_I18N.entry : DSAGFE_I18N.entries;
					b.textContent = '· ' + n + ' ' + w + ' ' + DSAGFE_I18N.inWord + ' ' + data.window;
				} else {
					b.textContent = '';
				}
			} );
		}

		function update() {
			var mySeq = ++seq;
			preview.innerHTML = '<em>' + esc( DSAGFE_I18N.calculating ) + '</em>';
			var fd = new FormData( theForm );
			fd.append( 'action', 'dsagfe_entry_count' );
			fd.append( 'nonce', DSAGFE_COUNT.nonce );
			fetch( DSAGFE_COUNT.url, { method: 'POST', body: fd, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( json ) {
					if ( mySeq !== seq ) { return; }
					if ( json && json.success ) {
						render( json.data );
					} else {
						preview.innerHTML = '<em>' + esc( ( json && json.data && json.data.message ) || DSAGFE_I18N.unable ) + '</em>';
					}
				} )
				.catch( function () {
					if ( mySeq === seq ) { preview.innerHTML = '<em>' + esc( DSAGFE_I18N.unable ) + '</em>'; }
				} );
		}

		function debounced() { clearTimeout( timer ); timer = setTimeout( update, 350 ); }

		theForm.addEventListener( 'change', function ( e ) {
			if ( e.target.closest && (
				e.target.id === 'dsagfe_freq' ||
				e.target.id === 'dsagfe_onetime' ||
				e.target.id === 'dsagfe_lookback' ||
				e.target.classList.contains( 'dsagfe-form-toggle' ) ||
				( e.target.name && ( e.target.name.indexOf( '[filters]' ) !== -1 || e.target.name.indexOf( '[filter_logic]' ) !== -1 ) )
			) ) { debounced(); }
		} );
		theForm.addEventListener( 'input', function ( e ) {
			if ( e.target.name && ( e.target.name.indexOf( '[filters]' ) !== -1 || e.target.id === 'dsagfe_lookback' ) ) { debounced(); }
		} );

		update();
	} )();
	</script>
	<?php
}
