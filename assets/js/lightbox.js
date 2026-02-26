/**
 * Kompas Lightbox
 *
 * Opens images in a fullscreen overlay when clicked.
 * Works for the featured image and all content images on single posts.
 */
( function () {
	'use strict';

	if ( ! document.body.classList.contains( 'single' ) ) {
		return;
	}

	// Build lightbox DOM.
	var lb = document.createElement( 'div' );
	lb.className = 'kompas-lightbox';
	lb.setAttribute( 'role', 'dialog' );
	lb.setAttribute( 'aria-modal', 'true' );
	lb.setAttribute( 'aria-label', 'Pregled fotografije' );
	lb.innerHTML =
		'<div class="kompas-lightbox__wrap">' +
			'<button class="kompas-lightbox__close" aria-label="Zatvori">&times;</button>' +
			'<img class="kompas-lightbox__img" src="" alt="" />' +
		'</div>';
	document.body.appendChild( lb );

	var lbWrap  = lb.querySelector( '.kompas-lightbox__wrap' );
	var lbImg   = lb.querySelector( '.kompas-lightbox__img' );
	var lbClose = lb.querySelector( '.kompas-lightbox__close' );

	function open( src, alt ) {
		lbImg.src = src;
		lbImg.alt = alt || '';
		lb.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
		lbClose.focus();
	}

	function close() {
		lb.classList.remove( 'is-open' );
		document.body.style.overflow = '';
		// Clear src after transition ends.
		setTimeout( function () {
			lbImg.src = '';
		}, 300 );
	}

	// Close on backdrop click (not on the image itself).
	lb.addEventListener( 'click', function ( e ) {
		if ( e.target === lb || e.target === lbWrap ) {
			close();
		}
	} );

	lbClose.addEventListener( 'click', close );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && lb.classList.contains( 'is-open' ) ) {
			close();
		}
	} );

	// Try to get the full-size URL by stripping WP size suffix (e.g. -800x500).
	function getFullUrl( url ) {
		return url.replace( /-\d+x\d+(\.[a-z]+(?:\?.*)?$)/i, '$1' );
	}

	function isInRelatedPosts( img ) {
		return !! img.closest( '.kompas-related-posts-query' );
	}

	function bindImage( img ) {
		if ( ! img || isInRelatedPosts( img ) ) {
			return;
		}

		var anchor = img.closest( 'a' );
		var fullSrc = img.getAttribute( 'data-full' ) ||
			( anchor ? anchor.href : getFullUrl( img.src ) );

		img.style.cursor = 'zoom-in';

		var handler = function ( e ) {
			e.preventDefault();
			open( fullSrc || img.src, img.alt );
		};

		if ( anchor ) {
			anchor.addEventListener( 'click', handler );
		} else {
			img.addEventListener( 'click', handler );
		}
	}

	// Featured image on current single post only (exclude related/query loop items).
	var featured = Array.prototype.find.call(
		document.querySelectorAll( '.wp-block-post-featured-image img' ),
		function ( img ) {
			return ! isInRelatedPosts( img ) && ! img.closest( '.wp-block-post-template' );
		}
	);
	bindImage( featured );

	// All images inside the current post content.
	document.querySelectorAll( '.wp-block-post-content img' ).forEach( bindImage );
}() );
