( function() {
	// Serbian Cyrillic → Latin mapping.
	var cyrToLat = {
		'А':'A','Б':'B','В':'V','Г':'G','Д':'D','Ђ':'Đ','Е':'E','Ж':'Ž','З':'Z','И':'I',
		'Ј':'J','К':'K','Л':'L','Љ':'Lj','М':'M','Н':'N','Њ':'Nj','О':'O','П':'P','Р':'R',
		'С':'S','Т':'T','Ћ':'Ć','У':'U','Ф':'F','Х':'H','Ц':'C','Ч':'Č','Џ':'Dž','Ш':'Š',
		'а':'a','б':'b','в':'v','г':'g','д':'d','ђ':'đ','е':'e','ж':'ž','з':'z','и':'i',
		'ј':'j','к':'k','л':'l','љ':'lj','м':'m','н':'n','њ':'nj','о':'o','п':'p','р':'r',
		'с':'s','т':'t','ћ':'ć','у':'u','ф':'f','х':'h','ц':'c','ч':'č','џ':'dž','ш':'š'
	};

	// Latin → Cyrillic mapping.
	var latToCyr = {};
	Object.keys( cyrToLat ).forEach( function( k ) {
		latToCyr[ cyrToLat[ k ] ] = k;
	} );

	// Digraphs that must be checked first (Lj, Nj, Dž and lowercase).
	var latDigraphs = [ 'Lj', 'LJ', 'lj', 'Nj', 'NJ', 'nj', 'Dž', 'DŽ', 'dž' ];
	var latDigraphMap = {
		'Lj':'Љ', 'LJ':'Љ', 'lj':'љ',
		'Nj':'Њ', 'NJ':'Њ', 'nj':'њ',
		'Dž':'Џ', 'DŽ':'Џ', 'dž':'џ'
	};

	function cyrillicToLatin( text ) {
		// Handle digraphs first (Љ, Њ, Џ).
		var result = '';
		for ( var i = 0; i < text.length; i++ ) {
			var ch = text[ i ];
			result += cyrToLat[ ch ] || ch;
		}
		return result;
	}

	function latinToCyrillic( text ) {
		var result = '';
		var i = 0;
		while ( i < text.length ) {
			var found = false;
			// Check 2-char digraphs first.
			if ( i + 1 < text.length ) {
				var two = text[ i ] + text[ i + 1 ];
				if ( latDigraphMap[ two ] ) {
					result += latDigraphMap[ two ];
					i += 2;
					found = true;
				}
			}
			if ( ! found ) {
				var ch = text[ i ];
				result += latToCyr[ ch ] || ch;
				i++;
			}
		}
		return result;
	}

	// Walk text nodes and convert.
	function walkTextNodes( node, converter ) {
		if ( node.nodeType === 3 ) {
			// Text node.
			var converted = converter( node.nodeValue );
			if ( converted !== node.nodeValue ) {
				node.nodeValue = converted;
			}
			return;
		}

		// Skip script-like/contentless nodes.
		var tag = node.tagName;
		if ( tag && /^(SCRIPT|STYLE|SVG|NOSCRIPT)$/i.test( tag ) ) {
			return;
		}

		// Do not convert user-entered form values in generic walk.
		if ( tag && /^(TEXTAREA|INPUT)$/i.test( tag ) ) {
			if ( node.nodeType === 1 ) {
				[ 'placeholder', 'title', 'alt' ].forEach( function( attr ) {
					if ( node.hasAttribute( attr ) ) {
						node.setAttribute( attr, converter( node.getAttribute( attr ) ) );
					}
				} );
			}
			return;
		}

		var children = node.childNodes;
		for ( var i = 0; i < children.length; i++ ) {
			walkTextNodes( children[ i ], converter );
		}

		// Also convert placeholder, title, alt attributes.
		if ( node.nodeType === 1 ) {
			[ 'placeholder', 'title', 'alt' ].forEach( function( attr ) {
				if ( node.hasAttribute( attr ) ) {
					node.setAttribute( attr, converter( node.getAttribute( attr ) ) );
				}
			} );
		}
	}

	function syncSearchInputsForScript( script, convertValues ) {
		var converter = script === 'lat' ? cyrillicToLatin : latinToCyrillic;
		var lang      = script === 'lat' ? 'sr-Latn' : 'sr-Cyrl';

		document.querySelectorAll( '.kompas-search .wp-block-search__input, .kompas-mobile-search__input' ).forEach( function( input ) {
			input.setAttribute( 'lang', lang );

			if ( input.hasAttribute( 'placeholder' ) ) {
				input.setAttribute( 'placeholder', converter( input.getAttribute( 'placeholder' ) ) );
			}

			if ( convertValues && input.value ) {
				input.value = converter( input.value );
			}
		} );
	}

	function bindSearchInputHandlers() {
		document.querySelectorAll( '.kompas-search .wp-block-search__input, .kompas-mobile-search__input' ).forEach( function( input ) {
			if ( input.dataset.kompasScriptBound === '1' ) {
				return;
			}
			input.dataset.kompasScriptBound = '1';

			input.addEventListener( 'input', function() {
				if ( currentScript !== 'cyr' ) {
					return;
				}

				var previous = input.value;
				var converted = latinToCyrillic( previous );
				if ( converted === previous ) {
					return;
				}

				var cursor = input.selectionStart;
				var nextCursor = null;
				if ( typeof cursor === 'number' ) {
					nextCursor = latinToCyrillic( previous.slice( 0, cursor ) ).length;
				}

				input.value = converted;
				if ( nextCursor !== null && input === document.activeElement ) {
					try {
						input.setSelectionRange( nextCursor, nextCursor );
					} catch ( e ) {}
				}
			} );

			var form = input.closest( 'form' );
			if ( form && form.dataset.kompasSearchBound !== '1' ) {
				form.dataset.kompasSearchBound = '1';
				form.addEventListener( 'submit', function() {
					var field = form.querySelector( '.wp-block-search__input, input[name="s"], input[type="search"]' );
					if ( ! field || ! field.value ) {
						return;
					}

					if ( currentScript === 'lat' ) {
						field.value = cyrillicToLatin( field.value );
					} else {
						field.value = latinToCyrillic( field.value );
					}
				} );
			}
		} );
	}

	function resolveInitialScript() {
		if ( document.querySelector( '.kompas-header .kompas-search .wp-block-search__input' ) ) {
			return 'cyr';
		}
		return currentScript;
	}

	var currentScript = 'cyr'; // Default.
	try {
		var saved = localStorage.getItem( 'kompas_script' );
		if ( saved === 'lat' || saved === 'cyr' ) {
			currentScript = saved;
		}
	} catch ( e ) {}

	function applyScript( script ) {
		currentScript = script;
		try { localStorage.setItem( 'kompas_script', script ); } catch ( e ) {}

		if ( script === 'lat' ) {
			walkTextNodes( document.body, cyrillicToLatin );
		} else {
			walkTextNodes( document.body, latinToCyrillic );
		}

		// Update toggle button appearance.
		document.querySelectorAll( '.kompas-script-toggle' ).forEach( function( el ) {
			if ( script === 'lat' ) {
				el.innerHTML = 'Ćir/<strong>LAT</strong>';
			} else {
				el.innerHTML = '<strong>ЋИР</strong>/Лат';
			}
		} );

		bindSearchInputHandlers();
		syncSearchInputsForScript( script, true );
	}

	// Bind click on toggle(s).
	document.addEventListener( 'click', function( e ) {
		var toggle = e.target.closest( '.kompas-script-toggle' );
		if ( ! toggle ) return;
		e.preventDefault();
		applyScript( currentScript === 'cyr' ? 'lat' : 'cyr' );
	} );

	// Start with saved script, otherwise Cyrillic default.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() {
			applyScript( resolveInitialScript() );
		} );
	} else {
		applyScript( resolveInitialScript() );
	}
} )();
