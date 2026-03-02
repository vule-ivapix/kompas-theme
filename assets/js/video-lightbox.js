( function () {
	'use strict';

	var overlay = null;

	/**
	 * Convert any YouTube URL to an embed URL with autoplay.
	 * Handles: watch?v=, youtu.be/, embed/
	 */
	function youtubeEmbedUrl( url ) {
		var match = url.match( /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/ );
		if ( ! match ) return '';
		return 'https://www.youtube.com/embed/' + match[ 1 ] + '?autoplay=1&rel=0';
	}

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
					'<iframe class="kompas-video-lightbox__video" frameborder="0" allowfullscreen ' +
						'allow="autoplay; encrypted-media; picture-in-picture"></iframe>' +
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

		var embedUrl = youtubeEmbedUrl( url );
		var iframe   = overlay.querySelector( '.kompas-video-lightbox__video' );
		iframe.src   = embedUrl;

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

		// Clear src to stop YouTube playback.
		var iframe = overlay.querySelector( '.kompas-video-lightbox__video' );
		iframe.src = '';
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
