( function() {
	function openOverlay( nav ) {
		var overlay   = nav.querySelector( '.kompas-mobile-overlay' );
		var hamburger = nav.querySelector( '.kompas-mobile-hamburger' );

		hamburger.setAttribute( 'aria-expanded', 'true' );
		hamburger.classList.add( 'is-open' );
		nav.classList.add( 'is-open' );
		overlay.classList.add( 'is-open' );
		overlay.setAttribute( 'aria-hidden', 'false' );
		document.body.classList.add( 'kompas-mobile-menu-open' );
	}

	document.addEventListener( 'click', function( e ) {
		// Hamburger toggle.
		var btn = e.target.closest( '.kompas-mobile-hamburger' );
		if ( btn ) {
			e.preventDefault();
			var nav     = btn.closest( '.kompas-mobile-nav' );
			var overlay = nav.querySelector( '.kompas-mobile-overlay' );
			var open    = btn.getAttribute( 'aria-expanded' ) === 'true';

			btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			btn.classList.toggle( 'is-open', ! open );
			nav.classList.toggle( 'is-open', ! open );
			overlay.classList.toggle( 'is-open', ! open );
			overlay.setAttribute( 'aria-hidden', open ? 'true' : 'false' );
			document.body.classList.toggle( 'kompas-mobile-menu-open', ! open );
			return;
		}

		// Search icon — open overlay and focus input.
		var searchBtn = e.target.closest( '.kompas-mobile-bar__search' );
		if ( searchBtn ) {
			e.preventDefault();
			var nav = searchBtn.closest( '.kompas-mobile-nav' );
			openOverlay( nav );
			var input = nav.querySelector( '.kompas-mobile-search__input' );
			if ( input ) {
				setTimeout( function() { input.focus(); }, 50 );
			}
		}
	} );
} )();
