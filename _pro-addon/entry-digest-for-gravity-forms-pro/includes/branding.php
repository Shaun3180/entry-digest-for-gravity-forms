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
 * Return logo markup for the email header.
 *
 * For 'above' (default): returns a plain <img> which the free plugin wraps in
 * its own <div> above the title — no change to the title rendering needed.
 *
 * For 'left' / 'right': the free plugin always renders the logo block *before*
 * the title block with no position awareness. To achieve a true side-by-side
 * layout we return a self-contained flex row that contains both the logo and a
 * copy of the title, then immediately follow it with a zero-height element that
 * collapses the real title <div> the free plugin appends. This keeps the free
 * plugin untouched while giving full layout control to Pro.
 */
function edfgfp_brand_logo( string $html, array $d ): string {
	$logo = (string) ( $d['brand_logo'] ?? '' );
	if ( '' === $logo ) {
		return $html;
	}

	$pos = (string) ( $d['brand_logo_position'] ?? 'above' );
	if ( ! in_array( $pos, [ 'above', 'left', 'right' ], true ) ) {
		$pos = 'above';
	}

	$img = '<img src="' . esc_url( $logo ) . '" alt="" style="max-height:48px;max-width:200px;height:auto;display:block;flex-shrink:0;">';

	if ( 'above' === $pos ) {
		// Simple: logo above title. Free plugin wraps this in its own div with
		// margin-bottom:10px, which is exactly what we want.
		return str_replace( 'flex-shrink:0;', 'display:block;', $img );
	}

	// Left or right: build a flex row containing the logo and the digest title.
	// We pull the title text from the digest config (same logic the free plugin
	// uses) so the copy is always in sync.
	$multi_form = isset( $d['form_ids'] ) && count( (array) $d['form_ids'] ) > 1;
	if ( $multi_form ) {
		$title_text = ! empty( $d['label'] ) ? $d['label'] : __( 'Entry digest', 'entry-digest-for-gravity-forms' );
	} else {
		$fid  = (int) ( ( (array) ( $d['form_ids'] ?? [] ) )[0] ?? 0 );
		$form = $fid && class_exists( 'GFAPI' ) ? GFAPI::get_form( $fid ) : null;
		$title_text = ( $form && ! empty( $form['title'] ) ) ? $form['title'] : __( 'Entry digest', 'entry-digest-for-gravity-forms' );
	}

	$row_direction  = ( 'right' === $pos ) ? 'row-reverse' : 'row';
	$logo_margin    = ( 'right' === $pos ) ? 'margin-left:14px;' : 'margin-right:14px;';

	// The flex row replaces both the logo placeholder and the title that follows.
	// After outputting this block we emit a zero-height/zero-font style that
	// collapses the real title <div> the free plugin appends unconditionally.
	$flex_row  = '<div style="display:flex;flex-direction:' . $row_direction . ';align-items:center;gap:0;">';
	$flex_row .= '<div style="' . $logo_margin . 'flex-shrink:0;">' . $img . '</div>';
	$flex_row .= '<div style="color:#ffffff;font-size:18px;font-weight:700;line-height:1.3;">' . esc_html( $title_text ) . '</div>';
	$flex_row .= '</div>';

	// Immediately after the logo block the free plugin emits:
	//   <div style="color:#ffffff;font-size:18px;font-weight:700;">$title</div>
	// We cancel it with an inline style injected via the wrapper the free plugin
	// adds: the logo block is wrapped in <div style="margin-bottom:10px;">.
	// We close that div early and open a zero-height div to swallow the title.
	$flex_row .= '</div><div style="font-size:0;line-height:0;max-height:0;overflow:hidden;color:transparent;">';
	// The free plugin will close its own wrapper divs correctly; the extra open
	// <div> above is intentional — it will be closed by the free plugin's next
	// </div> (the one that closes the title block), keeping the DOM balanced.

	return $flex_row;
}

add_filter( 'edfgf_email_footer_credit', 'edfgfp_brand_footer', 10, 2 );
function edfgfp_brand_footer( $credit, $d ) {
	$custom = (string) ( $d['brand_footer'] ?? '' );
	return '' !== $custom ? $custom : $credit;
}
