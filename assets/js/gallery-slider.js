( function() {
	'use strict';

	/* ── Lightbox ────────────────────────────────────────────────── */

	var lb, lbImg, lbCaption, lbCounter, lbClose, lbPrev, lbNext;
	var lbGallery = [];
	var lbIndex   = 0;

	function createLightbox() {
		if ( document.getElementById( 'kompas-slider-lb' ) ) {
			lb        = document.getElementById( 'kompas-slider-lb' );
			lbImg     = lb.querySelector( '.kompas-gallery-lb__img' );
			lbCaption = lb.querySelector( '.kompas-gallery-lb__caption' );
			lbCounter = lb.querySelector( '.kompas-gallery-lb__counter' );
			lbClose   = lb.querySelector( '.kompas-gallery-lb__close' );
			lbPrev    = lb.querySelector( '.kompas-gallery-lb__prev' );
			lbNext    = lb.querySelector( '.kompas-gallery-lb__next' );
			return;
		}

		lb = document.createElement( 'div' );
		lb.id = 'kompas-slider-lb';
		lb.setAttribute( 'role', 'dialog' );
		lb.setAttribute( 'aria-modal', 'true' );

		var wrap = document.createElement( 'div' );
		wrap.className = 'kompas-gallery-lb__wrap';

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

		wrap.appendChild( lbClose );
		wrap.appendChild( lbPrev );
		wrap.appendChild( lbImg );
		wrap.appendChild( lbNext );
		wrap.appendChild( lbCaption );
		wrap.appendChild( lbCounter );
		lb.appendChild( wrap );
		document.body.appendChild( lb );

		lbClose.addEventListener( 'click', closeLightbox );
		lbPrev.addEventListener( 'click', function() { lbNavigate( -1 ); } );
		lbNext.addEventListener( 'click', function() { lbNavigate(  1 ); } );

		lb.addEventListener( 'click', function( e ) {
			if ( e.target === lb ) closeLightbox();
		} );
	}

	function openLightbox( gallery, index ) {
		lbGallery = gallery;
		lbIndex   = index;
		lbShow();
		lb.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
		lbImg.focus();
	}

	function closeLightbox() {
		lb.classList.remove( 'is-open' );
		document.body.style.overflow = '';
	}

	function lbNavigate( dir ) {
		lbIndex = ( lbIndex + dir + lbGallery.length ) % lbGallery.length;
		lbShow();
	}

	function lbShow() {
		var item = lbGallery[ lbIndex ];
		lbImg.src             = item.full;
		lbImg.alt             = item.alt;
		lbCaption.textContent = item.credit ? 'Фото: ' + item.credit : '';
		lbCounter.textContent = ( lbIndex + 1 ) + ' / ' + lbGallery.length;
		lbPrev.style.display  = lbGallery.length > 1 ? '' : 'none';
		lbNext.style.display  = lbGallery.length > 1 ? '' : 'none';
	}

	/* ── Slider init ─────────────────────────────────────────────── */

	function init() {
		createLightbox();

		document.querySelectorAll( '.kompas-gallery-slider' ).forEach( function( slider ) {
			var slides   = Array.prototype.slice.call( slider.querySelectorAll( '.kompas-gallery-slider__slide' ) );
			var thumbBtns = Array.prototype.slice.call( slider.querySelectorAll( '.kompas-gallery-slider__thumb' ) );
			var btnPrev  = slider.querySelector( '.kompas-gallery-slider__arrow--prev' );
			var btnNext  = slider.querySelector( '.kompas-gallery-slider__arrow--next' );
			var fracCur  = slider.querySelector( '.kompas-gallery-slider__current' );
			var creditEl = slider.querySelector( '.kompas-gallery-slider__credit' );
			var total    = slides.length;
			var current  = 0;

			// Build gallery array for lightbox.
			var gallery = slides.map( function( slide ) {
				return {
					full:   slide.dataset.full   || slide.querySelector( 'img' ).src,
					alt:    slide.querySelector( 'img' ).alt,
					credit: slide.dataset.credit || '',
				};
			} );

			function goTo( index ) {
				slides[ current ].style.display = 'none';
				if ( thumbBtns[ current ] ) thumbBtns[ current ].classList.remove( 'is-active' );

				current = ( index + total ) % total;

				slides[ current ].style.display = '';
				if ( thumbBtns[ current ] ) thumbBtns[ current ].classList.add( 'is-active' );
				if ( fracCur ) fracCur.textContent = current + 1;

				var c = gallery[ current ].credit;
				if ( creditEl ) creditEl.textContent = c ? 'Фото: ' + c : '';
			}

			if ( btnPrev ) btnPrev.addEventListener( 'click', function() { goTo( current - 1 ); } );
			if ( btnNext ) btnNext.addEventListener( 'click', function() { goTo( current + 1 ); } );

			// Thumbnail clicks.
			thumbBtns.forEach( function( btn ) {
				btn.addEventListener( 'click', function() {
					goTo( parseInt( btn.dataset.index, 10 ) );
				} );
			} );

			// Click on slide image → open lightbox.
			slides.forEach( function( slide, i ) {
				slide.querySelector( 'img' ).addEventListener( 'click', function() {
					openLightbox( gallery, i );
				} );
			} );

			// Init credit text.
			if ( creditEl ) {
				var c0 = gallery[0].credit;
				creditEl.textContent = c0 ? 'Фото: ' + c0 : '';
			}
		} );

		document.addEventListener( 'keydown', function( e ) {
			if ( ! lb || ! lb.classList.contains( 'is-open' ) ) return;
			if ( e.key === 'Escape' )    closeLightbox();
			if ( e.key === 'ArrowLeft' ) lbNavigate( -1 );
			if ( e.key === 'ArrowRight' ) lbNavigate( 1 );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
