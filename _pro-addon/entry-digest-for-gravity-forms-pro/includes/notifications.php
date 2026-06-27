<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: manage a form's Gravity Forms notifications from inside the digest editor.
 *
 * For each form a digest covers, the editor lists that form's GF notifications
 * with a switch that flips the notification's real "active" state in Gravity
 * Forms - the same switch GF's own notifications list uses. Changes are
 * persistent and user-owned: the switches always reflect GF's live state,
 * removing a digest never re-enables anything, and a per-notification link opens
 * the GF notification editor in a new tab.
 *
 * This edits Gravity Forms data, so it is gated on the GF "edit forms"
 * capability rather than the digest screen's own capability.
 */

/**
 * Whether the current user may edit Gravity Forms notifications.
 */
function edfgfp_can_edit_notifications(): bool {
	if ( class_exists( 'GFCommon' ) && method_exists( 'GFCommon', 'current_user_can_any' ) ) {
		return (bool) GFCommon::current_user_can_any( 'gravityforms_edit_forms' );
	}
	return current_user_can( 'manage_options' );
}

/**
 * One nonce shared by every toggle on the page.
 */
function edfgfp_notifications_nonce(): string {
	static $nonce = null;
	if ( null === $nonce ) {
		$nonce = wp_create_nonce( 'edfgfp_toggle_notification' );
	}
	return $nonce;
}

// ── Per-form notification controls (inside each form block) ──────────────────
add_action( 'edfgf_editor_form_block', 'edfgfp_editor_notifications_ui', 20, 3 );
function edfgfp_editor_notifications_ui( string $fid, array $d, array $field_map ): void {
	if ( ! class_exists( 'GFAPI' ) || ! edfgfp_can_edit_notifications() ) {
		return;
	}
	$form = GFAPI::get_form( (int) $fid );
	if ( ! $form ) {
		return;
	}
	$notifications = ( isset( $form['notifications'] ) && is_array( $form['notifications'] ) ) ? $form['notifications'] : [];

	// Flag the footer to print the toggle script once.
	$GLOBALS['edfgfp_notifications_rendered'] = true;
	$nonce = edfgfp_notifications_nonce();
	?>
	<p style="font-weight:600;margin:14px 0 6px;"><?php esc_html_e( 'Gravity Forms notifications', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<?php if ( empty( $notifications ) ) : ?>
		<p class="description"><?php esc_html_e( 'This form has no notifications.', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<?php else : ?>
		<p class="description" style="margin-bottom:6px;max-width:680px;">
			<?php esc_html_e( 'Turn a notification off to stop its per-entry email - this digest covers those entries instead. These switches change the notification directly in Gravity Forms and take effect immediately.', 'entry-digest-for-gravity-forms-pro' ); ?>
		</p>
		<table class="widefat striped" style="max-width:680px;margin-bottom:6px;">
			<tbody>
			<?php
			foreach ( $notifications as $nid => $note ) :
				$nid    = (string) $nid;
				$name   = (string) ( $note['name'] ?? $nid );
				$to     = (string) ( $note['to'] ?? '' );
				// GF treats a notification with no explicit isActive as active.
				$active = ! isset( $note['isActive'] ) || ! empty( $note['isActive'] );
				$edit_url = add_query_arg(
					[
						'page'    => 'gf_edit_forms',
						'view'    => 'settings',
						'subview' => 'notification',
						'id'      => (int) $fid,
						'nid'     => rawurlencode( $nid ),
					],
					admin_url( 'admin.php' )
				);
				?>
				<tr>
					<td style="width:46px;vertical-align:middle;">
						<input type="checkbox" class="edfgfp-note-toggle"
							data-fid="<?php echo esc_attr( $fid ); ?>"
							data-nid="<?php echo esc_attr( $nid ); ?>"
							data-nonce="<?php echo esc_attr( $nonce ); ?>"
							<?php checked( $active ); ?>>
					</td>
					<td style="vertical-align:middle;">
						<strong><?php echo esc_html( $name ); ?></strong>
						<?php if ( '' !== $to ) : ?>
							<span style="color:#646970;"> - <?php echo esc_html( $to ); ?></span>
						<?php endif; ?>
					</td>
					<td style="width:60px;text-align:right;vertical-align:middle;">
						<a href="<?php echo esc_url( $edit_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Edit', 'entry-digest-for-gravity-forms-pro' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description" style="margin:0;"><?php esc_html_e( 'On = the notification still sends per entry. Off = held back; only the digest goes out.', 'entry-digest-for-gravity-forms-pro' ); ?></p>
	<?php endif; ?>
	<?php
}

// ── Toggle script (printed once, on the editor screen only) ──────────────────
add_action( 'admin_print_footer_scripts', 'edfgfp_notifications_script' );
function edfgfp_notifications_script(): void {
	if ( empty( $GLOBALS['edfgfp_notifications_rendered'] ) ) {
		return;
	}
	?>
	<script>
	( function () {
		var boxes = document.querySelectorAll( '.edfgfp-note-toggle' );
		boxes.forEach( function ( box ) {
			box.addEventListener( 'change', function () {
				box.disabled = true;
				var fd = new FormData();
				fd.append( 'action', 'edfgfp_toggle_notification' );
				fd.append( 'nonce', box.getAttribute( 'data-nonce' ) );
				fd.append( 'form_id', box.getAttribute( 'data-fid' ) );
				fd.append( 'notification_id', box.getAttribute( 'data-nid' ) );
				fd.append( 'active', box.checked ? '1' : '0' );
				fetch( window.ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						box.disabled = false;
						if ( ! res || ! res.success ) { box.checked = ! box.checked; }
					} )
					.catch( function () { box.disabled = false; box.checked = ! box.checked; } );
			} );
		} );
	}() );
	</script>
	<?php
}

// ── AJAX: flip a notification's active state in Gravity Forms ─────────────────
add_action( 'wp_ajax_edfgfp_toggle_notification', 'edfgfp_ajax_toggle_notification' );
function edfgfp_ajax_toggle_notification(): void {
	check_ajax_referer( 'edfgfp_toggle_notification', 'nonce' );

	if ( ! class_exists( 'GFAPI' ) || ! edfgfp_can_edit_notifications() ) {
		wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
	}

	$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
	$nid     = isset( $_POST['notification_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notification_id'] ) ) : '';
	$active  = isset( $_POST['active'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['active'] ) );

	if ( ! $form_id || '' === $nid ) {
		wp_send_json_error( [ 'message' => 'bad_request' ], 400 );
	}

	$form = GFAPI::get_form( $form_id );
	if ( ! $form || empty( $form['notifications'] ) || ! isset( $form['notifications'][ $nid ] ) ) {
		wp_send_json_error( [ 'message' => 'not_found' ], 404 );
	}

	$notifications                       = $form['notifications'];
	$notifications[ $nid ]['isActive']   = $active;

	// Prefer the surgical notifications-only write; fall back to a full form update.
	if ( class_exists( 'GFFormsModel' ) && method_exists( 'GFFormsModel', 'save_form_notifications' ) ) {
		GFFormsModel::save_form_notifications( $form_id, $notifications );
	} else {
		$form['notifications'] = $notifications;
		$result                = GFAPI::update_form( $form );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => 'save_failed' ], 500 );
		}
	}

	wp_send_json_success( [ 'active' => $active ] );
}
