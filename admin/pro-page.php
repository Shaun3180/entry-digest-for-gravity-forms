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
 *
 * The panel's styling lives in admin/css/pro-panel.css and its dismiss behavior
 * in admin/js/list.js - both enqueued from admin/enqueue.php. No CSS or JS is
 * emitted inline here.
 */

add_action( 'dsagfe_after_log_table', 'dsagfe_render_pro_panel' );
add_action( 'wp_ajax_dsagfe_dismiss_pro_panel', 'dsagfe_ajax_dismiss_pro_panel' );

/**
 * Whether the optional Pro add-on is installed and active. The add-on defines
 * EDFGFP_VERSION when it boots, so a constant check is the whole contract.
 */
function dsagfe_pro_active(): bool {
	return defined( 'EDFGFP_VERSION' );
}

/**
 * Small "PRO" badge markup used to label optional add-on features in the panel.
 */
function dsagfe_pro_badge(): string {
	return '<span class="dsagfe-pro-badge">' . esc_html__( 'PRO', 'entry-digest-for-gravity-forms' ) . '</span>';
}

/**
 * The Pro feature list shown in the panel.
 */
function dsagfe_pro_features(): array {
	return [
		[
			'title' => __( 'CSV & Excel Attachments', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Attach the full period’s entries as a CSV or native Excel (.xlsx) file. Recipients get the scannable summary in the email and the complete dataset ready to sort, filter, or import.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Conditional Filtering', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Include only the entries that matter. Set rules like “Status is Complete” or “Budget greater than 1000” - match all or any - and your digest filters itself automatically on every run.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Per-Recipient & Role Routing', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Send the right digest to the right people. Route your admissions form to your admissions lead and your support form to your help desk - by individual recipient or by WordPress role.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Custom Email Branding', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Upload your logo, set an accent color, and replace the default footer credit with your own text. Every digest your site sends reflects your brand instead of the plugin’s.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Extended Send History', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Configurable send-log retention beyond the default of ten recent sends.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Priority Support', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Get fast, direct help from the developer. Pro licenses come with priority email support so setup questions and edge cases don’t sit in a queue.', 'entry-digest-for-gravity-forms' ),
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
 * Whether the upsell panel should render for the current request/user. Used both
 * here and by admin/enqueue.php to decide whether to load the panel's assets.
 */
function dsagfe_pro_panel_should_show(): bool {
	return ! dsagfe_pro_active()
		&& current_user_can( 'manage_options' )
		&& ! dsagfe_pro_panel_dismissed();
}

/**
 * Render the collapsible Pro panel (free installs only).
 */
function dsagfe_render_pro_panel(): void {
	if ( ! dsagfe_pro_panel_should_show() ) {
		return;
	}

	$pro_url = dsagfe_pro_url();
	?>
	<details id="dsagfe-pro-panel" class="dsagfe-pro-panel" open>
		<summary class="dsagfe-pro-panel__summary">
			<?php esc_html_e( 'Entry Digest Pro - see what the optional add-on gives you', 'entry-digest-for-gravity-forms' ); ?>
		</summary>
		<div class="dsagfe-pro-panel__body">
			<p class="dsagfe-pro-panel__intro">
				<?php esc_html_e( 'Everything on this screen is free and unrestricted. The optional Pro add-on is a separate plugin that adds the extras below - nothing here is locked or disabled.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<div class="dsagfe-pro-grid">
				<?php foreach ( dsagfe_pro_features() as $feature ) : ?>
					<div class="dsagfe-pro-card">
						<h3 class="dsagfe-pro-card__title">
							<?php echo esc_html( $feature['title'] ); ?>
							<?php echo wp_kses_post( dsagfe_pro_badge() ); ?>
						</h3>
						<p class="dsagfe-pro-card__body"><?php echo esc_html( $feature['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="dsagfe-pro-panel__trial">
				<?php esc_html_e( 'Try Pro free for 14 days - no credit card required.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<p class="dsagfe-pro-panel__actions">
				<a href="<?php echo esc_url( $pro_url ); ?>" class="button button-primary" target="_blank" rel="noopener">
					<?php esc_html_e( 'Get Entry Digest Pro', 'entry-digest-for-gravity-forms' ); ?>
				</a>
				<a href="#"
					id="dsagfe-pro-dismiss"
					class="dsagfe-pro-dismiss"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'dsagfe_dismiss_pro_panel' ) ); ?>"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
					<?php esc_html_e( 'Hide this for a year', 'entry-digest-for-gravity-forms' ); ?>
				</a>
			</p>
			<p class="description dsagfe-pro-panel__note">
				<?php
				/* translators: %s: the external website host the button opens, e.g. addasitebuilders.com. */
				printf( esc_html__( 'Opens %s in a new tab.', 'entry-digest-for-gravity-forms' ), esc_html( (string) wp_parse_url( $pro_url, PHP_URL_HOST ) ) );
				?>
			</p>
		</div>
	</details>
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
