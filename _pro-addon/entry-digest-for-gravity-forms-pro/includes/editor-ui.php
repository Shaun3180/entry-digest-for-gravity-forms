<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro editor UI: inject the role-recipient, attachment, and conditional-filter
 * controls into the free plugin's digest editor, persist them on save, and make
 * the live entry-count preview reflect filtering.
 *
 * All markup here mirrors the field names the free plugin already understands
 * (dsagfe_digest[roles], [filters], [filter_logic], [attach_format]).
 */

// ── Role recipients (after the recipient email row) ──────────────────────────
add_action( 'dsagfe_editor_after_recipients', 'edfgfp_editor_roles_row' );
function edfgfp_editor_roles_row( array $d ): void {
	$roles = (array) ( $d['roles'] ?? [] );
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Send to roles', 'entry-digest-for-gravity-forms-pro' ); ?></th>
		<td>
			<fieldset>
				<p class="description" style="margin-bottom:8px;"><?php esc_html_e( 'Also deliver to every user in the selected role(s).', 'entry-digest-for-gravity-forms-pro' ); ?></p>
				<?php foreach ( wp_roles()->get_names() as $role_key => $role_name ) : ?>
					<label style="display:inline-block;margin:0 14px 4px 0;">
						<input type="checkbox" name="dsagfe_digest[roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $roles, true ) ); ?>>
						<?php echo esc_html( translate_user_role( $role_name ) ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</td>
	</tr>
	<?php
}

// ── Attachment selector (after the quiet-period row) ─────────────────────────
add_action( 'dsagfe_editor_after_schedule', 'edfgfp_editor_attachment_row' );
function edfgfp_editor_attachment_row( array $d ): void {
	$fmt = $d['attach_format'] ?? 'none';
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Attachment', 'entry-digest-for-gravity-forms-pro' ); ?></th>
		<td>
			<select name="dsagfe_digest[attach_format]">
				<option value="none" <?php selected( $fmt, 'none' ); ?>><?php esc_html_e( 'None', 'entry-digest-for-gravity-forms-pro' ); ?></option>
				<option value="xlsx" <?php selected( $fmt, 'xlsx' ); ?>><?php esc_html_e( 'Excel (.xlsx)', 'entry-digest-for-gravity-forms-pro' ); ?></option>
				<option value="csv"  <?php selected( $fmt, 'csv' ); ?>><?php esc_html_e( 'CSV', 'entry-digest-for-gravity-forms-pro' ); ?></option>
			</select>
			<p class="description"><?php esc_html_e( "Attach the full period's entries. Excel produces one sheet per form; CSV produces one file per form.", 'entry-digest-for-gravity-forms-pro' ); ?></p>
		</td>
	</tr>
	<?php
}

// ── Conditional filtering (inside each per-form block) ───────────────────────
add_action( 'dsagfe_editor_form_block', 'edfgfp_editor_filter_ui', 10, 3 );
function edfgfp_editor_filter_ui( string $fid, array $d, array $field_map ): void {
	$ops       = dsagfe_filter_operators();
	$f_filters = (array) ( $d['filters'][ $fid ]['rules'] ?? [] );
	$f_logic   = $d['filters'][ $fid ]['logic'] ?? 'all';

	$render_rule = static function ( $i, $rule, $field_map, $ops, $fid ) {
		$rf = $rule['field'] ?? '';
		$ro = $rule['op'] ?? 'is';
		$rv = $rule['value'] ?? '';
		?>
		<tr>
			<td>
				<select name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][field]">
					<option value="">— <?php esc_html_e( 'field', 'entry-digest-for-gravity-forms-pro' ); ?> —</option>
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
			<td><input type="text" name="dsagfe_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $rv ); ?>" placeholder="<?php esc_attr_e( 'value', 'entry-digest-for-gravity-forms-pro' ); ?>"></td>
		</tr>
		<?php
	};
	?>
	<p style="font-weight:600;margin:14px 0 6px;"><?php esc_html_e( 'Conditional filtering', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<p class="description" style="margin-bottom:6px;">
		<?php esc_html_e( 'Match', 'entry-digest-for-gravity-forms-pro' ); ?>
		<select name="dsagfe_digest[filter_logic][<?php echo esc_attr( $fid ); ?>]">
			<option value="all" <?php selected( $f_logic, 'all' ); ?>><?php esc_html_e( 'all', 'entry-digest-for-gravity-forms-pro' ); ?></option>
			<option value="any" <?php selected( $f_logic, 'any' ); ?>><?php esc_html_e( 'any', 'entry-digest-for-gravity-forms-pro' ); ?></option>
		</select>
		<?php esc_html_e( 'of these rules:', 'entry-digest-for-gravity-forms-pro' ); ?>
	</p>
	<table class="dsagfe-filters" data-fid="<?php echo esc_attr( $fid ); ?>" style="margin-bottom:8px;">
		<tbody>
			<?php
			$existing = $f_filters ?: [ [ 'field' => '', 'op' => 'is', 'value' => '' ] ];
			foreach ( $existing as $i => $rule ) {
				$render_rule( $i, $rule, $field_map, $ops, $fid );
			}
			// One spare blank row for adding a rule.
			$render_rule( count( $existing ), [ 'field' => '', 'op' => 'is', 'value' => '' ], $field_map, $ops, $fid );
			?>
		</tbody>
	</table>
	<p class="description"><?php esc_html_e( 'Leave fields blank to ignore a row. Add more rules by saving and reopening.', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<?php
}

// ── Persist Pro fields on save ───────────────────────────────────────────────
add_filter( 'dsagfe_save_digest', 'edfgfp_save_pro_fields', 10, 3 );
function edfgfp_save_pro_fields( array $d, array $raw, array $form_ids ): array {
	// Roles.
	$d['roles'] = ! empty( $raw['roles'] )
		? array_values( array_filter( array_map( 'sanitize_key', (array) $raw['roles'] ) ) )
		: [];

	// Conditional filters (reuses the engine's parser).
	$d['filters'] = dsagfe_parse_posted_filters( $raw, $form_ids, true );

	// Attachment format.
	$fmt = $raw['attach_format'] ?? 'none';
	$d['attach_format'] = in_array( $fmt, [ 'none', 'xlsx', 'csv' ], true ) ? $fmt : 'none';

	return $d;
}

// ── Make the live count preview reflect filtering ────────────────────────────
add_filter( 'dsagfe_preview_count', 'edfgfp_preview_count_with_filters', 10, 5 );
function edfgfp_preview_count_with_filters( array $result, array $raw, array $form_ids, string $frequency, $override ): array {
	if ( ! class_exists( 'GFAPI' ) ) {
		return $result;
	}
	$filters = dsagfe_parse_posted_filters( $raw, $form_ids, true );
	if ( empty( $filters ) ) {
		return $result;
	}

	// Rebuild the same search window the free counter used.
	$days_back = (int) ( $result['days_back'] ?? 0 );
	$end_date  = gmdate( 'Y-m-d H:i:s' );
	$start_date = ( $days_back > 0 )
		? gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) )
		: '2000-01-01 00:00:00';
	$search = [ 'status' => 'active', 'start_date' => $start_date, 'end_date' => $end_date ];

	$total = 0;
	foreach ( $result['per_form'] as $fid => $count ) {
		$conf = $filters[ (string) $fid ] ?? [];
		if ( ! empty( $conf['rules'] ) ) {
			$logic   = ( 'any' === ( $conf['logic'] ?? 'all' ) ) ? 'any' : 'all';
			$entries = GFAPI::get_entries( (int) $fid, $search, null, [ 'offset' => 0, 'page_size' => 2000 ] );
			$entries = is_array( $entries ) ? $entries : [];
			$count   = 0;
			foreach ( $entries as $e ) {
				if ( dsagfe_entry_matches( $e, $conf['rules'], $logic ) ) {
					$count++;
				}
			}
			$result['per_form'][ (string) $fid ] = $count;
		}
		$total += (int) $count;
	}
	$result['total'] = $total;

	return $result;
}
