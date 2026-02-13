( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		var sections = document.querySelectorAll( '.kompas-tabs-section' );

		sections.forEach( function ( section ) {
			var buttons = section.querySelectorAll( '.kompas-tab-btn' );
			var panels = section.querySelectorAll( '.kompas-tab-panel' );

			buttons.forEach( function ( btn ) {
				btn.addEventListener( 'click', function () {
					var target = btn.getAttribute( 'data-tab' );

					// Update buttons.
					buttons.forEach( function ( b ) {
						b.classList.remove( 'is-active' );
						b.style.borderBottomColor = 'transparent';
						b.style.color = 'var(--wp--preset--color--muted)';
					} );
					btn.classList.add( 'is-active' );
					btn.style.borderBottomColor = 'var(--wp--preset--color--primary)';
					btn.style.color = 'var(--wp--preset--color--dark)';

					// Update panels.
					panels.forEach( function ( panel ) {
						if ( panel.getAttribute( 'data-panel' ) === target ) {
							panel.style.display = '';
							panel.classList.add( 'is-active' );
						} else {
							panel.style.display = 'none';
							panel.classList.remove( 'is-active' );
						}
					} );
				} );
			} );
		} );
	} );
} )();
