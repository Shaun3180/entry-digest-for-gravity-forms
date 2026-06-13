<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: custom email branding — logo, accent color, and a white-label footer.
 *
 * Stored per digest under brand_logo / brand_accent / brand_footer, edited via
 * the free plugin's editor hook, and applied through its email-render filters.
 */

// ── Editor row (after the schedule/attachment rows) ──────────────────────────
add_action( 'dsagfe_editor_after_schedule', 'edfgfp_editor_branding_row' );
function edfgfp_editor_branding_row( array $d ): void {
	$logo   = (string) ( $d['brand_logo'] ?? '' );
	$accent = (string) ( $d['brand_accent'] ?? '' );
	$footer = (string) ( $d['brand_footer'] ?? '' );
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Email branding', 'entry-digest-for-gravity-forms-pro' ); ?></th>
		<td>
			<p style="margin:0 0 10px;">
				<label>
					<?php esc_html_e( 'Logo image URL', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<input type="url" name="dsagfe_digest[brand_logo]" value="<?php echo esc_attr( $logo ); ?>" class="regular-text" placeholder="https://example.com/logo.png">
				</label>
				<br><span class="description"><?php esc_html_e( 'Shown at the top of the email. Leave blank for none.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
			<p style="margin:0 0 10px;">
				<label>
					<?php esc_html_e( 'Accent color', 'entry-digest-for-gravity-forms-pro' ); ?>
					<input type="color" name="dsagfe_digest[brand_accent]" value="<?php echo esc_attr( $accent ?: '#2563eb' ); ?>">
				</label>
				<span class="description"><?php esc_html_e( 'Header background and link color.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
			<p style="margin:0;">
				<label>
					<?php esc_html_e( 'Custom footer text', 'entry-digest-for-gravity-forms-pro' ); ?><br>
					<input type="text" name="dsagfe_digest[brand_footer]" value="<?php echo esc_attr( $footer ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'e.g. Sent by Acme Co.', 'entry-digest-for-gravity-forms-pro' ); ?>">
				</label>
				<br><span class="description"><?php esc_html_e( 'Replaces the default credit line. Leave blank to keep the default.', 'entry-digest-for-gravity-forms-pro' ); ?></span>
			</p>
		</td>
	</tr>
	<?php
}

// ── Persist branding fields on save ──────────────────────────────────────────
add_filter( 'dsagfe_save_digest', 'edfgfp_save_branding', 10, 3 );
function edfgfp_save_branding( array $d, array $raw, array $form_ids ): array {
	$d['brand_logo'] = esc_url_raw( (string) ( $raw['brand_logo'] ?? '' ) );

	$accent = (string) ( $raw['brand_accent'] ?? '' );
	$d['brand_accent'] = preg_match( '/^#[0-9a-fA-F]{6}$/', $accent ) ? $accent : '';

	$d['brand_footer'] = sanitize_text_field( (string) ( $raw['brand_footer'] ?? '' ) );

	return $d;
}

// ── Apply branding to the rendered email ─────────────────────────────────────
add_filter( 'dsagfe_email_accent', 'edfgfp_brand_accent', 10, 2 );
function edfgfp_brand_accent( $accent, $d ) {
	$c = (string) ( $d['brand_accent'] ?? '' );
	return preg_match( '/^#[0-9a-fA-F]{6}$/', $c ) ? $c : $accent;
}

add_filter( 'dsagfe_email_logo_html', 'edfgfp_brand_logo', 10, 2 );
function edfgfp_brand_logo( $html, $d ) {
	$logo = (string) ( $d['brand_logo'] ?? '' );
	if ( '' === $logo ) {
		return $html;
	}
	return '<img src="' . esc_url( $logo ) . '" alt="" style="max-height:48px;max-width:240px;height:auto;display:block;">';
}

add_filter( 'dsagfe_email_footer_credit', 'edfgfp_brand_footer', 10, 2 );
function edfgfp_brand_footer( $credit, $d ) {
	$custom = (string) ( $d['brand_footer'] ?? '' );
	return '' !== $custom ? $custom : $credit;
}
