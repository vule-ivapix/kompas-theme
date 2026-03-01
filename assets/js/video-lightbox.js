( function () {
	'use strict';

	var overlay = null;

	function buildOverlay() {
		if ( overlay ) return;

		overlay = document.createElement( 'div' );
		overlay.className = 'kompas-video-lightbox';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-label', 'Video lightbox' );
		overlay.innerHTML =
			'<div class="kompas-video-lightbox__container">' +
				'<div class="kompas-video-lightbox__video-panel">' +
					'<video class="kompas-video-lightbox__video" controls playsinline></video>' +
				'</div>' +
				'<div class="kompas-video-lightbox__info-panel">' +
					'<button class="kompas-video-lightbox__close" aria-label="Zatvori">&times;</button>' +
					'<p class="kompas-video-lightbox__date"></p>' +
					'<h2 class="kompas-video-lightbox__title"></h2>' +
					'<p class="kompas-video-lightbox__desc"></p>' +
				'</div>' +
			'</div>';

		document.body.appendChild( overlay );

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) closeOverlay();
		} );

		overlay.querySelector( '.kompas-video-lightbox__close' ).addEventListener( 'click', closeOverlay );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && overlay && overlay.classList.contains( 'is-open' ) ) {
				closeOverlay();
			}
		} );
	}

	function openOverlay( url, title, desc, date ) {
		buildOverlay();

		var video = overlay.querySelector( '.kompas-video-lightbox__video' );
		video.src = url;

		overlay.querySelector( '.kompas-video-lightbox__title' ).textContent = title || '';
		overlay.querySelector( '.kompas-video-lightbox__desc' ).textContent  = desc  || '';
		overlay.querySelector( '.kompas-video-lightbox__date' ).textContent  = date  || '';

		overlay.classList.add( 'is-open' );
		document.body.style.overflow = 'hidden';
	}

	function closeOverlay() {
		if ( ! overlay ) return;
		overlay.classList.remove( 'is-open' );
		document.body.style.overflow = '';

		var video = overlay.querySelector( '.kompas-video-lightbox__video' );
		video.pause();
		video.src = '';
	}

	function init() {
		document.addEventListener( 'click', function ( e ) {
			var card = e.target.closest( '.kompas-video-card' );
			if ( ! card ) return;
			e.preventDefault();
			openOverlay(
				card.dataset.videoUrl || '',
				card.dataset.title    || '',
				card.dataset.desc     || '',
				card.dataset.date     || ''
			);
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
