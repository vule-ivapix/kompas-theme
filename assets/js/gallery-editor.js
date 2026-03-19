( function( blocks, element, blockEditor, components, apiFetch ) {
	var el                = element.createElement;
	var useEffect         = element.useEffect;
	var useBlockProps     = blockEditor.useBlockProps;
	var MediaUpload       = blockEditor.MediaUpload;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody         = components.PanelBody;
	var Button            = components.Button;

	function normalizeImage( image ) {
		var raw     = image || {};
		var id      = parseInt( raw.id, 10 ) || 0;
		var baseUrl = raw.url || '';
		var fullUrl = raw.fullUrl || '';
		var thumbUrl = raw.thumbUrl || baseUrl || fullUrl;

		return {
			id: id,
			url: fullUrl || baseUrl || thumbUrl,
			thumbUrl: thumbUrl || baseUrl || fullUrl,
			alt: raw.alt || '',
			credit: raw.credit || raw.source || '',
		};
	}

	function normalizeImages( images ) {
		return ( images || [] ).map( normalizeImage ).filter( function( image ) {
			return !! image.url;
		} );
	}

	function haveSameEditableImages( left, right ) {
		var i;

		if ( left.length !== right.length ) {
			return false;
		}

		for ( i = 0; i < left.length; i++ ) {
			if ( ( parseInt( left[ i ].id, 10 ) || 0 ) !== ( right[ i ].id || 0 ) ) {
				return false;
			}
			if ( ( left[ i ].url || '' ) !== ( right[ i ].url || '' ) ) {
				return false;
			}
			if ( ( left[ i ].thumbUrl || '' ) !== ( right[ i ].thumbUrl || '' ) ) {
				return false;
			}
			if ( ( left[ i ].alt || '' ) !== ( right[ i ].alt || '' ) ) {
				return false;
			}
			if ( ( left[ i ].credit || '' ) !== ( right[ i ].credit || '' ) ) {
				return false;
			}
		}

		return true;
	}

	function syncImagesFromMedia( nextImages ) {
		var normalized = normalizeImages( nextImages );
		var ids = normalized
			.map( function( image ) { return image.id; } )
			.filter( function( id ) { return id > 0; } );

		if ( ! ids.length ) {
			return Promise.resolve( normalized );
		}

		return apiFetch( {
			path: '/wp/v2/media?include=' + ids.join( ',' ) + '&per_page=' + ids.length + '&context=edit',
		} ).then( function( mediaItems ) {
			var creditsById = {};

			mediaItems.forEach( function( item ) {
				creditsById[ item.id ] = ( item.meta && item.meta.kompas_image_source )
					? String( item.meta.kompas_image_source ).trim()
					: '';
			} );

			return normalized.map( function( image ) {
				if ( ! image.id ) {
					return image;
				}

				return Object.assign( {}, image, {
					credit: Object.prototype.hasOwnProperty.call( creditsById, image.id )
						? creditsById[ image.id ]
						: '',
				} );
			} );
		} ).catch( function() {
			return normalized;
		} );
	}

	function createPreview( images, placeholder ) {
		if ( ! images.length ) {
			return el( 'div', {
				style: {
					padding: '3rem',
					background: '#f5f5f5',
					border: '2px dashed #ccc',
					textAlign: 'center',
					color: '#777',
				},
			}, placeholder );
		}

		return el( 'div', { style: { position: 'relative' } },
			el( 'img', {
				src: images[ 0 ].url,
				alt: images[ 0 ].alt,
				style: {
					width: '100%',
					display: 'block',
					aspectRatio: '16/10',
					objectFit: 'cover',
				},
			} ),
			el( 'div', {
				style: {
					position: 'absolute',
					bottom: '12px',
					right: '12px',
					background: 'rgba(0,0,0,0.6)',
					color: '#fff',
					padding: '3px 7px',
					fontSize: '11px',
					fontStyle: images[ 0 ].credit ? 'italic' : 'normal',
					maxWidth: 'calc(100% - 24px)',
				},
			},
			el( 'strong', {
				style: { fontStyle: 'normal', letterSpacing: '0.03em' },
			}, images[ 0 ].credit ? 'ФОТО: ' : '' ),
			images[ 0 ].credit || 'Nema upisan izvor na fotografiji'
			),
			el( 'div', {
				style: {
					position: 'absolute',
					bottom: '12px',
					left: '50%',
					transform: 'translateX(-50%)',
					background: 'rgba(0,0,0,0.6)',
					color: '#fff',
					padding: '4px 12px',
					borderRadius: '4px',
					fontSize: '14px',
					fontWeight: 600,
				},
			}, '1/' + images.length )
		);
	}

	function registerGalleryBlock( blockName, placeholder ) {
		blocks.registerBlockType( blockName, {
			edit: function( props ) {
				var blockProps = useBlockProps();
				var rawImages  = props.attributes.images || [];
				var images     = normalizeImages( rawImages );

				function setImages( next ) {
					props.setAttributes( { images: next } );
				}

				function removeImage( index ) {
					var copy = images.slice();
					copy.splice( index, 1 );
					setImages( copy );
				}

				function moveImage( index, dir ) {
					var copy = images.slice();
					var target = index + dir;

					if ( target < 0 || target >= copy.length ) {
						return;
					}

					var tmp = copy[ index ];
					copy[ index ] = copy[ target ];
					copy[ target ] = tmp;
					setImages( copy );
				}

				function onSelectImages( media ) {
					var existing = images.slice();
					var selected = media.filter( function( item ) {
						return ! existing.some( function( current ) {
							return current.id === item.id;
						} );
					} );

					if ( ! selected.length ) {
						setImages( existing );
						return;
					}

					syncImagesFromMedia(
						existing.concat(
							selected.map( function( item ) {
								return {
									id: item.id,
									url: item.url,
									thumbUrl: ( item.sizes && item.sizes.medium && item.sizes.medium.url )
										? item.sizes.medium.url
										: item.url,
									alt: item.alt || '',
									credit: ( item.meta && item.meta.kompas_image_source )
										? String( item.meta.kompas_image_source ).trim()
										: '',
								};
							} )
						)
					).then( function( resolvedImages ) {
						setImages( resolvedImages );
					} );
				}

				useEffect( function() {
					if ( ! rawImages.length ) {
						return;
					}

					syncImagesFromMedia( rawImages ).then( function( resolvedImages ) {
						if ( ! haveSameEditableImages( rawImages, resolvedImages ) ) {
							setImages( resolvedImages );
						}
					} );
				}, [ JSON.stringify( rawImages ) ] );

				return el( element.Fragment, null,
					el( InspectorControls, null,
						el( PanelBody, { title: 'Слике (' + images.length + ')', initialOpen: true },
							el( MediaUpload, {
								onSelect: onSelectImages,
								allowedTypes: [ 'image' ],
								multiple: true,
								render: function( obj ) {
									return el( Button, {
										variant: 'secondary',
										onClick: obj.open,
										style: {
											width: '100%',
											justifyContent: 'center',
											marginBottom: '12px',
										},
									}, '+ Додај слике' );
								},
							} ),
							el( 'p', {
								style: {
									fontSize: '11px',
									color: '#757575',
									marginTop: '0',
									marginBottom: '12px',
								},
							}, 'Izvor se čita iz same fotografije u Media biblioteci, ne unosi se ručno u ovom bloku.' ),
							images.map( function( image, index ) {
								var credit = image.credit || '';

								return el( 'div', {
									key: image.id || index,
									style: {
										display: 'flex',
										gap: '8px',
										alignItems: 'flex-start',
										padding: '8px 0',
										borderBottom: '1px solid #eee',
									},
								},
								el( 'img', {
									src: image.thumbUrl || image.url,
									style: {
										width: '48px',
										height: '48px',
										objectFit: 'cover',
										borderRadius: '4px',
										flexShrink: 0,
									},
								} ),
								el( 'div', { style: { flex: 1, minWidth: 0 } },
									el( 'div', {
										style: {
											fontSize: '11px',
											color: '#757575',
											marginBottom: '4px',
										},
									}, ( index + 1 ) + '/' + images.length ),
									el( 'div', {
										style: {
											fontSize: '11px',
											color: '#757575',
											marginBottom: '4px',
										},
									}, 'Izvor fotografije' ),
									el( 'div', {
										style: {
											fontSize: '13px',
											lineHeight: '1.4',
											color: credit ? '#1e1e1e' : '#757575',
											fontStyle: credit ? 'normal' : 'italic',
										},
									}, credit || 'Nema upisan izvor na fotografiji' )
								),
								el( 'div', {
									style: {
										display: 'flex',
										flexDirection: 'column',
										gap: '2px',
										flexShrink: 0,
									},
								},
								el( Button, {
									icon: 'arrow-up-alt2',
									size: 'small',
									disabled: index === 0,
									onClick: function() { moveImage( index, -1 ); },
									style: { minWidth: '24px', height: '24px', padding: 0 },
								} ),
								el( Button, {
									icon: 'arrow-down-alt2',
									size: 'small',
									disabled: index === images.length - 1,
									onClick: function() { moveImage( index, 1 ); },
									style: { minWidth: '24px', height: '24px', padding: 0 },
								} ),
								el( Button, {
									icon: 'no-alt',
									size: 'small',
									isDestructive: true,
									onClick: function() { removeImage( index ); },
									style: { minWidth: '24px', height: '24px', padding: 0 },
								} )
								) );
							} )
						)
					),
					el( 'div', blockProps, createPreview( images, placeholder ) )
				);
			},
			save: function() {
				return null;
			},
		} );
	}

	registerGalleryBlock( 'kompas/gallery-slider', 'Додајте слике у галерију →' );
	registerGalleryBlock( 'kompas/photo-gallery', 'Додајте слике у галерију →' );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.apiFetch
);
