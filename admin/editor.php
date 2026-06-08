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
		$id = isset( $_GET['digest'] ) ? sanitize_text_field( wp_unslash( $_GET['digest'] ) ) : '';
		$d  = dsagfe_get_digest( $id );
		if ( ! $d ) {
			echo '<div class="wrap"><div class="notice notice-error"><p>Digest not found.</p></div></div>';
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
	?>
	<div class="wrap">
		<h1><?php echo 'new' === $action ? 'Add Digest' : 'Edit Digest'; ?></h1>
		<p><a href="<?php echo esc_url( $base_url ); ?>">&larr; Back to all digests</a></p>

		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( ! $gf ) : ?>
			<div class="notice notice-error"><p>Gravity Forms is not active — the form and field lists below are unavailable.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( $base_url . '&action=' . $action . ( $d['id'] ? '&digest=' . rawurlencode( $d['id'] ) : '' ) ); ?>">
			<?php wp_nonce_field( 'dsagfe_save_digest' ); ?>
			<input type="hidden" name="dsagfe_digest[id]" value="<?php echo esc_attr( $d['id'] ); ?>">

			<table class="form-table" role="presentation"><tbody>

				<tr>
					<th scope="row"><label for="dsagfe_label">Digest name</label></th>
					<td><input type="text" id="dsagfe_label" name="dsagfe_digest[label]" value="<?php echo esc_attr( $d['label'] ); ?>" class="regular-text">
						<p class="description">For your reference (and used as the header on multi-form digests).</p></td>
				</tr>

				<tr>
					<th scope="row">Forms <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<?php if ( $gf && $all_forms ) : ?>
							<fieldset>
								<p class="description" style="margin-bottom:8px;">
									<?php echo $is_pro ? 'Select one or more forms to aggregate into this digest.' : 'Free plan: one form per digest. Multi-form aggregation is a Pro feature.'; ?>
								</p>
								<?php foreach ( $all_forms as $form ) : ?>
									<?php $fid = (string) $form['id']; $checked = in_array( (int) $fid, $d['form_ids'], true ); ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="<?php echo $is_pro ? 'checkbox' : 'radio'; ?>" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $fid ); ?>" class="dsagfe-form-toggle" data-fid="<?php echo esc_attr( $fid ); ?>" <?php checked( $checked ); ?>>
										<?php echo esc_html( $form['title'] ); ?> <span style="color:#888;">(ID <?php echo esc_html( $fid ); ?>)</span>
									</label>
								<?php endforeach; ?>
							</fieldset>
						<?php else : ?>
							<input type="number" name="dsagfe_digest[form_ids][]" value="<?php echo esc_attr( $d['form_ids'][0] ?? 1 ); ?>" min="1" class="small-text">
							<p class="description">Enter a form ID (Gravity Forms inactive — can't list forms).</p>
						<?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_to_email">Recipient email(s)</label></th>
					<td><textarea id="dsagfe_to_email" name="dsagfe_digest[to_email]" rows="2" class="large-text"><?php echo esc_textarea( $d['to_email'] ); ?></textarea>
						<p class="description">Comma-separated, e.g. <code>alice@example.com, bob@example.com</code></p></td>
				</tr>

				<tr>
					<th scope="row">Send to roles <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<fieldset>
							<p class="description" style="margin-bottom:8px;">Also deliver to every user in the selected role(s).</p>
							<?php foreach ( wp_roles()->get_names() as $role_key => $role_name ) : ?>
								<label style="display:inline-block;margin:0 14px 4px 0;">
									<input type="checkbox" name="dsagfe_digest[roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $d['roles'], true ) ); ?> <?php echo $lock; ?>>
									<?php echo esc_html( $role_name ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
						<?php if ( ! $is_pro ) : ?><p class="description"><a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>">Upgrade to Pro</a> to route by role.</p><?php endif; ?>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_subject">Email subject</label></th>
					<td><input type="text" id="dsagfe_subject" name="dsagfe_digest[email_subject]" value="<?php echo esc_attr( $d['email_subject'] ); ?>" class="regular-text"></td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_freq">Frequency</label></th>
					<td>
						<select id="dsagfe_freq" name="dsagfe_digest[frequency]">
							<option value="weekly" <?php selected( $d['frequency'], 'weekly' ); ?>>Weekly</option>
							<option value="daily"  <?php selected( $d['frequency'], 'daily' ); ?>>Daily</option>
						</select>
						<p class="description">Weekly covers the past 7 days; daily covers the past 24 hours.</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_day">Send day</label></th>
					<td><select id="dsagfe_day" name="dsagfe_digest[send_day]">
						<?php foreach ( $days as $day ) : ?>
							<option value="<?php echo esc_attr( $day ); ?>" <?php selected( $d['send_day'], $day ); ?>><?php echo esc_html( ucfirst( $day ) ); ?></option>
						<?php endforeach; ?>
					</select> <span class="description">Weekly only.</span></td>
				</tr>

				<tr>
					<th scope="row"><label for="dsagfe_time">Send time</label></th>
					<td><input type="time" id="dsagfe_time" name="dsagfe_digest[send_time]" value="<?php echo esc_attr( $d['send_time'] ); ?>">
						<p class="description">Site timezone: <strong><?php echo esc_html( wp_timezone_string() ); ?></strong></p></td>
				</tr>

				<tr>
					<th scope="row">Entries right now</th>
					<td>
						<div id="dsagfe-count-preview" aria-live="polite" style="font-size:14px;line-height:1.5;min-height:22px;">
							<em>Calculating…</em>
						</div>
						<p class="description">
							How many active entries would be included if this digest ran right now, using the selected frequency<?php echo $gf ? ', form(s)' : ''; ?><?php echo $is_pro ? ' and filters' : ''; ?>. Updates live as you change those settings; the real send uses the same rolling window (<?php echo esc_html( $d['frequency'] === 'daily' ? 'past 24 hours' : 'past 7 days' ); ?> for the current frequency) at send time.
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Attachment <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></th>
					<td>
						<select name="dsagfe_digest[attach_format]" <?php echo $lock; ?>>
							<option value="none" <?php selected( $d['attach_format'], 'none' ); ?>>None</option>
							<option value="xlsx" <?php selected( $d['attach_format'], 'xlsx' ); ?>>Excel (.xlsx)</option>
							<option value="csv"  <?php selected( $d['attach_format'], 'csv' ); ?>>CSV</option>
						</select>
						<p class="description">
							Attach the full period's entries. Excel produces one sheet per form; CSV produces one file per form.
							<?php if ( ! $is_pro ) : ?><a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>">Pro feature</a>.<?php endif; ?>
						</p>
					</td>
				</tr>

			</tbody></table>

			<h2>Per-form fields &amp; filters</h2>
			<p class="description">Choose which fields appear, and (Pro) which entries to include.</p>

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
					<h3 style="margin-top:4px;"><?php echo esc_html( $form['title'] ); ?> <span style="color:#888;font-weight:400;">(ID <?php echo esc_html( $fid ); ?>)</span> <span class="dsagfe-form-count" data-fid="<?php echo esc_attr( $fid ); ?>" style="color:#2271b1;font-weight:400;font-size:13px;"></span></h3>

					<p style="font-weight:600;margin-bottom:6px;">Fields to include <span style="font-weight:400;color:#888;">(none checked = all)</span></p>
					<fieldset style="columns:2;max-width:680px;">
						<?php foreach ( $field_map as $key => $label ) : ?>
							<label style="display:block;margin-bottom:3px;">
								<input type="checkbox" name="dsagfe_digest[fields][<?php echo esc_attr( $fid ); ?>][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( (string) $key, array_map( 'strval', $sel_fields ), true ) ); ?>>
								<?php echo esc_html( $label ); ?> <span style="color:#aaa;">(<?php echo esc_html( $key ); ?>)</span>
							</label>
						<?php endforeach; ?>
					</fieldset>

					<p style="font-weight:600;margin:14px 0 6px;">Conditional filtering <?php echo $is_pro ? '' : dsagfe_pro_badge(); // phpcs:ignore ?></p>
					<?php if ( $is_pro ) : ?>
						<p class="description" style="margin-bottom:6px;">
							Match
							<select name="dsagfe_digest[filter_logic][<?php echo esc_attr( $fid ); ?>]">
								<option value="all" <?php selected( $f_logic, 'all' ); ?>>all</option>
								<option value="any" <?php selected( $f_logic, 'any' ); ?>>any</option>
							</select>
							of these rules:
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
												<option value="">— field —</option>
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
										<td><input type="text" name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $rv ); ?>" placeholder="value"></td>
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
						<p class="description">Leave fields blank to ignore a row. Add more rules by saving and reopening.</p>
					<?php else : ?>
						<p class="description">Send only entries matching conditions (e.g. <em>Status is Complete</em>). <a href="<?php echo esc_url( dsagfe_upgrade_url() ); ?>">Upgrade to Pro</a>.</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<?php submit_button( 'new' === $action ? 'Create Digest' : 'Save Digest', 'primary', 'dsagfe_save_digest' ); ?>
		</form>
	</div>

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
	var DSAGFE_COUNT = {
		url:   <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'dsagfe_entry_count' ) ); ?>,
		gf:    <?php echo $gf ? 'true' : 'false'; ?>
	};
	( function () {
		var preview = document.getElementById( 'dsagfe-count-preview' );
		if ( ! preview || typeof DSAGFE_COUNT === 'undefined' ) { return; }
		if ( ! DSAGFE_COUNT.gf ) {
			preview.innerHTML = '<em>Gravity Forms is not active — counts unavailable.</em>';
			return;
		}
		var theForm = preview.closest( 'form' );
		var badges  = document.querySelectorAll( '.dsagfe-form-count' );
		var timer   = null;
		var seq     = 0;

		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s; return d.innerHTML; }

		function render( data ) {
			var total = data.total | 0;
			var html  = '<strong>' + total + '</strong> ' + ( total === 1 ? 'entry' : 'entries' ) + ' in the ' + esc( data.window );
			var fids  = Object.keys( data.per_form || {} );
			if ( fids.length > 1 ) {
				html += '<ul style="margin:6px 0 0 18px;list-style:disc;">';
				fids.forEach( function ( fid ) {
					var t = ( data.titles && data.titles[ fid ] ) ? data.titles[ fid ] : ( 'Form ' + fid );
					html += '<li>' + esc( t ) + ': <strong>' + ( data.per_form[ fid ] | 0 ) + '</strong></li>';
				} );
				html += '</ul>';
			} else if ( fids.length === 0 ) {
				html = '<em>Select a form to see a count.</em>';
			}
			preview.innerHTML = html;

			badges.forEach( function ( b ) {
				var fid = b.dataset.fid;
				if ( data.per_form && Object.prototype.hasOwnProperty.call( data.per_form, fid ) ) {
					var n = data.per_form[ fid ] | 0;
					b.textContent = '· ' + n + ' ' + ( n === 1 ? 'entry' : 'entries' ) + ' in ' + data.window;
				} else {
					b.textContent = '';
				}
			} );
		}

		function update() {
			var mySeq = ++seq;
			preview.innerHTML = '<em>Calculating…</em>';
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
						preview.innerHTML = '<em>' + esc( ( json && json.data && json.data.message ) || 'Unable to calculate.' ) + '</em>';
					}
				} )
				.catch( function () {
					if ( mySeq === seq ) { preview.innerHTML = '<em>Unable to calculate.</em>'; }
				} );
		}

		function debounced() { clearTimeout( timer ); timer = setTimeout( update, 350 ); }

		theForm.addEventListener( 'change', function ( e ) {
			if ( e.target.closest && (
				e.target.id === 'dsagfe_freq' ||
				e.target.classList.contains( 'dsagfe-form-toggle' ) ||
				( e.target.name && ( e.target.name.indexOf( '[filters]' ) !== -1 || e.target.name.indexOf( '[filter_logic]' ) !== -1 ) )
			) ) { debounced(); }
		} );
		theForm.addEventListener( 'input', function ( e ) {
			if ( e.target.name && e.target.name.indexOf( '[filters]' ) !== -1 ) { debounced(); }
		} );

		update();
	} )();
	</script>
	<?php
}
