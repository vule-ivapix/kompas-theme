/* global kompasAdminTitleLimit */
( function () {
	'use strict';

	var TITLE_MAX = parseInt( ( window.kompasAdminTitleLimit || {} ).max, 10 );
	if ( ! TITLE_MAX || TITLE_MAX < 1 ) {
		TITLE_MAX = 60;
	}
	var lastNoticeAt = 0;
	var NOTICE_COOLDOWN_MS = 1200;
	var boundDocs = [];
	var editorStoreGuardStarted = false;
	var htmlDecodeEl = null;

	function decodeTitleEntities( text ) {
		if ( 'string' !== typeof text || text.indexOf( '&' ) === -1 ) {
			return text || '';
		}

		if ( ! htmlDecodeEl ) {
			htmlDecodeEl = document.createElement( 'textarea' );
		}

		htmlDecodeEl.innerHTML = text;
		return htmlDecodeEl.value;
	}

	function getEntityAwareTitleLength( text ) {
		return decodeTitleEntities( text ).length;
	}

	function truncateEntityAwareTitle( text, limit ) {
		var raw = 'string' === typeof text ? text : '';

		if ( raw.indexOf( '&' ) === -1 ) {
			return raw.slice( 0, limit );
		}

		var decoded = decodeTitleEntities( raw );
		return decoded.length <= limit ? raw : decoded.slice( 0, limit );
	}

	function bindTitleLimits( rootDoc ) {
		if ( ! rootDoc || ! rootDoc.querySelectorAll ) {
			return;
		}

		var selectors = [
			'#title',
			'input[name="post_title"]',
			'textarea.editor-post-title__input',
			'.editor-post-title__input',
			'.editor-visual-editor__post-title-wrapper [contenteditable="true"]',
			'.edit-post-visual-editor__post-title-wrapper [contenteditable="true"]',
			'[data-type="core/post-title"] [contenteditable="true"]',
			'h1.wp-block-post-title[contenteditable="true"]',
			'.wp-block-post-title[contenteditable="true"]',
			'.wp-block-post-title [contenteditable="true"]',
			'[aria-label="Add title"]',
			'[aria-label="Dodaj naslov"]',
			'[aria-label="Dodajte naslov"]',
		];

		selectors.forEach( function ( selector ) {
			rootDoc.querySelectorAll( selector ).forEach( function ( el ) {
				if ( isTextInput( el ) ) {
					bindTextInput( el );
					return;
				}

				if ( isContentEditable( el ) ) {
					bindContentEditable( el );
				}
			} );
		} );
	}

	function isTextInput( el ) {
		return !! el && ( 'INPUT' === el.tagName || 'TEXTAREA' === el.tagName );
	}

	function isContentEditable( el ) {
		return !! el && el.isContentEditable;
	}

	function bindTextInput( el ) {
		if ( el.dataset.kompasTitleInputLimitBound === '1' ) {
			return;
		}
		el.dataset.kompasTitleInputLimitBound = '1';

		el.setAttribute( 'maxlength', TITLE_MAX );

		el.addEventListener( 'input', function () {
			if ( el.value.length <= TITLE_MAX ) {
				return;
			}

			var pos = typeof el.selectionStart === 'number' ? el.selectionStart : TITLE_MAX;
			el.value = el.value.slice( 0, TITLE_MAX );
			showLimitNotice();
			if ( el.ownerDocument && el === el.ownerDocument.activeElement ) {
				try {
					el.setSelectionRange( Math.min( pos, TITLE_MAX ), Math.min( pos, TITLE_MAX ) );
				} catch ( err ) { // eslint-disable-line no-unused-vars
					// Ignore selection errors on unsupported input types.
				}
			}
		} );
	}

	function bindContentEditable( el ) {
		if ( el.dataset.kompasTitleEditableLimitBound === '1' ) {
			return;
		}
		el.dataset.kompasTitleEditableLimitBound = '1';

		el.addEventListener( 'beforeinput', function ( e ) {
			var isDelete = e.inputType && (
				e.inputType.indexOf( 'delete' ) === 0 ||
				e.inputType === 'historyUndo' ||
				e.inputType === 'historyRedo'
			);
			if ( isDelete ) {
				return;
			}

			var text = el.textContent || '';
			var selectedLen = getSelectedLengthInside( el );
			var incomingLen = getIncomingLength( e );
			var nextLen = text.length - selectedLen + incomingLen;

			if ( nextLen > TITLE_MAX ) {
				e.preventDefault();
				showLimitNotice();
			}
		} );

		el.addEventListener( 'keydown', function ( e ) {
			if ( e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1 ) {
				return;
			}

			var text = el.textContent || '';
			var selectedLen = getSelectedLengthInside( el );
			if ( text.length - selectedLen >= TITLE_MAX ) {
				e.preventDefault();
				showLimitNotice();
			}
		} );

		el.addEventListener( 'input', function () {
			enforceEditableLimit( el );
		} );
	}

	function getTitleFieldFromNode( node ) {
		if ( ! node ) {
			return null;
		}

		var el = node.nodeType === 1 ? node : node.parentElement;
		if ( ! el ) {
			return null;
		}

		var directInput = el.closest( '#title, input[name="post_title"], textarea.editor-post-title__input, .editor-post-title__input' );
		if ( directInput ) {
			return directInput;
		}

		return el.closest(
			'.editor-visual-editor__post-title-wrapper [contenteditable="true"], ' +
			'.edit-post-visual-editor__post-title-wrapper [contenteditable="true"], ' +
			'[data-type="core/post-title"] [contenteditable="true"], ' +
			'h1.wp-block-post-title[contenteditable="true"], ' +
			'.wp-block-post-title[contenteditable="true"], ' +
			'.wp-block-post-title [contenteditable="true"], ' +
			'[aria-label="Add title"], ' +
			'[aria-label="Dodaj naslov"], ' +
			'[aria-label="Dodajte naslov"]'
		);
	}

	function enforceEditableLimit( el ) {
		var text = el.textContent || '';
		if ( text.length <= TITLE_MAX ) {
			return;
		}

		el.textContent = text.slice( 0, TITLE_MAX );
		moveCaretToEnd( el );
		showLimitNotice();
	}

	function moveCaretToEnd( el ) {
		var doc = el && el.ownerDocument ? el.ownerDocument : document;
		var win = doc.defaultView || window;

		var range = doc.createRange();
		range.selectNodeContents( el );
		range.collapse( false );

		var sel = win.getSelection ? win.getSelection() : null;
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
		var doc = el && el.ownerDocument ? el.ownerDocument : document;
		var win = doc.defaultView || window;
		var sel = win.getSelection ? win.getSelection() : null;
		if ( ! sel || sel.rangeCount === 0 ) {
			return 0;
		}

		var range = sel.getRangeAt( 0 );
		if ( ! el.contains( range.startContainer ) || ! el.contains( range.endContainer ) ) {
			return 0;
		}

		return sel.toString().length;
	}

	function getTextInputSelectionLength( el ) {
		if ( ! el ) {
			return 0;
		}

		var start = typeof el.selectionStart === 'number' ? el.selectionStart : 0;
		var end = typeof el.selectionEnd === 'number' ? el.selectionEnd : start;
		return Math.max( 0, end - start );
	}

	function replaceTextInputSelection( el, text ) {
		if ( ! el ) {
			return;
		}

		var value = el.value || '';
		var start = typeof el.selectionStart === 'number' ? el.selectionStart : value.length;
		var end = typeof el.selectionEnd === 'number' ? el.selectionEnd : start;

		if ( typeof el.setRangeText === 'function' ) {
			el.setRangeText( text, start, end, 'end' );
			return;
		}

		el.value = value.slice( 0, start ) + text + value.slice( end );
		var nextPos = start + text.length;
		if ( typeof el.setSelectionRange === 'function' ) {
			el.setSelectionRange( nextPos, nextPos );
		}
	}

	function dispatchInputEvent( el ) {
		if ( ! el || typeof el.dispatchEvent !== 'function' ) {
			return;
		}

		var doc = el.ownerDocument || document;
		var win = doc.defaultView || window;
		var EventCtor = win.Event || window.Event;
		if ( ! EventCtor ) {
			return;
		}

		el.dispatchEvent( new EventCtor( 'input', { bubbles: true } ) );
	}

	function getClipboardText( event ) {
		if ( event && event.clipboardData && typeof event.clipboardData.getData === 'function' ) {
			return event.clipboardData.getData( 'text/plain' ) || event.clipboardData.getData( 'text' ) || '';
		}

		if ( window.clipboardData && typeof window.clipboardData.getData === 'function' ) {
			return window.clipboardData.getData( 'Text' ) || '';
		}

		return '';
	}

	function insertTextIntoContentEditable( el, text ) {
		if ( ! el || ! text ) {
			return;
		}

		var doc = el.ownerDocument || document;
		var win = doc.defaultView || window;
		var sel = win.getSelection ? win.getSelection() : null;
		if ( ! sel || sel.rangeCount === 0 ) {
			el.textContent = ( el.textContent || '' ) + text;
			moveCaretToEnd( el );
			return;
		}

		var range = sel.getRangeAt( 0 );
		if ( ! el.contains( range.startContainer ) || ! el.contains( range.endContainer ) ) {
			el.textContent = ( el.textContent || '' ) + text;
			moveCaretToEnd( el );
			return;
		}

		range.deleteContents();
		var textNode = doc.createTextNode( text );
		range.insertNode( textNode );
		range.setStartAfter( textNode );
		range.collapse( true );
		sel.removeAllRanges();
		sel.addRange( range );
	}

	function bindEditorStoreGuard() {
		if ( editorStoreGuardStarted ) {
			return;
		}
		editorStoreGuardStarted = true;

		if (
			! window.wp ||
			! window.wp.data ||
			typeof window.wp.data.subscribe !== 'function' ||
			typeof window.wp.data.select !== 'function' ||
			typeof window.wp.data.dispatch !== 'function'
		) {
			return;
		}

		var isApplying = false;

		window.wp.data.subscribe( function () {
			if ( isApplying ) {
				return;
			}

			var editorStore = window.wp.data.select( 'core/editor' );
			if ( ! editorStore || typeof editorStore.getEditedPostAttribute !== 'function' ) {
				return;
			}

			var title = editorStore.getEditedPostAttribute( 'title' );
			if ( 'string' !== typeof title || getEntityAwareTitleLength( title ) <= TITLE_MAX ) {
				return;
			}

			isApplying = true;
			window.wp.data.dispatch( 'core/editor' ).editPost( { title: truncateEntityAwareTitle( title, TITLE_MAX ) } );
			isApplying = false;
			showLimitNotice();
		} );
	}

	function showLimitNotice() {
		var now = Date.now();
		if ( now - lastNoticeAt < NOTICE_COOLDOWN_MS ) {
			return;
		}
		lastNoticeAt = now;

		var message = 'Naslov može imati najviše ' + TITLE_MAX + ' karaktera.';
		var noticesDispatch = null;
		if (
			window.wp &&
			window.wp.data &&
			typeof window.wp.data.dispatch === 'function'
		) {
			noticesDispatch = window.wp.data.dispatch( 'core/notices' );
		}

		if ( noticesDispatch && typeof noticesDispatch.createWarningNotice === 'function' ) {
			noticesDispatch.createWarningNotice(
				message,
				{
					id: 'kompas-title-limit-live',
					isDismissible: true,
				}
			);
			return;
		}

		var toast = document.getElementById( 'kompas-title-limit-toast' );
		if ( ! toast ) {
			toast = document.createElement( 'div' );
			toast.id = 'kompas-title-limit-toast';
			toast.style.cssText = [
				'position:fixed',
				'bottom:20px',
				'right:20px',
				'z-index:100000',
				'max-width:360px',
				'padding:10px 12px',
				'border-radius:4px',
				'background:#cc1818',
				'color:#fff',
				'font-size:13px',
				'line-height:1.35',
				'box-shadow:0 6px 18px rgba(0,0,0,.25)',
				'opacity:0',
				'transition:opacity .15s ease',
			].join( ';' );
			document.body.appendChild( toast );
		}

		toast.textContent = message;
		toast.style.opacity = '1';

		window.clearTimeout( toast._kompasHideTimeout ); // eslint-disable-line no-underscore-dangle
		toast._kompasHideTimeout = window.setTimeout( function () { // eslint-disable-line no-underscore-dangle
			toast.style.opacity = '0';
		}, 1800 );
	}

	function isDocBound( doc ) {
		return boundDocs.indexOf( doc ) !== -1;
	}

	function markDocBound( doc ) {
		boundDocs.push( doc );
	}

	function bindDocumentListeners( rootDoc ) {
		if ( ! rootDoc || ! rootDoc.addEventListener || isDocBound( rootDoc ) ) {
			return;
		}
		markDocBound( rootDoc );

		rootDoc.addEventListener( 'paste', function ( e ) {
			var field = getTitleFieldFromNode( e.target );
			if ( ! field ) {
				return;
			}

			var pastedText = getClipboardText( e );

			if ( isTextInput( field ) ) {
				var currentValue = field.value || '';
				var selectedLen = getTextInputSelectionLength( field );
				var allowed = TITLE_MAX - ( currentValue.length - selectedLen );

				if ( pastedText ) {
					if ( pastedText.length <= allowed ) {
						return;
					}

					e.preventDefault();
					if ( allowed > 0 ) {
						replaceTextInputSelection( field, pastedText.slice( 0, allowed ) );
						dispatchInputEvent( field );
					}
					showLimitNotice();
					return;
				}

				if ( allowed <= 0 ) {
					e.preventDefault();
					showLimitNotice();
				}
				return;
			}

			if ( ! isContentEditable( field ) ) {
				return;
			}

			var text = field.textContent || '';
			var selectedLen = getSelectedLengthInside( field );
			var allowed = TITLE_MAX - ( text.length - selectedLen );

			if ( pastedText ) {
				if ( pastedText.length <= allowed ) {
					return;
				}

				e.preventDefault();
				if ( allowed > 0 ) {
					insertTextIntoContentEditable( field, pastedText.slice( 0, allowed ) );
				}
				enforceEditableLimit( field );
				showLimitNotice();
				return;
			}

			if ( allowed <= 0 ) {
				e.preventDefault();
				showLimitNotice();
			}
		}, true );

		rootDoc.addEventListener( 'beforeinput', function ( e ) {
			var field = getTitleFieldFromNode( e.target );
			if ( ! field ) {
				return;
			}

			if ( isTextInput( field ) ) {
				var currentValue = field.value || '';
				var selectedLen = getTextInputSelectionLength( field );
				var incomingLen = getIncomingLength( e );
				if ( currentValue.length - selectedLen + incomingLen > TITLE_MAX ) {
					e.preventDefault();
					showLimitNotice();
				}
				return;
			}

			if ( ! isContentEditable( field ) ) {
				return;
			}

			var isDelete = e.inputType && (
				e.inputType.indexOf( 'delete' ) === 0 ||
				e.inputType === 'historyUndo' ||
				e.inputType === 'historyRedo'
			);
			if ( isDelete ) {
				return;
			}

			var text = field.textContent || '';
			var selectedLen = getSelectedLengthInside( field );
			var incomingLen = getIncomingLength( e );
			if ( text.length - selectedLen + incomingLen > TITLE_MAX ) {
				e.preventDefault();
				showLimitNotice();
			}
		}, true );

		rootDoc.addEventListener( 'keydown', function ( e ) {
			var field = getTitleFieldFromNode( e.target );
			if ( ! field ) {
				return;
			}

			if ( e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1 ) {
				return;
			}

			if ( isTextInput( field ) ) {
				var currentValue = field.value || '';
				var selectedLen = getTextInputSelectionLength( field );
				if ( currentValue.length - selectedLen >= TITLE_MAX ) {
					e.preventDefault();
					showLimitNotice();
				}
				return;
			}

			if ( ! isContentEditable( field ) ) {
				return;
			}

			var text = field.textContent || '';
			var selectedLen = getSelectedLengthInside( field );
			if ( text.length - selectedLen >= TITLE_MAX ) {
				e.preventDefault();
				showLimitNotice();
			}
		}, true );

		rootDoc.addEventListener( 'input', function ( e ) {
			var field = getTitleFieldFromNode( e.target );
			if ( ! field ) {
				return;
			}

			if ( isTextInput( field ) ) {
				if ( field.value.length > TITLE_MAX ) {
					field.value = field.value.slice( 0, TITLE_MAX );
					showLimitNotice();
				}
				return;
			}

			if ( isContentEditable( field ) ) {
				enforceEditableLimit( field );
			}
		}, true );
	}

	function bindFrameDocuments() {
		document.querySelectorAll( 'iframe' ).forEach( function ( frame ) {
			var frameDoc = null;
			try {
				frameDoc = frame.contentDocument;
			} catch ( err ) { // eslint-disable-line no-unused-vars
				frameDoc = null;
			}

			if ( ! frameDoc ) {
				return;
			}

			bindDocumentListeners( frameDoc );
			bindTitleLimits( frameDoc );
		} );
	}

	function boot() {
		bindEditorStoreGuard();
		bindDocumentListeners( document );

		var rafPending = false;

		function scheduleBind() {
			if ( rafPending ) {
				return;
			}
			rafPending = true;
			window.requestAnimationFrame( function () {
				rafPending = false;
				bindTitleLimits( document );
				bindFrameDocuments();
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
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
