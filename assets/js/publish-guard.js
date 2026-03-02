/* global wp, kompasPublishGuard */
/**
 * Publish guard: prevents publishing a post without a featured image and a
 * non-default category. Shows an error notice and locks saving when conditions
 * are not met.
 *
 * Also enforces a 60-character limit on the post title via DOM manipulation
 * (maxlength / input handler) and a live character counter.
 */
( function () {
	'use strict';

	if ( ! wp || ! wp.data ) {
		return;
	}

	var subscribe        = wp.data.subscribe;
	var select           = wp.data.select;
	var dispatch         = wp.data.dispatch;
	var LOCK_KEY         = 'kompas-publish-guard';
	var NOTICE_ID        = 'kompas-publish-guard';
	var TITLE_MAX        = 60;
	var uncategorizedId  = ( window.kompasPublishGuard || {} ).uncategorizedId || 1;

	// Track previous error string to avoid re-dispatching on every store change.
	var prevIssues = undefined; // eslint-disable-line no-undefined

	// ── Publish guard ─────────────────────────────────────────────────────────

	subscribe( function () {
		var editor = select( 'core/editor' );
		if ( ! editor ) {
			return;
		}

		var status = editor.getEditedPostAttribute( 'status' );

		// Only guard when publishing or scheduling; drafts / pending are fine.
		if ( 'publish' !== status && 'future' !== status ) {
			if ( prevIssues !== null ) {
				prevIssues = null;
				dispatch( 'core/editor' ).unlockPostSaving( LOCK_KEY );
				dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
			}
			return;
		}

		var featuredImageId = editor.getEditedPostAttribute( 'featured_media' );
		var categories      = editor.getEditedPostAttribute( 'categories' ) || [];
		var realCategories  = categories.filter( function ( id ) {
			return id !== uncategorizedId;
		} );
		var title = editor.getEditedPostAttribute( 'title' ) || '';

		var errors = [];
		if ( ! featuredImageId ) {
			errors.push( 'naslovna slika nije postavljena' );
		}
		if ( realCategories.length === 0 ) {
			errors.push( 'kategorija nije izabrana' );
		}
		if ( title.length > TITLE_MAX ) {
			errors.push( 'naslov je duži od ' + TITLE_MAX + ' karaktera' );
		}

		var issues = errors.length > 0 ? errors.join( ', ' ) : null;

		// Bail early if nothing changed (prevents infinite re-renders).
		if ( issues === prevIssues ) {
			return;
		}
		prevIssues = issues;

		if ( issues ) {
			dispatch( 'core/editor' ).lockPostSaving( LOCK_KEY );
			dispatch( 'core/notices' ).createErrorNotice(
				'Vest ne može biti objavljena: ' + issues + '.',
				{ id: NOTICE_ID, isDismissible: true }
			);
		} else {
			dispatch( 'core/editor' ).unlockPostSaving( LOCK_KEY );
			dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
		}
	} );

	// ── Title character limit (DOM) ───────────────────────────────────────────

	if ( ! wp.domReady ) {
		return;
	}

	wp.domReady( function () {
		var tries = 0;
		var timer = setInterval( function () {
			// Gutenberg renders the title as a <textarea> (classic) or a
			// contenteditable element (newer builds). Try both selectors.
			var el = document.querySelector( '.editor-post-title__input' )
			      || document.querySelector( '[aria-label="Add title"]' )
			      || document.querySelector( '[aria-label="Dodaj naslov"]' );

			if ( el ) {
				clearInterval( timer );
				initTitleLimit( el );
			} else if ( ++tries > 40 ) {
				clearInterval( timer );
			}
		}, 250 );
	} );

	function initTitleLimit( el ) {
		if ( 'TEXTAREA' === el.tagName || 'INPUT' === el.tagName ) {
			// Native maxlength — browser enforces it hard.
			el.setAttribute( 'maxlength', TITLE_MAX );
		} else {
			// contenteditable: truncate on input.
			el.addEventListener( 'input', function () {
				var text = el.textContent || '';
				if ( text.length <= TITLE_MAX ) {
					return;
				}
				// Truncate and restore cursor to end.
				el.textContent = text.slice( 0, TITLE_MAX );
				var range = document.createRange();
				range.selectNodeContents( el );
				range.collapse( false );
				var sel = window.getSelection();
				sel.removeAllRanges();
				sel.addRange( range );
			} );
		}

		// Character counter element.
		var counter = document.createElement( 'div' );
		counter.id  = 'kompas-title-counter';
		counter.style.cssText = 'font-size:11px;text-align:right;padding:3px 4px 0;transition:color .15s;';
		el.parentNode.insertBefore( counter, el.nextSibling );

		function updateCounter() {
			var len = ( 'TEXTAREA' === el.tagName || 'INPUT' === el.tagName )
				? el.value.length
				: ( el.textContent || '' ).length;
			counter.textContent = len + ' / ' + TITLE_MAX;
			counter.style.color = len >= TITLE_MAX ? '#cc1818' : '#757575';
		}

		el.addEventListener( 'input', updateCounter );
		updateCounter();
	}

} )();
