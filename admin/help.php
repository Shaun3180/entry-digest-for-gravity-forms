<?php
defined( 'ABSPATH' ) || exit;

/**
 * Contextual help tabs for the Entry Digest admin pages.
 *
 * Tabs differ by page:
 *   - List page  (action = list / absent): overview, actions, send log.
 *   - Editor page (action = new | edit):   digest & forms, schedule, fields, testing.
 */
add_action( 'current_screen', 'edfgf_add_help_tabs' );

function edfgf_add_help_tabs(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page identification, no state change.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'edfgf-entry-digest' !== $page ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
	$is_editor = in_array( $action, [ 'new', 'edit' ], true );

	if ( $is_editor ) {
		edfgf_help_tabs_editor( $screen );
	} else {
		edfgf_help_tabs_list( $screen );
	}

	$screen->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For more information:', 'entry-digest-for-gravity-forms' ) . '</strong></p>' .
		'<p><a href="https://addasitebuilders.com/plugins" target="_blank" rel="noopener">' . esc_html__( 'Plugin documentation', 'entry-digest-for-gravity-forms' ) . '</a></p>' .
		'<p><a href="https://wordpress.org/support/plugin/entry-digest-for-gravity-forms/" target="_blank" rel="noopener">' . esc_html__( 'Support forum', 'entry-digest-for-gravity-forms' ) . '</a></p>'
	);
}

/**
 * Help tabs for the digest list screen.
 */
function edfgf_help_tabs_list( WP_Screen $screen ): void {

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-overview',
		'title'   => __( 'How digests work', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( 'A digest batches your Gravity Forms entries into a single scheduled email rather than sending one notification per submission. On each scheduled run it looks back over a rolling window (24 hours for daily, 7 days for weekly) and emails a tidy table of everything that came in.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'You can create as many digests as you need - one per form, one per team, or any other split that makes sense. Each digest has its own recipient list, schedule, and field selection.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'Digests run via WP-Cron, which fires when your site receives a page request. If your site has very low traffic, consider setting up a real server cron job that hits wp-cron.php on schedule - your host may offer this, or you can use a free service like cron-job.org.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-actions',
		'title'   => __( 'List actions', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p><strong>' . esc_html__( 'Edit', 'entry-digest-for-gravity-forms' ) . '</strong> - ' . esc_html__( 'Opens the digest editor to change any settings.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p><strong>' . esc_html__( 'Duplicate', 'entry-digest-for-gravity-forms' ) . '</strong> - ' . esc_html__( 'Creates a copy of the digest with the same forms, fields, and schedule. The copy starts active with no one-time date, so it is safe to edit before it sends.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p><strong>' . esc_html__( 'Send Now', 'entry-digest-for-gravity-forms' ) . '</strong> - ' . esc_html__( 'Triggers the digest immediately using the current rolling window. This does not affect the next scheduled send - the schedule continues as normal. Useful for testing with real data or for sending on demand.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p><strong>' . esc_html__( 'Pause / Resume', 'entry-digest-for-gravity-forms' ) . '</strong> - ' . esc_html__( 'Pausing cancels all scheduled cron events for that digest without deleting it. Resuming re-registers them. Use this instead of deleting when you want to temporarily stop sends.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p><strong>' . esc_html__( 'Delete', 'entry-digest-for-gravity-forms' ) . '</strong> - ' . esc_html__( 'Permanently removes the digest and cancels its cron events. This cannot be undone.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-log',
		'title'   => __( 'Send log', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( '"Sent" means the email was handed to your site\'s mailer (wp_mail). It does not guarantee delivery to the inbox - that depends on your hosting environment, DNS records (SPF/DKIM), and the recipient\'s spam filters.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'If a digest is not arriving, check these in order:', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<ol>' .
			'<li>' . esc_html__( 'Spam or junk folder.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'<li>' . esc_html__( 'A mail-logging plugin (e.g. WP Mail SMTP or Post SMTP) to see whether wp_mail even fired.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'<li>' . esc_html__( 'Your host\'s mail logs or a transactional mail service (SendGrid, Mailgun, etc.) if one is configured.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'</ol>' .
			'<p>' . esc_html__( '"Skipped" appears when a digest found no new entries and its "When there are no new entries" setting is set to skip. "No entries" means it sent a quiet-period notification instead.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );
}

/**
 * Help tabs for the digest editor screen (new or edit).
 */
function edfgf_help_tabs_editor( WP_Screen $screen ): void {

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-digest-forms',
		'title'   => __( 'Digest & forms', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( 'The digest name is for your reference and doubles as the heading in the email when multiple forms are selected. It is optional - an untitled digest works fine if you only have one.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'When you select multiple forms, each form gets its own section and entry table inside the same email. Only forms that have entries in the time window produce a table - forms with no new entries are omitted silently.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'The "Entries right now" counter updates live as you tick and untick forms. It is a preview of what would be included if the digest ran at this moment, using your current field and form selections.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-schedule',
		'title'   => __( 'Schedule', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( 'Daily digests cover the 24 hours up to the moment they run. Weekly digests cover the 7 days up to the moment they run. The window is always rolling - it is not tied to a calendar day or week boundary.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'A one-time send and a recurring schedule can coexist on the same digest. For example you might want to send a catch-up today and then continue with a regular weekly send from next Monday.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'The one-time lookback controls how far back that single send reaches. Set it to 0 to include every entry right up to the send moment regardless of age. After the one-time send fires, the date field clears itself automatically so it does not run again.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'All times use your site timezone (shown next to the time field). If your site timezone is wrong, fix it under Settings > General before saving a digest schedule.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'Scheduled sends run via WP-Cron. If your site gets very little traffic, the send may fire a few minutes late. For time-sensitive digests, set up a real server cron job pointing at wp-cron.php.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-fields',
		'title'   => __( 'Fields & entries', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( 'If you leave all field checkboxes unchecked, every field on the form is included. Check specific fields to narrow the table to only those columns.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'Entry links add a clickable date on each row that opens the entry inside Gravity Forms. They only work for recipients who can log in to WordPress and have permission to view GF entries. Turn them off when emailing people without admin access - the link would be useless or broken for them.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'Long cell values (over 200 characters) are truncated in the inline table to keep the email readable. The full value is always in the entry itself inside Gravity Forms.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );

	$screen->add_help_tab( [
		'id'      => 'edfgf-help-testing',
		'title'   => __( 'Testing & troubleshooting', 'entry-digest-for-gravity-forms' ),
		'content' =>
			'<p>' . esc_html__( 'The test-send field appears only after you save the digest for the first time. Save any edits before sending a test, since the test always uses the last saved state - not your unsaved changes.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'A test send always delivers, even when there are no new entries. Your real recipient list is never contacted during a test.', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<p>' . esc_html__( 'If the test email does not arrive:', 'entry-digest-for-gravity-forms' ) . '</p>' .
			'<ol>' .
			'<li>' . esc_html__( 'Check your spam folder.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'<li>' . esc_html__( 'Install a mail-logging plugin (WP Mail SMTP, Post SMTP) to confirm whether wp_mail is firing and what error it returns.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'<li>' . esc_html__( 'If you are on shared hosting, try switching to a transactional mail service - many hosts block PHP mail by default.', 'entry-digest-for-gravity-forms' ) . '</li>' .
			'</ol>' .
			'<p>' . esc_html__( 'Use "Send Now" on the list screen to trigger the real digest to real recipients on demand, without waiting for the next scheduled run.', 'entry-digest-for-gravity-forms' ) . '</p>',
	] );
}
