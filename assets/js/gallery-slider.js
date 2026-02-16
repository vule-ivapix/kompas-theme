( function() {
	document.querySelectorAll( '.kompas-gallery-slider' ).forEach( function( slider ) {
		var slides  = slider.querySelectorAll( '.kompas-gallery-slider__slide' );
		var total   = slides.length;
		var current = 0;

		var btnPrev  = slider.querySelector( '.kompas-gallery-slider__arrow--prev' );
		var btnNext  = slider.querySelector( '.kompas-gallery-slider__arrow--next' );
		var fracCur  = slider.querySelector( '.kompas-gallery-slider__current' );
		var creditEl = slider.querySelector( '.kompas-gallery-slider__credit' );

		var credits = [];
		try {
			credits = JSON.parse( creditEl.getAttribute( 'data-credits' ) || '[]' );
		} catch ( e ) {
			credits = [];
		}

		function show( index ) {
			slides[ current ].style.display = 'none';
			current = ( index + total ) % total;
			slides[ current ].style.display = '';
			fracCur.textContent = current + 1;

			// Update credit.
			var c = credits[ current ] || '';
			creditEl.textContent = c ? 'Фото: ' + c : '';
		}

		btnPrev.addEventListener( 'click', function() { show( current - 1 ); } );
		btnNext.addEventListener( 'click', function() { show( current + 1 ); } );
	} );
} )();
