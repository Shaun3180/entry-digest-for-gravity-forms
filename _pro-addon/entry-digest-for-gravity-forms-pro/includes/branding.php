<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: custom email branding - logo, accent color, and a white-label footer.
 *
 * Stored per digest under brand_logo / brand_accent / brand_footer, edited via
 * the free plugin's editor hook, and applied through its email-render filters.
 */

/**
 * Whitelisted, email-safe font-family stacks. Keys are the stored values; the
 * empty key is the default (inherits the plugin's system stack). Shared by the
 * editor dropdown and the save validator so only known-good values are stored.
 */
function edfgfp_brand_fonts(): array {
	return [
		''                                          => __( 'Default (system sans-serif)', 'entry-digest-for-gravity-forms-pro' ),
		'Helvetica,Arial,sans-serif'                => __( 'Helvetica / Arial', 'entry-digest-for-gravity-forms-pro' ),
		'Verdana,Geneva,sans-serif'                 => __( 'Verdana', 'entry-digest-for-gravity-forms-pro' ),
		'Tahoma,Geneva,sans-serif'                  => __( 'Tahoma', 'entry-digest-for-gravity-forms-pro' ),
		"'Trebuchet MS',Helvetica,sans-serif"       => __( 'Trebuchet MS', 'entry-digest-for-gravity-forms-pro' ),
		'Georgia,serif'                             => __( 'Georgia (serif)', 'entry-digest-for-gravity-forms-pro' ),
		"'Times New Roman',Times,serif"             => __( 'Times New Roman (serif)', 'entry-digest-for-gravity-forms-pro' ),
		"'Courier New',Courier,monospace"           => __( 'Courier (monospace)', 'entry-digest-for-gravity-forms-pro' ),
	];
}

// ── Editor row (Email section, after the subject) ────────────────────────────
add_action( 'edfgf_editor_email_options', 'edfgfp_editor_branding_row' );
function edfgfp_editor_branding_row( array $d ): void {
	$logo     = (string) ( $d['brand_logo'] ?? '' );
	$logo_pos = (string) ( $d['brand_logo_position'] ?? 'above' );
	if ( ! in_array( $logo_pos, [ 'above', 'left', 'right' ], true ) ) {
		$logo_pos = 'above';
	}
	$accent      = (string) ( $d['brand_accent'] ?? '' );
	$footer      = (string) ( $d['brand_footer'] ?? '' );
	$font        = (string) ( $d['brand_font'] ?? '' );
	$fonts       = edfgfp_brand_fonts();
	if ( ! array_key_exists( $font, $fonts ) ) {
		$font = '';
	}
	$header_text = (string) ( $d['brand_header_text'] ?? '' );
	$footer_bg   = (string) ( $d['brand_footer_bg'] ?? '' );
	$footer_text = (string) ( $d['brand_footer_text'] ?? '' );
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
			<p style="margin:0 0 14px;">
				<label>
					<?php esc_html_e( 'Custom footer text', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<input type="text" name="edfgf_digest[brand_footer]" value="<?php echo esc_attr( $footer ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Sent by Acme Co.', 'entry-digest-for-gravity-forms-pro' ); ?>">
				</label>
				<br><span class="description"><?php esc_html_e( 'Replaces the default credit line. Leave blank to keep the default.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>

			<details style="border-top:1px solid #dcdcde;padding-top:10px;">
				<summary style="cursor:pointer;font-weight:600;"><?php esc_html_e( 'Advanced colors &amp; typography', 'entry-digest-for-gravity-forms-pro' ); ?></summary>
				<div style="margin-top:12px;">
					<p style="margin:0 0 10px;">
						<label>
							<?php esc_html_e( 'Email font', 'entry-digest-for-gravity-forms-pro' ); ?><br>
							<select name="edfgf_digest[brand_font]">
								<?php foreach ( $fonts as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $font, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<br><span class="description"><?php esc_html_e( 'Font family for the whole email. Limited to email-safe fonts so it renders reliably across clients.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
					</p>
					<p style="margin:0 0 10px;">
						<label>
							<?php esc_html_e( 'Header text color', 'entry-digest-for-gravity-forms-pro' ); ?>
							<input type="color" name="edfgf_digest[brand_header_text]" value="<?php echo esc_attr( $header_text ?: '#ffffff' ); ?>">
						</label>
						<span class="description"><?php esc_html_e( 'Title and subtitle color in the header. Pick something readable on your accent color.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
					</p>
					<p style="margin:0 0 10px;">
						<label>
							<input type="checkbox" name="edfgf_digest[brand_footer_bg_on]" value="1" <?php checked( '' !== $footer_bg ); ?>>
							<?php esc_html_e( 'Custom footer background', 'entry-digest-for-gravity-forms-pro' ); ?>
						</label>
						<input type="color" name="edfgf_digest[brand_footer_bg]" value="<?php echo esc_attr( $footer_bg ?: '#f9fafb' ); ?>">
						<br><span class="description"><?php esc_html_e( 'When unchecked, the footer keeps the email background.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
					</p>
					<p style="margin:0;">
						<label>
							<?php esc_html_e( 'Footer text color', 'entry-digest-for-gravity-forms-pro' ); ?>
							<input type="color" name="edfgf_digest[brand_footer_text]" value="<?php echo esc_attr( $footer_text ?: '#6b7280' ); ?>">
						</label>
						<span class="description"><?php esc_html_e( 'Color of the footer credit line.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
					</p>
				</div>
			</details>
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

	// Font: only store a value that is in the whitelist; otherwise default (empty).
	$font = (string) ( $raw['brand_font'] ?? '' );
	$d['brand_font'] = array_key_exists( $font, edfgfp_brand_fonts() ) ? $font : '';

	// Header text color: 6-digit hex or empty (= white default).
	$header_text = (string) ( $raw['brand_header_text'] ?? '' );
	$d['brand_header_text'] = preg_match( '/^#[0-9a-fA-F]{6}$/', $header_text ) ? $header_text : '';

	// Footer background: only stored when the enable checkbox is ticked and valid.
	$footer_bg = (string) ( $raw['brand_footer_bg'] ?? '' );
	$d['brand_footer_bg'] = ( ! empty( $raw['brand_footer_bg_on'] ) && preg_match( '/^#[0-9a-fA-F]{6}$/', $footer_bg ) )
		? $footer_bg
		: '';

	// Footer text color: 6-digit hex or empty (= muted default).
	$footer_text = (string) ( $raw['brand_footer_text'] ?? '' );
	$d['brand_footer_text'] = preg_match( '/^#[0-9a-fA-F]{6}$/', $footer_text ) ? $footer_text : '';

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
	// The title/subtitle colors are filterable (header text color), so match any
	// color value rather than the default white/rgba literals.
	$pattern = '~'
		. '(<div style="margin-bottom:10px;">.*?</div>)'                               // logo wrapper
		. '\s*'
		. '(<div style="color:[^;"]*;font-size:18px;font-weight:700;">.*?</div>)'      // title
		. '\s*'
		. '(<div style="color:[^;"]*;font-size:13px;margin-top:2px;">.*?</div>)'       // subtitle
		. '~s';

	$replaced = preg_replace_callback( $pattern, function ( $m ) use ( $pos ) {
		$logo_block  = $m[1]; // <div style="margin-bottom:10px;"><img ...></div>
		$title_block = $m[2];
		$sub_block   = $m[3];

		// Strip the margin-bottom wrapper — we control spacing in the table cell.
		$img_tag = preg_replace( '~^<div[^>]*>(.*)</div>$~s', '$1', trim( $logo_block ) );

		// Build a logo cell and a title cell, then order them by position.
		// For 'left':  [ logo (left) ][ title (right) ]
		// For 'right': [ title (left) ][ logo (right) ]
		// The logo cell shrinks to its content (width:1%) and the title cell
		// fills the rest of the row (width:100%), so the logo always hugs the
		// outer edge it sits on rather than floating toward the middle.
		$logo_cell  = '<td style="vertical-align:middle;padding:0;width:1%;white-space:nowrap;'
			. ( 'right' === $pos ? 'text-align:right;' : '' )
			. '">' . $img_tag . '</td>';
		$title_cell = '<td style="vertical-align:middle;padding:0;width:100%;'
			. ( 'left' === $pos ? 'text-align:right;' : '' )
			. '">' . $title_block . $sub_block . '</td>';

		if ( 'right' === $pos ) {
			$first_cell  = $title_cell;
			$second_cell = $logo_cell;
		} else { // 'left'
			$first_cell  = $logo_cell;
			$second_cell = $title_cell;
		}

		return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">'
			. '<tr>'
			. $first_cell
			. $second_cell
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

add_filter( 'edfgf_email_font_family', 'edfgfp_brand_font', 10, 2 );
function edfgfp_brand_font( $font, $d ) {
	$custom = (string) ( $d['brand_font'] ?? '' );
	return array_key_exists( $custom, edfgfp_brand_fonts() ) && '' !== $custom ? $custom : $font;
}

add_filter( 'edfgf_email_header_text_color', 'edfgfp_brand_header_text', 10, 2 );
function edfgfp_brand_header_text( $color, $d ) {
	$custom = (string) ( $d['brand_header_text'] ?? '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $custom ) ? $custom : $color;
}

add_filter( 'edfgf_email_footer_bg', 'edfgfp_brand_footer_bg', 10, 2 );
function edfgfp_brand_footer_bg( $bg, $d ) {
	$custom = (string) ( $d['brand_footer_bg'] ?? '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $custom ) ? $custom : $bg;
}

add_filter( 'edfgf_email_footer_text', 'edfgfp_brand_footer_text', 10, 2 );
function edfgfp_brand_footer_text( $color, $d ) {
	$custom = (string) ( $d['brand_footer_text'] ?? '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $custom ) ? $custom : $color;
}
