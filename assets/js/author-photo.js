( function() {
	var frame;
	var selectBtn = document.getElementById( 'kompas-author-photo-select' );
	var removeBtn = document.getElementById( 'kompas-author-photo-remove' );
	var input     = document.getElementById( 'kompas-author-photo-id' );
	var preview   = document.getElementById( 'kompas-author-photo-preview' );

	if ( ! selectBtn || ! input ) {
		return;
	}

	selectBtn.addEventListener( 'click', function( e ) {
		e.preventDefault();

		if ( frame ) {
			frame.open();
			return;
		}

		frame = wp.media( {
			title:    'Изабери фотографију аутора',
			button:   { text: 'Изабери' },
			multiple: false,
			library:  { type: 'image' },
		} );

		frame.on( 'select', function() {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var url = attachment.sizes && attachment.sizes.thumbnail
				? attachment.sizes.thumbnail.url
				: attachment.url;
			input.value = attachment.id;
			preview.src = url;
			preview.style.display = 'block';
			removeBtn.style.display = 'inline-block';
		} );

		frame.open();
	} );

	if ( removeBtn ) {
		removeBtn.addEventListener( 'click', function( e ) {
			e.preventDefault();
			input.value = '';
			preview.src = '';
			preview.style.display = 'none';
			removeBtn.style.display = 'none';
		} );
	}
} )();
