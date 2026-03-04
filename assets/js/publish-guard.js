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

	if ( 'undefined' === typeof wp || ! wp.data ) {
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

		// Hard fallback at data-layer level in case any DOM pathway misses.
		if ( title.length > TITLE_MAX ) {
			title = title.slice( 0, TITLE_MAX );
			dispatch( 'core/editor' ).editPost( { title: title } );
		}

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
		var rafPending = false;

		function scheduleBind() {
			if ( rafPending ) {
				return;
			}
			rafPending = true;
			window.requestAnimationFrame( function () {
				rafPending = false;
				bindTitleLimit();
			} );
		}

		scheduleBind();

		var observer = new MutationObserver( function () {
			scheduleBind();
		} );

		observer.observe( document.body, {
			childList: true,
			subtree: true,
		} );
	} );

	function bindTitleLimit() {
		var selectors = [
			'.editor-post-title__input',
			'[data-type="core/post-title"] [contenteditable="true"]',
			'h1.wp-block-post-title[contenteditable="true"]',
			'[aria-label="Add title"]',
			'[aria-label="Dodaj naslov"]',
			'[aria-label="Dodajte naslov"]',
		];

		selectors.forEach( function ( selector ) {
			document.querySelectorAll( selector ).forEach( function ( el ) {
				initTitleLimit( el );
			} );
		} );
	}

	function initTitleLimit( el ) {
		if ( el.dataset.kompasTitleLimitBound === '1' ) {
			return;
		}
		el.dataset.kompasTitleLimitBound = '1';

		if ( 'TEXTAREA' === el.tagName || 'INPUT' === el.tagName ) {
			// Native maxlength — browser enforces it hard.
			el.setAttribute( 'maxlength', TITLE_MAX );
			el.addEventListener( 'input', function () {
				if ( el.value.length > TITLE_MAX ) {
					el.value = el.value.slice( 0, TITLE_MAX );
				}
			} );
		} else {
			// contenteditable: beforeinput as primary block.
			el.addEventListener( 'beforeinput', function ( e ) {
				var text = el.textContent || '';
				var isDelete = e.inputType && (
					e.inputType.indexOf( 'delete' ) === 0 ||
					e.inputType === 'historyUndo' ||
					e.inputType === 'historyRedo'
				);
				if ( isDelete ) {
					return;
				}

				var selectedLen = getSelectedLengthInside( el );
				var incomingLen = getIncomingLength( e );
				var nextLen = text.length - selectedLen + incomingLen;

				if ( nextLen > TITLE_MAX ) {
					e.preventDefault();
				}
			} );

			// Fallback truncation: handles paste and any case beforeinput misses.
			el.addEventListener( 'input', function () {
				enforceContentEditableLimit( el );
			} );

			// Fallback for browsers where beforeinput is inconsistent.
			el.addEventListener( 'keydown', function ( e ) {
				if ( e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1 ) {
					return;
				}

				var text = el.textContent || '';
				var selectedLen = getSelectedLengthInside( el );
				if ( text.length - selectedLen >= TITLE_MAX ) {
					e.preventDefault();
				}
			} );
		}

		// Character counter element.
		var counter = el.nextElementSibling;
		if ( ! counter || counter.dataset.kompasTitleCounter !== '1' ) {
			counter = document.createElement( 'div' );
			counter.dataset.kompasTitleCounter = '1';
			counter.style.cssText = 'font-size:11px;text-align:right;padding:3px 4px 0;transition:color .15s;';
			el.parentNode.insertBefore( counter, el.nextSibling );
		}

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

	function enforceContentEditableLimit( el ) {
		var text = el.textContent || '';
		if ( text.length <= TITLE_MAX ) {
			return;
		}

		el.textContent = text.slice( 0, TITLE_MAX );
		moveCaretToEnd( el );
	}

	function moveCaretToEnd( el ) {
		var range = document.createRange();
		range.selectNodeContents( el );
		range.collapse( false );

		var sel = window.getSelection();
		if ( ! sel ) {
			return;
		}

		sel.removeAllRanges();
		sel.addRange( range );
	}

	function getIncomingLength( event ) {
		if ( 'string' === typeof event.data ) {
			return event.data.length;
		}

		if ( event.inputType === 'insertParagraph' || event.inputType === 'insertLineBreak' ) {
			return 1;
		}

		return 1;
	}

	function getSelectedLengthInside( el ) {
		var sel = window.getSelection();
		if ( ! sel || sel.rangeCount === 0 ) {
			return 0;
		}

		var range = sel.getRangeAt( 0 );
		if ( ! el.contains( range.startContainer ) || ! el.contains( range.endContainer ) ) {
			return 0;
		}

		return sel.toString().length;
	}

} )();
