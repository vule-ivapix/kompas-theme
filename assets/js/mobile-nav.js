( function() {
	document.addEventListener( 'click', function( e ) {
		var btn = e.target.closest( '.kompas-mobile-hamburger' );
		if ( ! btn ) return;

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
	} );
} )();
