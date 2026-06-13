/**
 * Entry Digest for Gravity Forms — digest editor screen behavior.
 *
 * Localized data is provided by admin/enqueue.php:
 *   - window.DSAGFE_I18N  : translated UI strings
 *   - window.DSAGFE_COUNT : { url, nonce, gf } for the live entry-count preview
 */
( function () {
	'use strict';

	var I18N  = window.DSAGFE_I18N || {};
	var COUNT = window.DSAGFE_COUNT || {};

	// ── Show only the per-form config blocks for the selected forms ──────────
	( function () {
		function sync() {
			var checked = {};
			document.querySelectorAll( '.dsagfe-form-toggle' ).forEach( function ( el ) {
				if ( el.checked ) { checked[ el.dataset.fid ] = true; }
			} );
			document.querySelectorAll( '.dsagfe-form-block' ).forEach( function ( block ) {
				block.style.display = checked[ block.dataset.fid ] ? '' : 'none';
			} );
		}
		document.querySelectorAll( '.dsagfe-form-toggle' ).forEach( function ( el ) {
			el.addEventListener( 'change', sync );
		} );
		sync();
	} )();

	// ── Show/hide schedule rows based on frequency + one-time date ───────────
	( function () {
		var freq     = document.getElementById( 'dsagfe_freq' );
		var onetime  = document.getElementById( 'dsagfe_onetime' );
		var clearBtn = document.getElementById( 'dsagfe_onetime_clear' );
		if ( ! freq ) { return; }

		function show( sel, on ) {
			document.querySelectorAll( sel ).forEach( function ( el ) {
				el.style.display = on ? '' : 'none';
			} );
		}
		function sync() {
			var f = freq.value;
			show( '.dsagfe-weekly-row', f === 'weekly' );                     // send day: weekly only
			show( '.dsagfe-recurring-row', f === 'daily' || f === 'weekly' ); // recurring send time
			show( '.dsagfe-onetime-row', !! ( onetime && onetime.value ) );   // lookback: only with a date
		}
		freq.addEventListener( 'change', sync );
		if ( onetime ) { onetime.addEventListener( 'input', sync ); }
		if ( clearBtn && onetime ) {
			clearBtn.addEventListener( 'click', function () {
				onetime.value = '';
				sync();
				onetime.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		}
		sync();
	} )();

	// ── Live entry-count preview ─────────────────────────────────────────────
	( function () {
		var preview = document.getElementById( 'dsagfe-count-preview' );
		if ( ! preview ) { return; }

		function esc( s ) { var d = document.createElement( 'div' ); d.textContent = s; return d.innerHTML; }

		if ( ! COUNT.gf ) {
			preview.innerHTML = '<em>' + esc( I18N.gfInactive ) + '</em>';
			return;
		}

		var theForm = preview.closest( 'form' );
		var badges  = document.querySelectorAll( '.dsagfe-form-count' );
		var timer   = null;
		var seq     = 0;

		function render( data ) {
			var total = data.total | 0;
			var word  = ( total === 1 ) ? I18N.entry : I18N.entries;
			var html  = '<strong>' + total + '</strong> ' + esc( word ) + ' ' + esc( I18N.inThe ) + ' ' + esc( data.window );
			var fids  = Object.keys( data.per_form || {} );
			if ( fids.length > 1 ) {
				html += '<ul style="margin:6px 0 0 18px;list-style:disc;">';
				fids.forEach( function ( fid ) {
					var t = ( data.titles && data.titles[ fid ] ) ? data.titles[ fid ] : ( 'Form ' + fid );
					html += '<li>' + esc( t ) + ': <strong>' + ( data.per_form[ fid ] | 0 ) + '</strong></li>';
				} );
				html += '</ul>';
			} else if ( fids.length === 0 ) {
				html = '<em>' + esc( I18N.selectForm ) + '</em>';
			}
			preview.innerHTML = html;

			badges.forEach( function ( b ) {
				var fid = b.dataset.fid;
				if ( data.per_form && Object.prototype.hasOwnProperty.call( data.per_form, fid ) ) {
					var n = data.per_form[ fid ] | 0;
					var w = ( n === 1 ) ? I18N.entry : I18N.entries;
					b.textContent = '· ' + n + ' ' + w + ' ' + I18N.inWord + ' ' + data.window;
				} else {
					b.textContent = '';
				}
			} );
		}

		function update() {
			var mySeq = ++seq;
			preview.innerHTML = '<em>' + esc( I18N.calculating ) + '</em>';
			var fd = new FormData( theForm );
			fd.append( 'action', 'dsagfe_entry_count' );
			fd.append( 'nonce', COUNT.nonce );
			fetch( COUNT.url, { method: 'POST', body: fd, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( json ) {
					if ( mySeq !== seq ) { return; }
					if ( json && json.success ) {
						render( json.data );
					} else {
						preview.innerHTML = '<em>' + esc( ( json && json.data && json.data.message ) || I18N.unable ) + '</em>';
					}
				} )
				.catch( function () {
					if ( mySeq === seq ) { preview.innerHTML = '<em>' + esc( I18N.unable ) + '</em>'; }
				} );
		}

		function debounced() { clearTimeout( timer ); timer = setTimeout( update, 350 ); }

		theForm.addEventListener( 'change', function ( e ) {
			if ( e.target.closest && (
				e.target.id === 'dsagfe_freq' ||
				e.target.id === 'dsagfe_onetime' ||
				e.target.id === 'dsagfe_lookback' ||
				e.target.classList.contains( 'dsagfe-form-toggle' ) ||
				( e.target.name && ( e.target.name.indexOf( '[filters]' ) !== -1 || e.target.name.indexOf( '[filter_logic]' ) !== -1 ) )
			) ) { debounced(); }
		} );
		theForm.addEventListener( 'input', function ( e ) {
			if ( e.target.name && ( e.target.name.indexOf( '[filters]' ) !== -1 || e.target.id === 'dsagfe_lookback' ) ) { debounced(); }
		} );

		update();
	} )();
} )();
