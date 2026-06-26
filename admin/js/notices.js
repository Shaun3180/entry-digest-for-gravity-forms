/**
 * Entry Digest for Gravity Forms - admin notice behavior.
 *
 * Wires up the dismiss button on the "scheduled sends may not be running"
 * overdue-cron notice so dismissing it is remembered server-side (per user).
 *
 * Data is provided by admin/enqueue.php via wp_localize_script():
 *   - window.DSAGFE_NOTICE : { url, nonce }
 * The overdue event timestamp is read from the notice's data-earliest attribute.
 */
( function () {
	'use strict';

	var cfg = window.DSAGFE_NOTICE || {};

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target;
		if ( ! btn || ! btn.classList || ! btn.classList.contains( 'notice-dismiss' ) ) {
			return;
		}
		var notice = btn.closest( '#dsagfe-overdue-notice' );
		if ( ! notice ) {
			return;
		}
		var fd = new FormData();
		fd.append( 'action', 'dsagfe_dismiss_overdue_notice' );
		fd.append( 'nonce', cfg.nonce || '' );
		fd.append( 'earliest', notice.getAttribute( 'data-earliest' ) || '' );
		fetch( cfg.url, { method: 'POST', body: fd, credentials: 'same-origin' } );
	} );
}() );
