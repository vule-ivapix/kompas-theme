( function() {
	'use strict';

	var lb, lbImg, lbCaption, lbCounter, lbClose, lbPrev, lbNext;
	var currentGallery = [];
	var currentIndex   = 0;

	function closeOtherLightboxes() {
		var other = document.getElementById( 'kompas-slider-lb' );
		if ( other ) {
			other.classList.remove( 'is-open' );
		}
	}

	function createLightbox() {
		if ( document.getElementById( 'kompas-gallery-lb' ) ) return;

		lb = document.createElement( 'div' );
		lb.id            = 'kompas-gallery-lb';
		lb.setAttribute( 'role', 'dialog' );
		lb.setAttribute( 'aria-modal', 'true' );

		var wrap = document.createElement( 'div' );
		var mediaWrap = document.createElement( 'div' );
		wrap.className = 'kompas-gallery-lb__wrap';
		mediaWrap.className = 'kompas-gallery-lb__media';

		lbClose = document.createElement( 'button' );
		lbClose.className   = 'kompas-gallery-lb__close';
		lbClose.type        = 'button';
		lbClose.textContent = '\u00d7';
		lbClose.setAttribute( 'aria-label', 'Затвори' );

		lbPrev = document.createElement( 'button' );
		lbPrev.className   = 'kompas-gallery-lb__prev';
		lbPrev.type        = 'button';
		lbPrev.textContent = '\u2039';
		lbPrev.setAttribute( 'aria-label', 'Претходна' );

		lbImg = document.createElement( 'img' );
		lbImg.className = 'kompas-gallery-lb__img';

		lbNext = document.createElement( 'button' );
		lbNext.className   = 'kompas-gallery-lb__next';
		lbNext.type        = 'button';
		lbNext.textContent = '\u203a';
		lbNext.setAttribute( 'aria-label', 'Следећа' );

		lbCaption = document.createElement( 'p' );
		lbCaption.className = 'kompas-gallery-lb__caption';

		lbCounter = document.createElement( 'span' );
		lbCounter.className = 'kompas-gallery-lb__counter';

		mediaWrap.appendChild( lbImg );
		mediaWrap.appendChild( lbCaption );

		wrap.appendChild( lbClose );
		wrap.appendChild( lbPrev );
		wrap.appendChild( mediaWrap );
		wrap.appendChild( lbNext );
		wrap.appendChild( lbCounter );
		lb.appendChild( wrap );
		document.body.appendChild( lb );

		lbClose.addEventListener( 'click', closeLightbox );
		lbPrev.addEventListener( 'click', function() { navigate( -1 ); } );
		lbNext.addEventListener( 'click', function() { navigate(  1 ); } );

		// Click on backdrop (outside wrap) closes lightbox.
		lb.addEventListener( 'click', function( e ) {
			if ( e.target === lb ) {
				closeLightbox();
			}
		} );
	}

	function openAt( gallery, index ) {
		closeOtherLightboxes();
		currentGallery = gallery;
		currentIndex   = index;
		showCurrent();
		lb.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
		lbImg.focus();
	}

	function closeLightbox() {
		lb.classList.remove( 'is-open' );
		document.body.style.overflow = '';
	}

	function navigate( dir ) {
		currentIndex = ( currentIndex + dir + currentGallery.length ) % currentGallery.length;
		showCurrent();
	}

	function showCurrent() {
		var item = currentGallery[ currentIndex ];
		lbImg.src     = item.full;
		lbImg.alt     = item.alt;
		lbCaption.textContent = item.source ? 'Фото: ' + item.source : '';
		lbCaption.style.display = item.source ? '' : 'none';
		lbCounter.textContent = ( currentIndex + 1 ) + ' / ' + currentGallery.length;

		lbPrev.style.display = currentGallery.length > 1 ? '' : 'none';
		lbNext.style.display = currentGallery.length > 1 ? '' : 'none';
	}

	function init() {
		createLightbox();

		var galleries = document.querySelectorAll( '.kompas-photo-gallery' );
		galleries.forEach( function( galleryEl ) {
			var thumbs  = galleryEl.querySelectorAll( '.kompas-photo-gallery__thumb' );
			var gallery = [];

			thumbs.forEach( function( btn ) {
				gallery.push( {
					full:   btn.dataset.full   || '',
					source: btn.dataset.source || '',
					alt:    btn.dataset.alt    || '',
				} );
			} );

			thumbs.forEach( function( btn, i ) {
				btn.addEventListener( 'click', function() {
					openAt( gallery, i );
				} );
			} );
		} );

		document.addEventListener( 'keydown', function( e ) {
			if ( ! lb || ! lb.classList.contains( 'is-open' ) ) return;
			if ( e.key === 'Escape' )     closeLightbox();
			if ( e.key === 'ArrowLeft' )  navigate( -1 );
			if ( e.key === 'ArrowRight' ) navigate(  1 );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
