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

add_action( 'edfgf_after_log_table', 'edfgf_render_pro_panel' );
add_action( 'wp_ajax_edfgf_dismiss_pro_panel', 'edfgf_ajax_dismiss_pro_panel' );

/**
 * Whether the optional Pro add-on is installed and active. The add-on defines
 * EDFGFP_VERSION when it boots, so a constant check is the whole contract.
 */
function edfgf_pro_active(): bool {
	return defined( 'EDFGFP_VERSION' );
}

/**
 * Small "PRO" badge markup used to label optional add-on features in the panel.
 */
function edfgf_pro_badge(): string {
	return '<span class="edfgf-pro-badge">' . esc_html__( 'PRO', 'entry-digest-for-gravity-forms' ) . '</span>';
}

/**
 * The Pro feature list shown in the panel.
 */
function edfgf_pro_features(): array {
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
			'body'  => __( 'Make every digest yours: add your logo above, left, or right of the title, set the header accent and text colors, pick an email-safe font, and customize the footer background, text color, and credit line.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Email Preview', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'See what recipients will get before you send. Preview the fully styled digest - with your branding, fields, and realistic sample data - right in the editor, instead of waiting for the next scheduled run.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Notification Controls', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Manage each form’s Gravity Forms notifications from inside the digest editor. Switch off the per-entry emails once a scheduled digest covers them - the toggles change Gravity Forms directly and take effect immediately.', 'entry-digest-for-gravity-forms' ),
		],
		[
			'title' => __( 'Form & Field Ordering', 'entry-digest-for-gravity-forms' ),
			'body'  => __( 'Arrange a digest exactly how you want it. Simple up/down controls set the order forms appear and the order fields show as columns in the entry table.', 'entry-digest-for-gravity-forms' ),
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
function edfgf_pro_panel_dismissed(): bool {
	$until = (int) get_user_meta( get_current_user_id(), 'edfgf_pro_panel_dismissed', true );
	return $until > time();
}

/**
 * Whether the upsell panel should render for the current request/user. Used both
 * here and by admin/enqueue.php to decide whether to load the panel's assets.
 */
function edfgf_pro_panel_should_show(): bool {
	return ! edfgf_pro_active()
		&& current_user_can( 'manage_options' )
		&& ! edfgf_pro_panel_dismissed();
}

/**
 * Render the collapsible Pro panel (free installs only).
 */
function edfgf_render_pro_panel(): void {
	if ( ! edfgf_pro_panel_should_show() ) {
		return;
	}

	$pro_url = edfgf_pro_url();
	?>
	<details id="edfgf-pro-panel" class="edfgf-pro-panel" open>
		<summary class="edfgf-pro-panel__summary">
			<?php esc_html_e( 'Entry Digest Pro - see what the optional add-on gives you', 'entry-digest-for-gravity-forms' ); ?>
		</summary>
		<div class="edfgf-pro-panel__body">
			<p class="edfgf-pro-panel__intro">
				<?php esc_html_e( 'Everything on this screen is free and unrestricted. The optional Pro add-on is a separate plugin that adds the extras below - nothing here is locked or disabled.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<div class="edfgf-pro-grid">
				<?php foreach ( edfgf_pro_features() as $feature ) : ?>
					<div class="edfgf-pro-card">
						<h3 class="edfgf-pro-card__title">
							<?php echo esc_html( $feature['title'] ); ?>
							<?php echo wp_kses_post( edfgf_pro_badge() ); ?>
						</h3>
						<p class="edfgf-pro-card__body"><?php echo esc_html( $feature['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="edfgf-pro-panel__trial">
				<?php esc_html_e( 'Try Pro free for 14 days - no credit card required.', 'entry-digest-for-gravity-forms' ); ?>
			</p>
			<p class="edfgf-pro-panel__actions">
				<a href="<?php echo esc_url( $pro_url ); ?>" class="button button-primary" target="_blank" rel="noopener">
					<?php esc_html_e( 'Get Entry Digest Pro', 'entry-digest-for-gravity-forms' ); ?>
				</a>
				<a href="#"
					id="edfgf-pro-dismiss"
					class="edfgf-pro-dismiss"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'edfgf_dismiss_pro_panel' ) ); ?>"
					data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
					<?php esc_html_e( 'Hide this for a year', 'entry-digest-for-gravity-forms' ); ?>
				</a>
			</p>
			<p class="description edfgf-pro-panel__note">
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
function edfgf_ajax_dismiss_pro_panel(): void {
	check_ajax_referer( 'edfgf_dismiss_pro_panel', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', '', [ 'response' => 403 ] );
	}
	update_user_meta( get_current_user_id(), 'edfgf_pro_panel_dismissed', time() + YEAR_IN_SECONDS );
	wp_die();
}
