( function () {
	var searchInput  = document.getElementById( 'kompas-author-search-input' );
	var hiddenInput  = document.getElementById( 'kompas-author-id-input' );
	var resultsDiv   = document.getElementById( 'kompas-author-search-results' );
	var selectedDiv  = document.getElementById( 'kompas-author-selected' );
	var selectedName = document.getElementById( 'kompas-author-selected-name' );
	var selectedPhoto = document.getElementById( 'kompas-author-selected-photo' );
	var removeBtn    = document.getElementById( 'kompas-author-remove' );

	if ( ! searchInput || ! hiddenInput ) {
		return;
	}

	var timeout;

	searchInput.addEventListener( 'input', function () {
		clearTimeout( timeout );
		var query = searchInput.value.trim();
		if ( query.length < 2 ) {
			resultsDiv.innerHTML = '';
			resultsDiv.style.display = 'none';
			return;
		}
		timeout = setTimeout( function () {
			fetch(
				kompasAuthorMeta.restUrl + '?search=' + encodeURIComponent( query ) + '&per_page=10&_fields=id,title,featured_image_url',
				{
					headers: { 'X-WP-Nonce': kompasAuthorMeta.nonce },
				}
			)
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					resultsDiv.innerHTML = '';
					if ( ! Array.isArray( data ) || ! data.length ) {
						resultsDiv.innerHTML = '<p style="padding:6px 8px;color:#888;margin:0;font-size:12px">Нема резултата</p>';
						resultsDiv.style.display = 'block';
						return;
					}
					resultsDiv.style.display = 'block';
					data.forEach( function ( autor ) {
						var item = document.createElement( 'div' );
						item.style.cssText = 'padding:6px 8px;cursor:pointer;display:flex;align-items:center;gap:8px;border-bottom:1px solid #eee;font-size:12px';
						item.addEventListener( 'mouseenter', function () { item.style.background = '#f0f0f0'; } );
						item.addEventListener( 'mouseleave', function () { item.style.background = ''; } );

						if ( autor.featured_image_url ) {
							var img = document.createElement( 'img' );
							img.src = autor.featured_image_url;
							img.style.cssText = 'width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0';
							item.appendChild( img );
						}

						var nameSpan = document.createElement( 'span' );
						nameSpan.textContent = autor.title.rendered;
						item.appendChild( nameSpan );

						item.addEventListener( 'click', function () {
							selectAutor( autor );
						} );

						resultsDiv.appendChild( item );
					} );
				} )
				.catch( function () {
					resultsDiv.innerHTML = '';
					resultsDiv.style.display = 'none';
				} );
		}, 300 );
	} );

	function selectAutor( autor ) {
		hiddenInput.value = autor.id;
		resultsDiv.innerHTML = '';
		resultsDiv.style.display = 'none';
		searchInput.value = '';
		searchInput.style.display = 'none';

		if ( selectedName ) {
			selectedName.textContent = autor.title.rendered;
		}
		if ( selectedPhoto ) {
			if ( autor.featured_image_url ) {
				selectedPhoto.src = autor.featured_image_url;
				selectedPhoto.style.display = 'inline-block';
			} else {
				selectedPhoto.src = '';
				selectedPhoto.style.display = 'none';
			}
		}
		if ( selectedDiv ) {
			selectedDiv.style.display = 'block';
		}
		if ( removeBtn ) {
			removeBtn.style.display = 'inline-block';
		}
	}

	if ( removeBtn ) {
		removeBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			hiddenInput.value = '';
			if ( selectedDiv ) {
				selectedDiv.style.display = 'none';
			}
			if ( selectedName ) {
				selectedName.textContent = '';
			}
			if ( selectedPhoto ) {
				selectedPhoto.src = '';
				selectedPhoto.style.display = 'none';
			}
			removeBtn.style.display = 'none';
			searchInput.style.display = '';
			searchInput.focus();
		} );
	}

	// Close results when clicking outside.
	document.addEventListener( 'click', function ( e ) {
		if ( ! resultsDiv.contains( e.target ) && e.target !== searchInput ) {
			resultsDiv.innerHTML = '';
			resultsDiv.style.display = 'none';
		}
	} );
} )();
