<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: configurable send-log retention.
 *
 * The free plugin keeps a small rolling log (a handful of recent sends) and
 * exposes the count through the `edfgf_log_max` filter. Pro hooks that filter
 * (see run.php) to honor a site-chosen value, and this file provides the UI to
 * set it: a control rendered beneath the core "Recent sends" table via the
 * `edfgf_after_log_table` action, saved to the `edfgfp_log_max` option through
 * admin-post.php.
 */

const EDFGFP_LOG_MAX_DEFAULT = 1000;
const EDFGFP_LOG_MAX_CEILING = 100000;

/**
 * Render the retention control under the send-log table.
 */
add_action( 'edfgf_after_log_table', 'edfgfp_render_log_setting' );
function edfgfp_render_log_setting(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Only render alongside the send-log table this setting governs, so it does not
	// appear on the empty first-run screen (before any digests have sent).
	if ( ! function_exists( 'edfgf_get_log' ) || empty( edfgf_get_log() ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only confirmation flag, no state change.
	if ( isset( $_GET['edfgfp_log_saved'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__( 'Send-log retention updated.', 'entry-digest-for-gravity-forms-pro' )
			. '</p></div>';
	}

	$current = (int) get_option( 'edfgfp_log_max', EDFGFP_LOG_MAX_DEFAULT );
	if ( $current < 1 ) {
		$current = EDFGFP_LOG_MAX_DEFAULT;
	}
	?>
	<h2 style="margin-top:30px;"><?php esc_html_e( 'Send-log history', 'entry-digest-for-gravity-forms-pro' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="edfgfp_save_log_max">
		<?php wp_nonce_field( 'edfgfp_save_log_max' ); ?>
		<p>
			<label>
				<?php esc_html_e( 'Number of past sends to keep:', 'entry-digest-for-gravity-forms-pro' ); ?>
				<input type="number" name="edfgfp_log_max" min="1" max="<?php echo esc_attr( (string) EDFGFP_LOG_MAX_CEILING ); ?>" step="1" value="<?php echo esc_attr( (string) $current ); ?>" style="width:110px;">
			</label>
			<button type="submit" class="button button-small"><?php esc_html_e( 'Save', 'entry-digest-for-gravity-forms-pro' ); ?></button>
		</p>
		<p class="description" style="font-size:12px;color:#666;">
			<?php esc_html_e( 'Overrides the free plugin’s default retention. A higher value keeps a longer history for debugging and audits.', 'entry-digest-for-gravity-forms-pro' ); ?>
		</p>
	</form>
	<?php
}

/**
 * Persist the retention setting, then redirect back to the digest list screen.
 */
add_action( 'admin_post_edfgfp_save_log_max', 'edfgfp_handle_save_log_max' );
function edfgfp_handle_save_log_max(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'entry-digest-for-gravity-forms-pro' ) );
	}
	check_admin_referer( 'edfgfp_save_log_max' );

	$max = isset( $_POST['edfgfp_log_max'] ) ? absint( wp_unslash( $_POST['edfgfp_log_max'] ) ) : EDFGFP_LOG_MAX_DEFAULT;
	$max = max( 1, min( EDFGFP_LOG_MAX_CEILING, $max ) );
	update_option( 'edfgfp_log_max', $max, false );

	$redirect = function_exists( 'edfgf_page_url' ) ? edfgf_page_url() : admin_url();
	wp_safe_redirect( add_query_arg( 'edfgfp_log_saved', '1', $redirect ) );
	exit;
}
