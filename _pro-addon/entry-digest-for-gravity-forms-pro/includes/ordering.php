<?php
defined( 'ABSPATH' ) || exit;

/**
 * Pro: manual ordering of forms and fields in the digest editor.
 *
 * The free editor already submits the form list (edfgf_digest[form_ids][]) and
 * each form's field list (edfgf_digest[fields][FID][]) in DOM order, and the
 * renderer honors that order (sections follow form_ids; columns follow
 * edfgf_filter_field_map's selection order). So all we need to add is:
 *
 *   1. Tell the free editor to lay its lists out reorderable (selected items
 *      first, in saved order) via the edfgf_editor_reorderable filter.
 *   2. A small vanilla-JS layer that adds up/down buttons to each list item and
 *      swaps adjacent items, which changes the DOM order and therefore the order
 *      the values are submitted in.
 *
 * No new stored fields are needed - ordering rides on the existing arrays.
 */

// Render the editor's form/field lists in reorderable (selected-first) order.
add_filter( 'edfgf_editor_reorderable', '__return_true' );

// Up/down ordering controls. Emitted once on the digest editor screen only,
// matching the inline-footer-script pattern used by the conditional-filter UI.
add_action( 'admin_print_footer_scripts', 'edfgfp_ordering_script' );
function edfgfp_ordering_script(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen routing.
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	if ( 'new' !== $action && 'edit' !== $action ) {
		return;
	}

	$up_label   = esc_attr__( 'Move up', 'entry-digest-for-gravity-forms-pro' );
	$down_label = esc_attr__( 'Move down', 'entry-digest-for-gravity-forms-pro' );
	$hint       = esc_html__( 'Use the arrows to set the order forms and columns appear in the email.', 'entry-digest-for-gravity-forms-pro' );
	?>
	<script>
	( function () {
		'use strict';

		var UP = <?php echo wp_json_encode( $up_label ); ?>;
		var DOWN = <?php echo wp_json_encode( $down_label ); ?>;
		var HINT = <?php echo wp_json_encode( $hint ); ?>;

		function items( list ) {
			return Array.prototype.slice.call(
				list.querySelectorAll( ':scope > .edfgf-orderable-item' )
			);
		}

		function makeBtn( glyph, label ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'button-link edfgfp-order-btn';
			b.setAttribute( 'aria-label', label );
			b.title = label;
			b.textContent = glyph;
			b.style.cssText = 'margin-left:6px;text-decoration:none;font-size:13px;line-height:1;color:#2271b1;';
			return b;
		}

		function move( list, item, dir ) {
			var list_items = items( list );
			var i = list_items.indexOf( item );
			if ( i < 0 ) { return; }
			if ( dir < 0 && i > 0 ) {
				list.insertBefore( item, list_items[ i - 1 ] );
			} else if ( dir > 0 && i < list_items.length - 1 ) {
				list.insertBefore( list_items[ i + 1 ], item );
			}
		}

		function enhance( list, singleColumn ) {
			var list_items = items( list );
			// Nothing to reorder with fewer than two items.
			if ( list_items.length < 2 ) { return; }

			if ( singleColumn ) {
				list.style.columns = '1';
				list.style.webkitColumns = '1';
			}

			list_items.forEach( function ( item ) {
				if ( item.querySelector( '.edfgfp-order-btn' ) ) { return; }
				var up = makeBtn( '▲', UP );     // ▲
				var down = makeBtn( '▼', DOWN );  // ▼
				up.addEventListener( 'click', function ( e ) {
					e.preventDefault(); e.stopPropagation();
					move( list, item, -1 );
				} );
				down.addEventListener( 'click', function ( e ) {
					e.preventDefault(); e.stopPropagation();
					move( list, item, 1 );
				} );
				item.appendChild( up );
				item.appendChild( down );
			} );
		}

		function addHint( list ) {
			if ( items( list ).length < 2 ) { return; }
			var p = list.parentNode.querySelector( '.edfgfp-order-hint' );
			if ( p ) { return; }
			p = document.createElement( 'p' );
			p.className = 'description edfgfp-order-hint';
			p.style.cssText = 'margin:6px 0 8px;';
			p.textContent = HINT;
			list.parentNode.insertBefore( p, list );
		}

		document.querySelectorAll( '.edfgf-form-list' ).forEach( function ( list ) {
			addHint( list );
			enhance( list, false );
		} );
		document.querySelectorAll( '.edfgf-field-list' ).forEach( function ( list ) {
			enhance( list, true );
		} );
	}() );
	</script>
	<?php
}
