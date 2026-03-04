( function() {
	var scrollY = 0;

	function lockScroll() {
		scrollY = window.scrollY;
		document.body.style.top = '-' + scrollY + 'px';
		document.body.classList.add( 'kompas-mobile-menu-open' );
	}

	function unlockScroll() {
		document.body.classList.remove( 'kompas-mobile-menu-open' );
		document.body.style.top = '';
		window.scrollTo( 0, scrollY );
	}

	function openOverlay( nav ) {
		var overlay   = nav.querySelector( '.kompas-mobile-overlay' );
		var hamburger = nav.querySelector( '.kompas-mobile-hamburger' );

		hamburger.setAttribute( 'aria-expanded', 'true' );
		hamburger.classList.add( 'is-open' );
		nav.classList.add( 'is-open' );
		overlay.classList.add( 'is-open' );
		overlay.setAttribute( 'aria-hidden', 'false' );
		lockScroll();
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

			if ( open ) {
				unlockScroll();
			} else {
				lockScroll();
			}
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
