( function( blocks, element, blockEditor, components, apiFetch ) {
	var el              = element.createElement;
	var useState        = element.useState;
	var useBlockProps   = blockEditor.useBlockProps;
	var MediaUpload     = blockEditor.MediaUpload;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody       = components.PanelBody;
	var Button          = components.Button;
	var TextControl     = components.TextControl;

	blocks.registerBlockType( 'kompas/photo-gallery', {
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
				var copy   = images.slice();
				var target = index + dir;
				if ( target < 0 || target >= copy.length ) return;
				var tmp        = copy[ index ];
				copy[ index ]  = copy[ target ];
				copy[ target ] = tmp;
				setImages( copy );
			}

			function onSelectImages( media ) {
				var existing = images.slice();
				var pending  = media.length;

				media.forEach( function( m ) {
					var found = existing.some( function( e ) { return e.id === m.id; } );
					if ( found ) {
						pending--;
						return;
					}

					var newImg = {
						id:      m.id,
						url:     ( m.sizes && m.sizes.medium && m.sizes.medium.url ) ? m.sizes.medium.url : m.url,
						fullUrl: m.url,
						alt:     m.alt || '',
						source:  ( m.meta && m.meta.kompas_image_source ) ? m.meta.kompas_image_source : '',
					};

					// If meta not in library object, fetch from REST.
					if ( ! newImg.source ) {
						apiFetch( { path: '/wp/v2/media/' + m.id + '?context=edit' } ).then( function( data ) {
							var src = ( data.meta && data.meta.kompas_image_source ) ? data.meta.kompas_image_source : '';
							existing.push( Object.assign( {}, newImg, { source: src } ) );
							pending--;
							if ( pending <= 0 ) {
								setImages( existing );
							}
						} ).catch( function() {
							existing.push( newImg );
							pending--;
							if ( pending <= 0 ) {
								setImages( existing );
							}
						} );
					} else {
						existing.push( newImg );
						pending--;
						if ( pending <= 0 ) {
							setImages( existing );
						}
					}
				} );

				// If all were duplicates, still trigger update.
				if ( pending <= 0 ) {
					setImages( existing );
				}
			}

			// Preview: prva slika velika, ostale u redu ispod.
			var previewGrid = images.length > 0
				? el( 'div', { style: { display: 'flex', flexDirection: 'column', gap: '4px' } },
					// Prva slika — velika
					el( 'div', { style: { aspectRatio: '16/9', overflow: 'hidden', background: '#111' } },
						el( 'img', {
							src: images[0].url,
							alt: images[0].alt,
							style: { width: '100%', height: '100%', objectFit: 'cover' },
						} )
					),
					// Ostale — red thumbnaila
					images.length > 1 && el( 'div', {
						style: {
							display: 'grid',
							gridTemplateColumns: 'repeat(4, 1fr)',
							gap: '4px',
						},
					},
						images.slice(1).map( function( img, i ) {
							return el( 'div', {
								key: img.id || i,
								style: { aspectRatio: '4/3', overflow: 'hidden', background: '#111' },
							},
								el( 'img', {
									src: img.url,
									alt: img.alt,
									style: { width: '100%', height: '100%', objectFit: 'cover' },
								} )
							);
						} )
					)
				)
				: el( 'div', {
					style: {
						padding: '3rem',
						background: '#f5f5f5',
						border: '2px dashed #ccc',
						textAlign: 'center',
						color: '#777',
					},
				}, 'Додајте слике у фото галерију →' );

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Слике (' + images.length + ')', initialOpen: true },

						el( MediaUpload, {
							onSelect:     onSelectImages,
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

						images.map( function( img, i ) {
							return el( 'div', {
								key: img.id || i,
								style: {
									display:       'flex',
									gap:           '8px',
									alignItems:    'flex-start',
									padding:       '8px 0',
									borderBottom:  '1px solid #eee',
								},
							},
								el( 'img', {
									src:   img.url,
									style: { width: '48px', height: '48px', objectFit: 'cover', borderRadius: '4px', flexShrink: 0 },
								} ),
								el( 'div', { style: { flex: 1, minWidth: 0 } },
									el( 'div', { style: { fontSize: '11px', color: '#757575', marginBottom: '4px' } }, (i+1) + '/' + images.length ),
									el( TextControl, {
										placeholder:           'Извор (нпр. Тањуг)',
										value:                 img.source || '',
										onChange:              function( val ) { updateImage( i, 'source', val ); },
										__nextHasNoMarginBottom: true,
									} )
								),
								el( 'div', { style: { display: 'flex', flexDirection: 'column', gap: '2px', flexShrink: 0 } },
									el( Button, { icon: 'arrow-up-alt2',   size: 'small', disabled: i === 0,               onClick: function() { moveImage(i,-1); }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
									el( Button, { icon: 'arrow-down-alt2', size: 'small', disabled: i === images.length-1, onClick: function() { moveImage(i,1);  }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
									el( Button, { icon: 'no-alt',          size: 'small', isDestructive: true,             onClick: function() { removeImage(i);  }, style: { minWidth:'24px', height:'24px', padding:0 } } )
								)
							);
						} )
					)
				),
				el( 'div', blockProps, previewGrid )
			);
		},
		save: function() { return null; },
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.apiFetch
);
