<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: custom email branding - logo, accent color, and a white-label footer.
 *
 * Stored per digest under brand_logo / brand_accent / brand_footer, edited via
 * the free plugin's editor hook, and applied through its email-render filters.
 */

// ── Editor row (Email section, after the subject) ────────────────────────────
add_action( 'edfgf_editor_email_options', 'edfgfp_editor_branding_row' );
function edfgfp_editor_branding_row( array $d ): void {
	$logo     = (string) ( $d['brand_logo'] ?? '' );
	$logo_pos = (string) ( $d['brand_logo_position'] ?? 'above' );
	if ( ! in_array( $logo_pos, [ 'above', 'left', 'right' ], true ) ) {
		$logo_pos = 'above';
	}
	$accent = (string) ( $d['brand_accent'] ?? '' );
	$footer = (string) ( $d['brand_footer'] ?? '' );
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Email branding', 'entry-digest-for-gravity-forms-pro' ); ?></th>
		<td>
			<p style="margin:0 0 10px;">
				<label>
					<?php esc_html_e( 'Logo image URL', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<input type="url" name="edfgf_digest[brand_logo]" value="<?php echo esc_attr( $logo ); ?>" class="regular-text" placeholder="https://example.com/logo.png">
				</label>
				<br><span class="description"><?php esc_html_e( 'Shown in the email header. Leave blank for none.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
			<p style="margin:0 0 10px;">
				<label>
					<?php esc_html_e( 'Logo position', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<select name="edfgf_digest[brand_logo_position]">
						<option value="above" <?php selected( $logo_pos, 'above' ); ?>><?php esc_html_e( 'Above title', 'entry-digest-for-gravity-forms-pro' ); ?></option>
						<option value="left"  <?php selected( $logo_pos, 'left' );  ?>><?php esc_html_e( 'Left of title', 'entry-digest-for-gravity-forms-pro' ); ?></option>
						<option value="right" <?php selected( $logo_pos, 'right' ); ?>><?php esc_html_e( 'Right of title', 'entry-digest-for-gravity-forms-pro' ); ?></option>
					</select>
				</label>
				<br><span class="description"><?php esc_html_e( 'Where the logo sits relative to the digest title. Only applies when a logo URL is set.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
			<p style="margin:0 0 10px;">
				<label>
					<?php esc_html_e( 'Accent color', 'entry-digest-for-gravity-forms-pro' ); ?>
					<input type="color" name="edfgf_digest[brand_accent]" value="<?php echo esc_attr( $accent ?: '#2563eb' ); ?>">
				</label>
				<span class="description"><?php esc_html_e( 'Header background and link color.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
			<p style="margin:0;">
				<label>
					<?php esc_html_e( 'Custom footer text', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<input type="text" name="edfgf_digest[brand_footer]" value="<?php echo esc_attr( $footer ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Sent by Acme Co.', 'entry-digest-for-gravity-forms-pro' ); ?>">
				</label>
				<br><span class="description"><?php esc_html_e( 'Replaces the default credit line. Leave blank to keep the default.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
		</td>
	</tr>
	<?php
}

// ── Persist branding fields on save ──────────────────────────────────────────
add_filter( 'edfgf_save_digest', 'edfgfp_save_branding', 10, 3 );
function edfgfp_save_branding( array $d, array $raw, array $form_ids ): array {
	$d['brand_logo'] = esc_url_raw( (string) ( $raw['brand_logo'] ?? '' ) );

	$pos = (string) ( $raw['brand_logo_position'] ?? 'above' );
	$d['brand_logo_position'] = in_array( $pos, [ 'above', 'left', 'right' ], true ) ? $pos : 'above';

	$accent = (string) ( $raw['brand_accent'] ?? '' );
	$d['brand_accent'] = preg_match( '/^#[0-9a-fA-F]{6}$/', $accent ) ? $accent : '';

	$d['brand_footer'] = sanitize_text_field( (string) ( $raw['brand_footer'] ?? '' ) );

	return $d;
}

// ── Apply branding to the rendered email ─────────────────────────────────────
add_filter( 'edfgf_email_accent', 'edfgfp_brand_accent', 10, 2 );
function edfgfp_brand_accent( $accent, $d ) {
	$c = (string) ( $d['brand_accent'] ?? '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? $c : $accent;
}

add_filter( 'edfgf_email_logo_html', 'edfgfp_brand_logo', 10, 2 );
/**
 * Return logo <img> markup for the 'above' position (the free plugin's default
 * behaviour: logo block sits above the title, wrapped in its own div).
 *
 * For 'left' and 'right' the logo HTML alone isn't enough — we need to
 * restructure the header so logo and title sit side-by-side. That is handled
 * by edfgfp_rewrite_header_for_position(), which post-processes the finished
 * HTML string via the edfgf_digest_html filter.
 */
function edfgfp_brand_logo( string $html, array $d ): string {
	$logo = (string) ( $d['brand_logo'] ?? '' );
	if ( '' === $logo ) {
		return $html;
	}
	// Return a plain img for all positions. For left/right the post-processor
	// will move it into the correct flex layout after the full HTML is built.
	return '<img src="' . esc_url( $logo ) . '" alt="" style="max-height:48px;max-width:200px;height:auto;display:block;">';
}

add_filter( 'edfgf_digest_html', 'edfgfp_rewrite_header_for_position', 10, 2 );
/**
 * Post-process the finished email HTML to restructure the header when
 * logo position is 'left' or 'right'.
 *
 * The free plugin always renders:
 *   <div style="margin-bottom:10px;"><img ...></div>   ← logo wrapper
 *   <div style="color:#fff;font-size:18px;...">Title</div>
 *   <div style="color:rgba(...);font-size:13px;...">subtitle</div>
 *
 * For left/right we collapse those three siblings into a single table-based
 * row (tables for email-client compatibility) with the logo on one side and
 * the title+subtitle stacked on the other, then justify them to opposite ends.
 *
 * We use a regex that matches the exact markup the free plugin produces, so
 * this is precise and doesn't touch anything else in the email.
 */
function edfgfp_rewrite_header_for_position( string $html, array $d ): string {
	$logo = (string) ( $d['brand_logo'] ?? '' );
	if ( '' === $logo ) {
		return $html;
	}

	$pos = (string) ( $d['brand_logo_position'] ?? 'above' );
	if ( ! in_array( $pos, [ 'left', 'right' ], true ) ) {
		return $html; // 'above' needs no restructuring.
	}

	// Match the three-part header block the free plugin produces.
	// The title and subtitle divs contain indented/whitespace-padded text
	// as rendered by PHP, so we use .*? with the s (dotall) flag.
	// We match on the opening style= values exactly as they appear in render-email.php.
	$pattern = '~'
		. '(<div style="margin-bottom:10px;">.*?</div>)'                               // logo wrapper
		. '\s*'
		. '(<div style="color:#ffffff;font-size:18px;font-weight:700;">.*?</div>)'    // title
		. '\s*'
		. '(<div style="color:rgba\(255,255,255,0\.85\);font-size:13px;margin-top:2px;">.*?</div>)'  // subtitle
		. '~s';

	$replaced = preg_replace_callback( $pattern, function ( $m ) use ( $pos ) {
		$logo_block  = $m[1]; // <div style="margin-bottom:10px;"><img ...></div>
		$title_block = $m[2];
		$sub_block   = $m[3];

		// Strip the margin-bottom wrapper — we control spacing in the table cell.
		$img_tag = preg_replace( '~^<div[^>]*>(.*)</div>$~s', '$1', trim( $logo_block ) );

		// For left: logo on left (text-align:left), title on right (text-align:right)
		// For right: logo on right, title on left — swap the cells.
		$logo_cell_html  = '<td style="vertical-align:middle;padding:0;">' . $img_tag . '</td>';
		$title_cell_html = '<td style="vertical-align:middle;padding:0;text-align:right;">'
			. $title_block
			. $sub_block
			. '</td>';

		if ( 'right' === $pos ) {
			// Logo on right: title cell first (left-aligned), logo cell second.
			$title_cell_html = '<td style="vertical-align:middle;padding:0;">'
				. $title_block
				. $sub_block
				. '</td>';
			$logo_cell_html  = '<td style="vertical-align:middle;padding:0;text-align:right;">' . $img_tag . '</td>';
		}

		return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
			. '<tr>'
			. $logo_cell_html
			. $title_cell_html
			. '</tr>'
			. '</table>';
	}, $html, 1 );

	return $replaced ?? $html;
}

add_filter( 'edfgf_email_footer_credit', 'edfgfp_brand_footer', 10, 2 );
function edfgfp_brand_footer( $credit, $d ) {
	$custom = (string) ( $d['brand_footer'] ?? '' );
	return '' !== $custom ? $custom : $credit;
}
