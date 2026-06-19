<?php
defined( 'ABSPATH' ) || exit;

/**
 * Optional "Entry Digest Pro" upsell.
 *
 * Renders a single, collapsible, dismissible panel at the bottom of the digest
 * list screen describing what the optional Pro add-on adds. It is purely
 * informational: it links out to the add-on's product page and does not gate,
 * disable, or unlock anything in this plugin. It is shown only on free installs
 * (hidden automatically when the Pro add-on is active) and can be dismissed for a
 * year per user.
 */

add_action( 'dsagfe_after_log_table', 'dsagfe_render_pro_panel' );
add_action( 'wp_ajax_dsagfe_dismiss_pro_panel', 'dsagfe_ajax_dismiss_pro_panel' );

/**
 * The Pro feature list shown in the panel.
 */
function dsagfe_pro_features(): array {
	return [
		[
			'title' => __( 'Unlimited Forms per Digest', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Run as many digests as you need, all while rolling several forms into one email. Aggregate your various forms into a single morning summary instead of juggling separate notifications.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Per-Recipient & Role Routing', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Send the right digest to the right people. Route the admissions form to your admissions lead and the support form to your help desk - by individual recipient or by WordPress role.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Conditional Filtering', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Include only the entries that matter. Set rules like “Status is Complete” or “Budget greater than 1000” - match all or any - and your digest filters itself automatically on every run.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'CSV & Excel Attachments', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Attach the full period’s entries as a CSV or native Excel (.xlsx) file. Recipients get the scannable summary in the email and the complete dataset ready to sort, filter, or import.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Custom Email Branding', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Upload your logo, set an accent color, and replace the default footer credit with your own text. Every digest your site sends reflects your brand instead of the plugin’s.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Priority Support', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Get fast, direct help from the developer. Pro licenses come with priority email support so setup questions and edge cases don’t sit in a queue.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Extended Send History', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Configurable send-log retention beyond the default of five recent sends.', 'entry-digest-for-gravity-forms' ),
		],
	];
}

/**
 * Whether the current user has dismissed the panel within the last year.
 */
function dsagfe_pro_panel_dismissed(): bool {
	$until = (int) get_user_meta( get_current_user_id(), 'dsagfe_pro_panel_dismissed', true );
	return $until > time();
}

/**
 * Render the collapsible Pro panel (free installs only).
 */
function dsagfe_render_pro_panel(): void {
	// Hidden when the Pro add-on is active, for users who can't act on it, or once dismissed.
	if ( defined( 'EDFGFP_VERSION' ) || ! current_user_can( 'manage_options' ) || dsagfe_pro_panel_dismissed() ) {
		return;
	}

	$pro_url = dsagfe_pro_url();
	$nonce   = wp_create_nonce( 'dsagfe_dismiss_pro_panel' );
	?>
	<details id="dsagfe-pro-panel" open style="max-width:1000px;margin:30px 0 10px;border:1px solid #dcdcde;border-radius:8px;background:#fbfaff;">
		<summary style="cursor:pointer;padding:14px 18px;font-size:15px;font-weight:600;color:#1d2327;">
			<?php esc_html_e( 'Entry Digest Pro - see what the optional add-on gives you', 'entry-digest-for-gravity-forms' ); ?>
		</summary>
		<div style="padding:4px 18px 18px;">
			<p style="max-width:700px;font-size:13px;color:#50575e;margin:0 0 16px;">
				<?php esc_html_e( 'Everything on this screen is free and unrestricted. The optional Pro add-on is a separate plugin that adds the extras below - nothing here is locked or disabled.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
				<?php foreach ( dsagfe_pro_features() as $feature ) : ?>
					<div style="background:#fff;border:1px solid #e2e2e7;border-radius:6px;padding:14px 16px;">
						<h3 style="margin:0 0 6px;font-size:14px;line-height:1.4;color:#1d2327;">
							<?php echo esc_html( $feature['title'] ); ?>
							<span style="display:inline-block;margin-left:5px;padding:1px 6px;border-radius:9px;background:#7c3aed;color:#fff;font-size:10px;font-weight:700;letter-spacing:.04em;vertical-align:middle;">PRO</span>
						</h3>
						<p style="margin:0;color:#646970;font-size:12.5px;line-height:1.55;"><?php echo esc_html( $feature['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<p style="margin:18px 0 4px;font-size:13.5px;font-weight:600;color:#1d2327;">
				<?php esc_html_e( 'Try Pro free for 14 days - no credit card required.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<p style="margin:6px 0 6px;">
				<a href="<?php echo esc_url( $pro_url ); ?>" class="button button-primary" target="_blank" rel="noopener">
					<?php esc_html_e( 'Get Entry Digest Pro', 'entry-digest-for-gravity-forms' ); ?>
				</a>
				<a href="#" id="dsagfe-pro-dismiss" style="margin-left:12px;color:#646970;font-size:12px;text-decoration:underline;">
					<?php esc_html_e( 'Hide this for a year', 'entry-digest-for-gravity-forms' ); ?>
				</a>
			</p>
			<p class="description" style="font-size:11.5px;color:#787c82;margin:4px 0 0;">
				<?php
				/* translators: %s: the external website host the button opens, e.g. addasitebuilders.com. */
				printf( esc_html__( 'Opens %s in a new tab.', 'entry-digest-for-gravity-forms' ), esc_html( (string) wp_parse_url( $pro_url, PHP_URL_HOST ) ) );
				?>
			</p>
		</div>
	</details>
	<script>
	( function () {
		var panel = document.getElementById( 'dsagfe-pro-panel' );
		if ( ! panel ) { return; }

		// Remember the collapsed/expanded state across page loads (per browser).
		try {
			if ( '1' === window.localStorage.getItem( 'dsagfeProPanelCollapsed' ) ) {
				panel.open = false;
			}
			panel.addEventListener( 'toggle', function () {
				try { window.localStorage.setItem( 'dsagfeProPanelCollapsed', panel.open ? '0' : '1' ); } catch ( err ) {}
			} );
		} catch ( err ) {}

		// "Hide this for a year" - dismiss server-side, per user.
		var link = document.getElementById( 'dsagfe-pro-dismiss' );
		if ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				panel.style.display = 'none';
				var fd = new FormData();
				fd.append( 'action', 'dsagfe_dismiss_pro_panel' );
				fd.append( 'nonce', '<?php echo esc_js( $nonce ); ?>' );
				fetch( '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', { method: 'POST', body: fd, credentials: 'same-origin' } );
			} );
		}
	}() );
	</script>
	<?php
}

/**
 * AJAX handler: remember the dismissal for one year (per user).
 */
function dsagfe_ajax_dismiss_pro_panel(): void {
	check_ajax_referer( 'dsagfe_dismiss_pro_panel', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', '', [ 'response' => 403 ] );
	}
	update_user_meta( get_current_user_id(), 'dsagfe_pro_panel_dismissed', time() + YEAR_IN_SECONDS );
	wp_die();
}
