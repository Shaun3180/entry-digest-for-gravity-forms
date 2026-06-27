<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: Email Preview card in the digest editor.
 *
 * Adds a "Preview Email" card below the Test send section. The card shows:
 *  - The rendered subject line (with common merge tags resolved)
 *  - The list of fields that will appear in the digest
 *  - The full styled email HTML (identical to what recipients receive),
 *    rendered via the free plugin's edfgf_build_digest_html() so Pro branding
 *    filters (logo, accent color, footer) are applied automatically
 *
 * The card is injected via admin_print_footer_scripts so it works with any
 * version of the free plugin without requiring a hook change there.
 *
 * Hooks:
 *  admin_print_footer_scripts   - render and inject the Preview card.
 *  wp_ajax_edfgfp_email_preview - generate preview data via AJAX.
 */

// ── Preview card ──────────────────────────────────────────────────────────────

add_action( 'admin_print_footer_scripts', 'edfgfp_preview_card' );
/**
 * Output the Preview Email card HTML and the JS that moves it into .wrap.
 * Runs in the admin footer so no changes to the free plugin are needed.
 */
function edfgfp_preview_card(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen routing.
	$page   = isset( $_GET['page'] )   ? sanitize_key( wp_unslash( $_GET['page'] ) )   : '';
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	// Only on the digest edit screen. Skip 'new' - unsaved digests have no ID.
	if ( 'edfgf-entry-digest' !== $page || 'edit' !== $action ) {
		return;
	}

	$digest_id = isset( $_GET['digest'] ) ? sanitize_text_field( wp_unslash( $_GET['digest'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' === $digest_id ) {
		return;
	}

	if ( ! function_exists( 'edfgf_get_digest' ) ) {
		return;
	}

	$d = edfgf_get_digest( $digest_id );
	if ( null === $d ) {
		return;
	}

	$nonce      = wp_create_nonce( 'edfgfp_email_preview_' . $digest_id );
	$card_style = 'background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:2px 20px 18px;margin:0 0 18px;max-width:900px;';
	$head_style = 'margin:14px 0 0;padding-bottom:8px;border-bottom:1px solid #f0f0f1;font-size:15px;';
	?>
	<div id="edfgfp-preview-card" style="<?php echo esc_attr( $card_style ); ?> display:none;">
		<h2 style="<?php echo esc_attr( $head_style ); ?>"><?php esc_html_e( 'Preview Email', 'entry-digest-for-gravity-forms-pro' ); ?></h2>

		<p style="margin:12px 0 16px;color:#646970;max-width:680px;">
			<?php esc_html_e(
				'Preview the digest email exactly as recipients will receive it, using your saved settings and realistic sample data. Save any changes first to see them reflected here.',
				'entry-digest-for-gravity-forms-pro'
			); ?>
		</p>

		<p>
			<button type="button" id="edfgfp-preview-btn" class="button button-primary"
				data-digest="<?php echo esc_attr( $digest_id ); ?>"
				data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<?php esc_html_e( 'Generate Preview', 'entry-digest-for-gravity-forms-pro' ); ?>
			</button>
			<span id="edfgfp-preview-spinner" class="spinner" style="float:none;vertical-align:middle;display:none;"></span>
		</p>

		<div id="edfgfp-preview-error" style="display:none;max-width:680px;" class="notice notice-error inline">
			<p></p>
		</div>

		<!-- Attachment indicator -->
		<div id="edfgfp-preview-attachment-wrap" style="display:none;margin:0 0 16px;">
			<p style="margin:0;padding:8px 12px;background:#f0f6fc;border:1px solid #c8d8e8;border-radius:4px;font-size:13px;color:#1d2327;max-width:680px;">
				<span style="display:inline-block;width:16px;height:16px;vertical-align:middle;margin-right:6px;margin-top:-2px;">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
				</span>
				<span id="edfgfp-preview-attachment-label"></span>
			</p>
		</div>

		<!-- Subject line -->
		<div id="edfgfp-preview-subject-wrap" style="display:none;margin:4px 0 16px;">
			<table class="form-table" style="margin:0;">
				<tr>
					<th scope="row" style="width:140px;padding:8px 10px 8px 0;">
						<?php esc_html_e( 'Subject', 'entry-digest-for-gravity-forms-pro' ); ?>
					</th>
					<td style="padding:8px 0;">
						<code id="edfgfp-preview-subject"
							style="display:block;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:3px;padding:8px 10px;font-size:13px;word-break:break-word;">
						</code>
					</td>
				</tr>
			</table>
		</div>

		<!-- Field list -->
		<div id="edfgfp-preview-fields-wrap" style="display:none;margin:0 0 16px;">
			<h3 style="margin:0 0 6px;font-size:13px;font-weight:600;">
				<?php esc_html_e( 'Fields included in this digest', 'entry-digest-for-gravity-forms-pro' ); ?>
			</h3>
			<ul id="edfgfp-preview-fields"
				style="margin:0;padding:0 0 0 18px;list-style:disc;font-size:13px;color:#1d2327;column-count:2;column-gap:40px;max-width:680px;">
			</ul>
		</div>

		<!-- Email HTML iframe -->
		<div id="edfgfp-preview-email-wrap" style="display:none;">
			<h3 style="margin:0 0 8px;font-size:13px;font-weight:600;">
				<?php esc_html_e( 'Email preview', 'entry-digest-for-gravity-forms-pro' ); ?>
			</h3>
			<div style="border:1px solid #c3c4c7;border-radius:4px;background:#fff;overflow:hidden;max-width:680px;">
				<iframe id="edfgfp-preview-iframe"
					style="width:100%;border:none;display:block;"
					title="<?php esc_attr_e( 'Email preview', 'entry-digest-for-gravity-forms-pro' ); ?>">
				</iframe>
			</div>
			<p style="margin:8px 0 0;font-size:11px;color:#8c8f94;">
				<?php esc_html_e(
					'Sample entries use generated placeholder values so you can judge layout and branding without using real data.',
					'entry-digest-for-gravity-forms-pro'
				); ?>
			</p>
		</div>
	</div>

	<script>
	( function () {
		'use strict';

		// Move the preview card from the footer into .wrap so it sits below the
		// Test send section, consistent with the other editor cards.
		var card = document.getElementById( 'edfgfp-preview-card' );
		var wrap = card && document.querySelector( '.wrap' );
		if ( wrap && card ) {
			wrap.appendChild( card );
			card.style.display = '';
		}

		// ── Button / AJAX wiring ──────────────────────────────────────────────
		var btn     = document.getElementById( 'edfgfp-preview-btn' );
		var spinner = document.getElementById( 'edfgfp-preview-spinner' );
		var errBox  = document.getElementById( 'edfgfp-preview-error' );
		var errMsg  = errBox && errBox.querySelector( 'p' );

		var subjectWrap = document.getElementById( 'edfgfp-preview-subject-wrap' );
		var subjectEl   = document.getElementById( 'edfgfp-preview-subject' );
		var fieldsWrap  = document.getElementById( 'edfgfp-preview-fields-wrap' );
		var fieldsList  = document.getElementById( 'edfgfp-preview-fields' );
		var emailWrap   = document.getElementById( 'edfgfp-preview-email-wrap' );
		var iframe      = document.getElementById( 'edfgfp-preview-iframe' );

		if ( ! btn ) { return; }

		function showError( msg ) {
			if ( errMsg ) { errMsg.textContent = msg; }
			if ( errBox ) { errBox.style.display = 'block'; }
		}

		function hideError() {
			if ( errBox ) { errBox.style.display = 'none'; }
		}

		function setLoading( loading ) {
			btn.disabled = loading;
			if ( spinner ) { spinner.style.display = loading ? 'inline-block' : 'none'; }
		}

		function writeIframe( html ) {
			// Use srcdoc (no sandbox) so external images like logos load without
			// cross-origin restrictions that document.write() in a sandboxed
			// iframe can trigger.
			iframe.addEventListener( 'load', function () {
				try {
					var body = iframe.contentDocument.body;
					if ( body ) {
						iframe.style.height = ( body.scrollHeight + 24 ) + 'px';
					}
				} catch ( e ) {
					iframe.style.height = '600px';
				}
			}, { once: true } );
			iframe.srcdoc = html;
		}

		btn.addEventListener( 'click', function () {
			hideError();
			setLoading( true );
			var attachWrapReset = document.getElementById( 'edfgfp-preview-attachment-wrap' );
			if ( attachWrapReset ) { attachWrapReset.style.display = 'none'; }

			var fd = new FormData();
			fd.append( 'action',    'edfgfp_email_preview' );
			fd.append( 'nonce',     btn.getAttribute( 'data-nonce' ) );
			fd.append( 'digest_id', btn.getAttribute( 'data-digest' ) );

			fetch( window.ajaxurl, {
				method:      'POST',
				body:        fd,
				credentials: 'same-origin',
			} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				setLoading( false );
				if ( ! res || ! res.success ) {
					showError( ( res && res.data && res.data.message ) || <?php echo wp_json_encode( __( 'Preview generation failed. Please try again.', 'entry-digest-for-gravity-forms-pro' ) ); ?> );
					return;
				}

				var data = res.data;

				// Attachment indicator.
				var attachWrap  = document.getElementById( 'edfgfp-preview-attachment-wrap' );
				var attachLabel = document.getElementById( 'edfgfp-preview-attachment-label' );
				var fmt = data.attach_format || 'none';
				if ( attachWrap && attachLabel && fmt !== 'none' ) {
					var fmtName = fmt === 'xlsx'
						? <?php echo wp_json_encode( __( 'Excel (.xlsx)', 'entry-digest-for-gravity-forms-pro' ) ); ?>
						: <?php echo wp_json_encode( __( 'CSV (.csv)', 'entry-digest-for-gravity-forms-pro' ) ); ?>;
					attachLabel.textContent = <?php echo wp_json_encode( __( 'This digest will include a', 'entry-digest-for-gravity-forms-pro' ) ); ?> + ' ' + fmtName + ' ' + <?php echo wp_json_encode( __( 'attachment with the full entry data.', 'entry-digest-for-gravity-forms-pro' ) ); ?>;
					attachWrap.style.display = '';
				} else if ( attachWrap ) {
					attachWrap.style.display = 'none';
				}

				// Subject.
				if ( subjectEl ) { subjectEl.textContent = data.subject || ''; }
				if ( subjectWrap ) { subjectWrap.style.display = data.subject ? '' : 'none'; }

				// Fields list.
				if ( fieldsList ) { fieldsList.innerHTML = ''; }
				if ( data.fields && data.fields.length && fieldsList ) {
					data.fields.forEach( function ( f ) {
						var li = document.createElement( 'li' );
						li.textContent = f;
						fieldsList.appendChild( li );
					} );
					if ( fieldsWrap ) { fieldsWrap.style.display = ''; }
				} else if ( fieldsWrap ) {
					fieldsWrap.style.display = 'none';
				}

				// Email HTML.
				if ( data.html && iframe ) {
					writeIframe( data.html );
					if ( emailWrap ) { emailWrap.style.display = ''; }
				} else if ( emailWrap ) {
					emailWrap.style.display = 'none';
				}
			} )
			.catch( function () {
				setLoading( false );
				showError( <?php echo wp_json_encode( __( 'Network error. Please try again.', 'entry-digest-for-gravity-forms-pro' ) ); ?> );
			} );
		} );
	}() );
	</script>
	<?php
}

// ── AJAX: generate the preview ────────────────────────────────────────────────

add_action( 'wp_ajax_edfgfp_email_preview', 'edfgfp_ajax_email_preview' );
/**
 * Generate and return the preview data: subject, field list, and full email HTML.
 */
function edfgfp_ajax_email_preview(): void {
	$digest_id = isset( $_POST['digest_id'] ) ? sanitize_text_field( wp_unslash( $_POST['digest_id'] ) ) : '';

	check_ajax_referer( 'edfgfp_email_preview_' . $digest_id, 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'entry-digest-for-gravity-forms-pro' ) ], 403 );
	}

	if ( ! function_exists( 'edfgf_get_digests' ) || ! class_exists( 'GFAPI' ) ) {
		wp_send_json_error( [ 'message' => __( 'Required plugins are not active.', 'entry-digest-for-gravity-forms-pro' ) ], 500 );
	}

	// Load the saved digest.
	$d = edfgf_get_digest( $digest_id );
	if ( null === $d ) {
		wp_send_json_error( [ 'message' => __( 'Digest not found. Make sure you have saved it first.', 'entry-digest-for-gravity-forms-pro' ) ], 404 );
	}

	// Collect form IDs this digest covers.
	$form_ids = array_map( 'intval', array_filter( (array) ( $d['form_ids'] ?? [] ) ) );
	if ( empty( $form_ids ) ) {
		wp_send_json_error( [ 'message' => __( 'No forms are assigned to this digest yet.', 'entry-digest-for-gravity-forms-pro' ) ], 400 );
	}

	// ── Build sections with dummy entries ─────────────────────────────────────
	$sections   = [];
	$all_fields = [];

	foreach ( $form_ids as $fid ) {
		$form = GFAPI::get_form( $fid );
		if ( ! $form ) {
			continue;
		}

		// Mirror the free plugin's field-map logic exactly.
		$field_map = edfgf_filter_field_map(
			edfgf_build_field_map( $form ),
			(array) ( $d['fields'][ (string) $fid ] ?? [] )
		);

		foreach ( array_values( $field_map ) as $label ) {
			if ( ! in_array( $label, $all_fields, true ) ) {
				$all_fields[] = $label;
			}
		}

		$dummy_entries = edfgfp_preview_dummy_entries( $field_map, 3 );

		$sections[] = [
			'form'      => $form,
			'field_map' => $field_map,
			'entries'   => $dummy_entries,
			'count'     => count( $dummy_entries ),
		];
	}

	if ( empty( $sections ) ) {
		wp_send_json_error( [ 'message' => __( 'None of the assigned forms could be loaded.', 'entry-digest-for-gravity-forms-pro' ) ], 400 );
	}

	// ── Subject ───────────────────────────────────────────────────────────────
	$raw_subject = (string) ( $d['email_subject'] ?? '' );
	if ( '' === $raw_subject ) {
		$form_titles = array_map(
			static fn( $s ) => (string) ( $s['form']['title'] ?? '' ),
			$sections
		);
		$raw_subject = sprintf(
			/* translators: %s: comma-separated form titles */
			__( 'Entry Digest: %s', 'entry-digest-for-gravity-forms' ),
			implode( ', ', array_filter( $form_titles ) )
		);
	}
	$subject = edfgfp_preview_resolve_subject( $raw_subject, $sections, $d );

	// ── Email HTML ────────────────────────────────────────────────────────────
	// Call edfgf_build_digest_html() so Pro branding filters (logo, accent,
	// footer) are applied automatically - the preview is pixel-perfect to the
	// real send.
	$total_count = array_sum( array_column( $sections, 'count' ) );

	$end_date   = gmdate( 'Y-m-d H:i:s' );
	$days_back  = ( 'daily' === ( $d['frequency'] ?? 'weekly' ) ) ? 1 : 7;
	$start_date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days_back . ' days' ) );

	$html = edfgf_build_digest_html( $sections, $d, $total_count, $start_date, $end_date, 'recurring' );

	wp_send_json_success( [
		'subject'       => $subject,
		'fields'        => $all_fields,
		'html'          => $html,
		'attach_format' => (string) ( $d['attach_format'] ?? 'none' ),
	] );
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Generate a set of dummy entries that look realistic.
 * Each entry has every field in the field_map filled with heuristic sample data.
 *
 * @param array $field_map [ field_key => label ]
 * @param int   $count     Number of dummy entries to generate.
 * @return array
 */
function edfgfp_preview_dummy_entries( array $field_map, int $count = 3 ): array {
	$sample_names  = [ 'Alice Johnson', 'Bob Martinez', 'Carol Smith', 'David Lee', 'Eva Chen' ];
	$sample_emails = [ 'alice@example.com', 'bob@example.com', 'carol@example.com', 'david@example.com', 'eva@example.com' ];
	$sample_phones = [ '(303) 555-0101', '(303) 555-0182', '(303) 555-0143', '(303) 555-0197', '(303) 555-0156' ];
	$sample_msgs   = [
		'I would love more information about your services.',
		'Please send me your latest pricing guide.',
		'Looking forward to hearing from you soon.',
	];
	$sample_dates  = [ '2025-06-01', '2025-06-08', '2025-06-15', '2025-06-22', '2025-06-28' ];

	$entries = [];
	for ( $i = 0; $i < $count; $i++ ) {
		$idx   = $i % 5;
		$entry = [
			'id'           => (string) ( 1000 + $i + 1 ),
			'date_created' => $sample_dates[ $idx ] . ' ' . sprintf( '%02d:00:00', 9 + $i ),
			'ip'           => '192.168.1.' . ( 10 + $i ),
		];

		foreach ( $field_map as $fid => $label ) {
			$lower = strtolower( $label );

			if ( str_contains( $lower, 'name' ) ) {
				$val = $sample_names[ $idx ];
			} elseif ( str_contains( $lower, 'email' ) ) {
				$val = $sample_emails[ $idx ];
			} elseif ( str_contains( $lower, 'phone' ) || str_contains( $lower, 'tel' ) ) {
				$val = $sample_phones[ $idx ];
			} elseif ( str_contains( $lower, 'message' ) || str_contains( $lower, 'comment' ) || str_contains( $lower, 'note' ) ) {
				$val = $sample_msgs[ $i % count( $sample_msgs ) ];
			} elseif ( str_contains( $lower, 'date' ) ) {
				$val = $sample_dates[ $idx ];
			} elseif ( str_contains( $lower, 'agree' ) || str_contains( $lower, 'accept' ) || str_contains( $lower, 'consent' ) ) {
				$val = 'Yes';
			} else {
				$val = 'Sample value ' . ( $i + 1 );
			}

			$entry[ $fid ] = $val;
		}

		$entries[] = $entry;
	}

	return $entries;
}

/**
 * Resolve common merge tags in the subject line for the preview.
 */
function edfgfp_preview_resolve_subject( string $subject, array $sections, array $d ): string {
	$total      = array_sum( array_column( $sections, 'count' ) );
	$form_title = (string) ( $sections[0]['form']['title'] ?? '' );
	$period     = edfgfp_preview_period_label( $d );
	$site_name  = get_bloginfo( 'name' );
	$today      = wp_date( get_option( 'date_format' ) );

	$replacements = [
		'{entry_count}' => (string) $total,
		'{form_title}'  => $form_title,
		'{form_name}'   => $form_title,
		'{period}'      => $period,
		'{site_name}'   => $site_name,
		'{site_title}'  => $site_name,
		'{date}'        => $today,
		'{today}'       => $today,
	];

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $subject );
}

/**
 * Return a human-readable period label for the preview (e.g. "Daily", "Weekly").
 */
function edfgfp_preview_period_label( array $d ): string {
	$freq = (string) ( $d['frequency'] ?? 'weekly' );
	$map  = [
		'daily'   => __( 'Daily', 'entry-digest-for-gravity-forms' ),
		'weekly'  => __( 'Weekly', 'entry-digest-for-gravity-forms' ),
		'monthly' => __( 'Monthly', 'entry-digest-for-gravity-forms' ),
		'hourly'  => __( 'Hourly', 'entry-digest-for-gravity-forms' ),
	];
	return $map[ $freq ] ?? ucfirst( $freq );
}
