<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro editor UI: inject the role-recipient, attachment, and conditional-filter
 * controls into the free plugin's digest editor, persist them on save, and make
 * the live entry-count preview reflect filtering.
 *
 * All markup here mirrors the field names the free plugin already understands
 * (edfgf_digest[roles], [filters], [filter_logic], [attach_format]).
 */

// ── Role recipients (after the recipient email row) ──────────────────────────
add_action( 'edfgf_editor_after_recipients', 'edfgfp_editor_roles_row' );
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
						<input type="checkbox" name="edfgf_digest[roles][]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $roles, true ) ); ?>>
						<?php echo esc_html( translate_user_role( $role_name ) ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>
		</td>
	</tr>
	<?php
}

// ── Attachment selector (Email section, after the subject) ───────────────────
add_action( 'edfgf_editor_email_options', 'edfgfp_editor_attachment_row' );
function edfgfp_editor_attachment_row( array $d ): void {
	$fmt = $d['attach_format'] ?? 'none';
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Attachment', 'entry-digest-for-gravity-forms-pro' ); ?></th>
		<td>
			<select name="edfgf_digest[attach_format]">
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
add_action( 'edfgf_editor_form_block', 'edfgfp_editor_filter_ui', 10, 3 );
function edfgfp_editor_filter_ui( string $fid, array $d, array $field_map ): void {
	$ops       = edfgfp_filter_operators();
	$f_filters = (array) ( $d['filters'][ $fid ]['rules'] ?? [] );
	$f_logic   = $d['filters'][ $fid ]['logic'] ?? 'all';

	$render_rule = static function ( $i, $rule, $field_map, $ops, $fid ) {
		$rf = $rule['field'] ?? '';
		$ro = $rule['op'] ?? 'is';
		$rv = $rule['value'] ?? '';
		?>
		<tr class="edfgfp-filter-row">
			<td>
				<select name="edfgf_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][field]">
					<option value="">- <?php esc_html_e( 'field', 'entry-digest-for-gravity-forms-pro' ); ?> -</option>
					<?php foreach ( $field_map as $k => $lab ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( (string) $rf, (string) $k ); ?>><?php echo esc_html( $lab ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select name="edfgf_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][op]">
					<?php foreach ( $ops as $ok => $olabel ) : ?>
						<option value="<?php echo esc_attr( $ok ); ?>" <?php selected( $ro, $ok ); ?>><?php echo esc_html( $olabel ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td><input type="text" name="edfgf_digest[filters][<?php echo esc_attr( $fid ); ?>][<?php echo (int) $i; ?>][value]" value="<?php echo esc_attr( $rv ); ?>" placeholder="<?php esc_attr_e( 'value', 'entry-digest-for-gravity-forms-pro' ); ?>"></td>
			<td style="width:32px;vertical-align:middle;">
				<button type="button" class="edfgfp-remove-rule button-link" aria-label="<?php esc_attr_e( 'Remove rule', 'entry-digest-for-gravity-forms-pro' ); ?>" style="color:#d63638;padding:0 4px;line-height:1;">&times;</button>
			</td>
		</tr>
		<?php
	};
	?>
	<p style="font-weight:600;margin:14px 0 6px;"><?php esc_html_e( 'Conditional filtering', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<p class="description" style="margin-bottom:6px;">
		<?php esc_html_e( 'Match', 'entry-digest-for-gravity-forms-pro' ); ?>
		<select name="edfgf_digest[filter_logic][<?php echo esc_attr( $fid ); ?>]">
			<option value="all" <?php selected( $f_logic, 'all' ); ?>><?php esc_html_e( 'all', 'entry-digest-for-gravity-forms-pro' ); ?></option>
			<option value="any" <?php selected( $f_logic, 'any' ); ?>><?php esc_html_e( 'any', 'entry-digest-for-gravity-forms-pro' ); ?></option>
		</select>
		<?php esc_html_e( 'of these rules:', 'entry-digest-for-gravity-forms-pro' ); ?>
	</p>
	<table class="edfgfp-filters" data-fid="<?php echo esc_attr( $fid ); ?>" style="margin-bottom:8px;">
		<tbody>
			<?php
			$existing = $f_filters ?: [ [ 'field' => '', 'op' => 'is', 'value' => '' ] ];
			foreach ( $existing as $i => $rule ) {
				$render_rule( $i, $rule, $field_map, $ops, $fid );
			}
			?>
		</tbody>
	</table>

	<?php
	/*
	 * Template row used by JS to clone new blank rules without any HTML-in-JS.
	 * Index placeholder __IDX__ is replaced by the script before insertion.
	 * Hidden from the DOM via the <template> element (never submitted).
	 */
	?>
	<template class="edfgfp-row-template" data-fid="<?php echo esc_attr( $fid ); ?>">
		<?php $render_rule( '__IDX__', [ 'field' => '', 'op' => 'is', 'value' => '' ], $field_map, $ops, $fid ); ?>
	</template>

	<p style="margin:0 0 8px;">
		<button type="button" class="edfgfp-add-rule button button-small" data-fid="<?php echo esc_attr( $fid ); ?>">
			+ <?php esc_html_e( 'Add rule', 'entry-digest-for-gravity-forms-pro' ); ?>
		</button>
	</p>
	<p class="description"><?php esc_html_e( 'Leave the field blank on a row to ignore it.', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<?php
}

// ── Persist Pro fields on save ───────────────────────────────────────────────
add_filter( 'edfgf_save_digest', 'edfgfp_save_pro_fields', 10, 3 );
function edfgfp_save_pro_fields( array $d, array $raw, array $form_ids ): array {
	// Roles.
	$d['roles'] = ! empty( $raw['roles'] )
		? array_values( array_filter( array_map( 'sanitize_key', (array) $raw['roles'] ) ) )
		: [];

	// Conditional filters (reuses the engine's parser).
	$d['filters'] = edfgfp_parse_posted_filters( $raw, $form_ids, true );

	// Attachment format.
	$fmt = $raw['attach_format'] ?? 'none';
	$d['attach_format'] = in_array( $fmt, [ 'none', 'xlsx', 'csv' ], true ) ? $fmt : 'none';

	// Note: "Max entries in email" (email_table_limit) is a free, core field now -
	// the free plugin renders the editor input and parses it in admin/save.php.

	return $d;
}

// ── Make the live count preview reflect filtering ────────────────────────────
add_filter( 'edfgf_preview_count', 'edfgfp_preview_count_with_filters', 10, 5 );
function edfgfp_preview_count_with_filters( array $result, array $raw, array $form_ids, string $frequency, $override ): array {
	if ( ! class_exists( 'GFAPI' ) ) {
		return $result;
	}
	$filters = edfgfp_parse_posted_filters( $raw, $form_ids, true );
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
				if ( edfgfp_entry_matches( $e, $conf['rules'], $logic ) ) {
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

// ── Dynamic filter rows script ────────────────────────────────────────────────
// Printed once per page load, only on the editor screen, via the same pattern
// as notifications.php. Handles:
//   • "Add rule"   – clones the <template>, replaces __IDX__ with the next index,
//                    appends to the <tbody>, then reindexes all rows.
//   • "Remove" (×) – removes the row, then reindexes all remaining rows.
//   • Reindex      – walks every row in a table and rewrites the [N] part of
//                    every name attribute so they stay 0-based and sequential.
//                    This keeps the PHP parser happy regardless of how many rows
//                    were added or removed.

add_action( 'admin_print_footer_scripts', 'edfgfp_filter_rows_script' );
function edfgfp_filter_rows_script(): void {
	// Only emit on the digest editor screen (action=new or action=edit).
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	if ( 'new' !== $action && 'edit' !== $action ) {
		return;
	}
	?>
	<script>
	( function () {
		'use strict';

		/**
		 * Reindex every row in a filter table so name attributes use 0-based
		 * sequential indices: edfgf_digest[filters][FID][0][field], [1][field]…
		 * We replace only the numeric part that sits between the fid segment and
		 * the field/op/value segment, e.g.:
		 *   filters][42][3][field]  →  filters][42][0][field]
		 * The regex targets the integer (or __IDX__ placeholder) between the two
		 * closing brackets that follow the form ID segment.
		 */
		function reindex( tbody, fid ) {
			var rows = tbody.querySelectorAll( 'tr.edfgfp-filter-row' );
			rows.forEach( function ( row, i ) {
				row.querySelectorAll( '[name]' ).forEach( function ( el ) {
					// Replace ][FID][ANY_INDEX][ with ][FID][i][
					el.name = el.name.replace(
						new RegExp( '(\\[filters\\]\\[' + fid + '\\]\\[)[^\\]]+' ),
						'$1' + i
					);
				} );
			} );
		}

		// ── "Add rule" buttons ──────────────────────────────────────────────
		document.querySelectorAll( '.edfgfp-add-rule' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var fid    = btn.getAttribute( 'data-fid' );
				var table  = document.querySelector( '.edfgfp-filters[data-fid="' + fid + '"]' );
				var tmpl   = document.querySelector( '.edfgfp-row-template[data-fid="' + fid + '"]' );
				if ( ! table || ! tmpl ) { return; }

				var tbody   = table.querySelector( 'tbody' );
				var nextIdx = tbody.querySelectorAll( 'tr.edfgfp-filter-row' ).length;

				// Clone the template content and stamp in the real index.
				var clone = document.createElement( 'tbody' );
				clone.innerHTML = tmpl.innerHTML.replace( /__IDX__/g, nextIdx );
				var newRow = clone.querySelector( 'tr' );
				if ( ! newRow ) { return; }

				tbody.appendChild( newRow );
				reindex( tbody, fid );

				// Wire the remove button on the freshly added row.
				wireRemove( newRow, fid, tbody );

				// Focus the field selector in the new row for keyboard users.
				var firstSelect = newRow.querySelector( 'select' );
				if ( firstSelect ) { firstSelect.focus(); }
			} );
		} );

		// ── "Remove" (×) buttons ───────────────────────────────────────────
		function wireRemove( row, fid, tbody ) {
			var btn = row.querySelector( '.edfgfp-remove-rule' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				// Always keep at least one row so the UI doesn't collapse.
				var rows = tbody.querySelectorAll( 'tr.edfgfp-filter-row' );
				if ( rows.length <= 1 ) {
					// Clear the row instead of removing it.
					row.querySelectorAll( 'select' ).forEach( function ( sel ) {
						sel.selectedIndex = 0;
					} );
					row.querySelectorAll( 'input[type="text"]' ).forEach( function ( inp ) {
						inp.value = '';
					} );
					return;
				}
				row.parentNode.removeChild( row );
				reindex( tbody, fid );
			} );
		}

		// Wire remove buttons that were rendered server-side on page load.
		document.querySelectorAll( '.edfgfp-filters' ).forEach( function ( table ) {
			var fid   = table.getAttribute( 'data-fid' );
			var tbody = table.querySelector( 'tbody' );
			if ( ! tbody ) { return; }
			tbody.querySelectorAll( 'tr.edfgfp-filter-row' ).forEach( function ( row ) {
				wireRemove( row, fid, tbody );
			} );
		} );

	}() );
	</script>
	<?php
}