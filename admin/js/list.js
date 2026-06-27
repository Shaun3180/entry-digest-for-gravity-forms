/**
 * Entry Digest for Gravity Forms - digest list screen behavior.
 *
 * Handles two things on the digest list screen:
 *   1. A confirmation prompt before destructive form submissions (delete a
 *      digest, clear the send log). Forms opt in with class="edfgf-confirm" and
 *      a data-confirm message.
 *   2. The optional "Entry Digest Pro" upsell panel: remembering its
 *      collapsed/expanded state (per browser) and the "Hide this for a year"
 *      dismissal (persisted server-side, per user).
 *
 * The Pro panel's dismiss nonce and AJAX URL are read from data-nonce /
 * data-ajax-url attributes on the dismiss link itself (no localized globals).
 */
( function () {
	'use strict';

	// 1. Confirm before submitting destructive forms.
	document.querySelectorAll( 'form.edfgf-confirm' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			var msg = form.getAttribute( 'data-confirm' );
			if ( msg && ! window.confirm( msg ) ) {
				e.preventDefault();
			}
		} );
	} );

	// 2. Pro upsell panel.
	( function () {
		var panel = document.getElementById( 'edfgf-pro-panel' );
		if ( ! panel ) {
			return;
		}

		// Remember the collapsed/expanded state across page loads (per browser).
		try {
			if ( '1' === window.localStorage.getItem( 'edfgfProPanelCollapsed' ) ) {
				panel.open = false;
			}
			panel.addEventListener( 'toggle', function () {
				try {
					window.localStorage.setItem( 'edfgfProPanelCollapsed', panel.open ? '0' : '1' );
				} catch ( err ) {}
			} );
		} catch ( err ) {}

		// "Hide this for a year" - dismiss server-side, per user.
		var link = document.getElementById( 'edfgf-pro-dismiss' );
		if ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				panel.style.display = 'none';
				var fd = new FormData();
				fd.append( 'action', 'edfgf_dismiss_pro_panel' );
				fd.append( 'nonce', link.getAttribute( 'data-nonce' ) || '' );
				fetch( link.getAttribute( 'data-ajax-url' ), { method: 'POST', body: fd, credentials: 'same-origin' } );
			} );
		}
	}() );
}() );
