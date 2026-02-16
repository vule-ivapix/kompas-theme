( function( blocks, element, blockEditor, components ) {
	var el              = element.createElement;
	var useState        = element.useState;
	var useBlockProps   = blockEditor.useBlockProps;
	var MediaUpload     = blockEditor.MediaUpload;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody       = components.PanelBody;
	var Button          = components.Button;
	var TextControl     = components.TextControl;

	blocks.registerBlockType( 'kompas/gallery-slider', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			var images     = props.attributes.images || [];

			function setImages( next ) {
				props.setAttributes( { images: next } );
			}

			function updateImage( index, key, value ) {
				var copy = images.slice();
				copy[ index ] = Object.assign( {}, copy[ index ] );
				copy[ index ][ key ] = value;
				setImages( copy );
			}

			function removeImage( index ) {
				var copy = images.slice();
				copy.splice( index, 1 );
				setImages( copy );
			}

			function moveImage( index, dir ) {
				var copy = images.slice();
				var target = index + dir;
				if ( target < 0 || target >= copy.length ) return;
				var tmp = copy[ index ];
				copy[ index ] = copy[ target ];
				copy[ target ] = tmp;
				setImages( copy );
			}

			function onSelectImages( media ) {
				var existing = images.slice();
				media.forEach( function( m ) {
					// Don't add duplicates.
					var found = existing.some( function( e ) { return e.id === m.id; } );
					if ( ! found ) {
						existing.push( {
							id:     m.id,
							url:    m.url,
							alt:    m.alt || '',
							credit: '',
						} );
					}
				} );
				setImages( existing );
			}

			// Preview: show first image or placeholder.
			var previewImg = images.length > 0
				? el( 'div', { style: { position: 'relative' } },
					el( 'img', {
						src: images[0].url,
						alt: images[0].alt,
						style: { width: '100%', display: 'block', aspectRatio: '16/10', objectFit: 'cover' },
					} ),
					el( 'div', {
						style: {
							position: 'absolute', bottom: '12px', left: '50%', transform: 'translateX(-50%)',
							background: 'rgba(0,0,0,0.6)', color: '#fff', padding: '4px 12px',
							borderRadius: '4px', fontSize: '14px', fontWeight: 600,
						},
					}, '1/' + images.length ),
					images[0].credit && el( 'p', {
						style: { fontSize: '12px', color: '#777', margin: '8px 0 0' },
					}, 'Фото: ' + images[0].credit )
				)
				: el( 'div', {
					style: { padding: '3rem', background: '#f5f5f5', border: '2px dashed #ccc', textAlign: 'center', color: '#777' },
				}, 'Додајте слике у галерију →' );

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Слике (' + images.length + ')', initialOpen: true },

						// Add images button.
						el( MediaUpload, {
							onSelect:    onSelectImages,
							allowedTypes: [ 'image' ],
							multiple:     true,
							render:       function( obj ) {
								return el( Button, {
									variant: 'secondary',
									onClick: obj.open,
									style:   { width: '100%', justifyContent: 'center', marginBottom: '12px' },
								}, '+ Додај слике' );
							},
						} ),

						// Image list.
						images.map( function( img, i ) {
							return el( 'div', {
								key: img.id || i,
								style: {
									display: 'flex', gap: '8px', alignItems: 'flex-start',
									padding: '8px 0', borderBottom: '1px solid #eee',
								},
							},
								el( 'img', {
									src: img.url,
									style: { width: '48px', height: '48px', objectFit: 'cover', borderRadius: '4px', flexShrink: 0 },
								} ),
								el( 'div', { style: { flex: 1, minWidth: 0 } },
									el( 'div', { style: { fontSize: '11px', color: '#757575', marginBottom: '4px' } }, (i+1) + '/' + images.length ),
									el( TextControl, {
										placeholder: 'Извор (нпр. Профимедиа)',
										value: img.credit || '',
										onChange: function( val ) { updateImage( i, 'credit', val ); },
										__nextHasNoMarginBottom: true,
										style: { marginBottom: 0 },
									} )
								),
								el( 'div', { style: { display: 'flex', flexDirection: 'column', gap: '2px', flexShrink: 0 } },
									el( Button, { icon: 'arrow-up-alt2', size: 'small', disabled: i === 0, onClick: function() { moveImage(i,-1); }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
									el( Button, { icon: 'arrow-down-alt2', size: 'small', disabled: i === images.length-1, onClick: function() { moveImage(i,1); }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
									el( Button, { icon: 'no-alt', size: 'small', isDestructive: true, onClick: function() { removeImage(i); }, style: { minWidth:'24px', height:'24px', padding:0 } } )
								)
							);
						} )
					)
				),
				el( 'div', blockProps, previewImg )
			);
		},
		save: function() { return null; },
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components
);
