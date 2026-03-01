( function() {
	function closeDropdown( root ) {
		var toggle = root.querySelector( '.kompas-header-tags__toggle' );
		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'false' );
		}
		root.classList.remove( 'is-open' );
	}

	function openDropdown( root ) {
		var toggle = root.querySelector( '.kompas-header-tags__toggle' );
		var search = root.querySelector( '.kompas-header-tags__search' );
		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'true' );
		}
		root.classList.add( 'is-open' );
		if ( search ) {
			search.focus();
		}
	}

	function applyFilter( root ) {
		var search = root.querySelector( '.kompas-header-tags__search' );
		var empty = root.querySelector( '.kompas-header-tags__empty' );
		var links = root.querySelectorAll( '.kompas-header-tags__list .kompas-header-tag-link' );
		var q = search ? String( search.value || '' ).trim().toLowerCase() : '';
		var visible = 0;

		links.forEach( function( link ) {
			var text = String( link.textContent || '' ).toLowerCase();
			var match = q === '' || text.indexOf( q ) !== -1;
			link.style.display = match ? '' : 'none';
			if ( match ) {
				visible++;
			}
		} );

		if ( empty ) {
			empty.hidden = visible !== 0;
		}
	}

	function setupDropdown( root ) {
		var toggle = root.querySelector( '.kompas-header-tags__toggle' );
		var search = root.querySelector( '.kompas-header-tags__search' );
		var links = root.querySelectorAll( '.kompas-header-tags__list .kompas-header-tag-link' );

		if ( ! toggle ) {
			return;
		}

		toggle.addEventListener( 'click', function() {
			if ( root.classList.contains( 'is-open' ) ) {
				closeDropdown( root );
				return;
			}
			openDropdown( root );
		} );

		if ( search ) {
			search.addEventListener( 'input', function() {
				applyFilter( root );
			} );
		}

		links.forEach( function( link ) {
			link.addEventListener( 'click', function() {
				closeDropdown( root );
			} );
		} );
	}

	document.addEventListener( 'click', function( e ) {
		document.querySelectorAll( '[data-kompas-header-tags].is-open' ).forEach( function( root ) {
			if ( root.contains( e.target ) ) {
				return;
			}
			closeDropdown( root );
		} );
	} );

	document.addEventListener( 'keydown', function( e ) {
		if ( e.key !== 'Escape' ) {
			return;
		}
		document.querySelectorAll( '[data-kompas-header-tags].is-open' ).forEach( function( root ) {
			closeDropdown( root );
		} );
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() {
			document.querySelectorAll( '[data-kompas-header-tags]' ).forEach( setupDropdown );
		} );
	} else {
		document.querySelectorAll( '[data-kompas-header-tags]' ).forEach( setupDropdown );
	}
} )();
