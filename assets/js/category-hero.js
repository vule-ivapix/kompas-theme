( function() {
	var wrap       = document.getElementById( 'kompas-category-hero-wrap' );
	if ( ! wrap ) return;

	var input      = document.getElementById( 'kompas-category-hero-ids' );
	var searchEl   = document.getElementById( 'kompas-category-hero-search' );
	var resultEl   = document.getElementById( 'kompas-category-hero-results' );
	var listEl     = document.getElementById( 'kompas-category-hero-list' );
	var maxPosts   = 7;
	var searchTimer;

	function getIds() {
		var val = input.value.trim();
		return val ? val.split( ',' ).map( Number ).filter( Boolean ) : [];
	}

	function setIds( ids ) {
		input.value = ids.join( ',' );
	}

	function renderList( ids ) {
		listEl.innerHTML = '';
		if ( ! ids.length ) return;

		var url = '/wp-json/wp/v2/posts?include=' + ids.join( ',' ) + '&per_page=' + ids.length + '&_fields=id,title';
		fetch( url, { credentials: 'same-origin', headers: { 'X-WP-Nonce': kompasHeroData.nonce } } )
			.then( function( r ) { return r.json(); } )
			.then( function( posts ) {
				listEl.innerHTML = '';
				ids.forEach( function( id, i ) {
					var post = posts.find( function( p ) { return p.id === id; } );
					if ( ! post ) return;

					var row = document.createElement( 'div' );
					row.style.cssText = 'display:flex;align-items:center;gap:6px;padding:6px 8px;margin-bottom:4px;background:#f0f0f0;border-radius:4px;font-size:13px';

					var num = document.createElement( 'span' );
					num.style.cssText = 'color:#757575;font-size:11px;min-width:20px';
					num.textContent = ( i + 1 ) + '.';

					var title = document.createElement( 'span' );
					title.style.cssText = 'flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
					title.textContent = post.title.rendered;

					var upBtn = document.createElement( 'button' );
					upBtn.type = 'button';
					upBtn.className = 'button button-small';
					upBtn.textContent = '\u2191';
					upBtn.disabled = i === 0;
					upBtn.addEventListener( 'click', function() { movePost( i, -1 ); } );

					var downBtn = document.createElement( 'button' );
					downBtn.type = 'button';
					downBtn.className = 'button button-small';
					downBtn.textContent = '\u2193';
					downBtn.disabled = i === ids.length - 1;
					downBtn.addEventListener( 'click', function() { movePost( i, 1 ); } );

					var removeBtn = document.createElement( 'button' );
					removeBtn.type = 'button';
					removeBtn.className = 'button button-small';
					removeBtn.textContent = '\u00d7';
					removeBtn.style.color = '#d63638';
					removeBtn.addEventListener( 'click', function() { removePost( id ); } );

					row.appendChild( num );
					row.appendChild( title );
					row.appendChild( upBtn );
					row.appendChild( downBtn );
					row.appendChild( removeBtn );
					listEl.appendChild( row );
				} );
			} );
	}

	function addPost( id ) {
		var ids = getIds();
		if ( ids.length >= maxPosts || ids.indexOf( id ) !== -1 ) return;
		ids.push( id );
		setIds( ids );
		renderList( ids );
		searchEl.value = '';
		resultEl.innerHTML = '';
	}

	function removePost( id ) {
		var ids = getIds().filter( function( x ) { return x !== id; } );
		setIds( ids );
		renderList( ids );
	}

	function movePost( idx, dir ) {
		var ids = getIds();
		var target = idx + dir;
		if ( target < 0 || target >= ids.length ) return;
		var tmp = ids[ idx ];
		ids[ idx ] = ids[ target ];
		ids[ target ] = tmp;
		setIds( ids );
		renderList( ids );
	}

	searchEl.addEventListener( 'input', function() {
		clearTimeout( searchTimer );
		var q = searchEl.value.trim();
		if ( q.length < 2 ) { resultEl.innerHTML = ''; return; }

		searchTimer = setTimeout( function() {
			var url = '/wp-json/wp/v2/posts?search=' + encodeURIComponent( q ) + '&per_page=10&_fields=id,title';
			fetch( url, { credentials: 'same-origin', headers: { 'X-WP-Nonce': kompasHeroData.nonce } } )
				.then( function( r ) { return r.json(); } )
				.then( function( posts ) {
					resultEl.innerHTML = '';
					var ids = getIds();
					posts.forEach( function( post ) {
						if ( ids.indexOf( post.id ) !== -1 ) return;
						var btn = document.createElement( 'button' );
						btn.type = 'button';
						btn.className = 'button';
						btn.style.cssText = 'display:block;width:100%;text-align:left;padding:6px 10px;margin-bottom:2px;font-size:13px';
						btn.textContent = '#' + post.id + ' ' + post.title.rendered;
						btn.addEventListener( 'click', function() { addPost( post.id ); } );
						resultEl.appendChild( btn );
					} );
				} );
		}, 300 );
	} );

	// Initial render.
	renderList( getIds() );
} )();
