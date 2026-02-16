( function( blocks, element, serverSideRender, blockEditor, components, apiFetch ) {
	var el           = element.createElement;
	var useState     = element.useState;
	var useEffect    = element.useEffect;
	var SSR          = serverSideRender;
	var useBlockProps       = blockEditor.useBlockProps;
	var InspectorControls  = blockEditor.InspectorControls;
	var PanelBody           = components.PanelBody;
	var CheckboxControl     = components.CheckboxControl;
	var Spinner             = components.Spinner;

	/**
	 * TermPicker: renders checkboxes for a taxonomy in InspectorControls.
	 */
	function TermPicker( props ) {
		var selectedIds    = props.selectedIds || [];
		var setSelectedIds = props.onChange;
		var restBase       = props.restBase; // 'categories' or 'tags'
		var label          = props.label;

		var terms    = useState( [] );
		var loading  = useState( true );
		var termList = terms[0];
		var setTerms = terms[1];
		var isLoading  = loading[0];
		var setLoading = loading[1];

		useEffect( function() {
			apiFetch( { path: '/wp/v2/' + restBase + '?per_page=100&hide_empty=false&orderby=name&order=asc' } )
				.then( function( data ) {
					setTerms( data );
					setLoading( false );
				} )
				.catch( function() {
					setLoading( false );
				} );
		}, [ restBase ] );

		if ( isLoading ) {
			return el( PanelBody, { title: label, initialOpen: true },
				el( Spinner )
			);
		}

		function toggleTerm( termId, checked ) {
			var next;
			if ( checked ) {
				next = selectedIds.concat( [ termId ] );
			} else {
				next = selectedIds.filter( function( id ) { return id !== termId; } );
			}
			setSelectedIds( next );
		}

		return el( PanelBody, { title: label, initialOpen: true },
			termList.map( function( term ) {
				return el( CheckboxControl, {
					key:      term.id,
					label:    term.name + ' (' + term.count + ')',
					checked:  selectedIds.indexOf( term.id ) !== -1,
					onChange: function( checked ) { toggleTerm( term.id, checked ); },
				} );
			} )
		);
	}

	/**
	 * Factory: creates an edit component with a TermPicker sidebar + SSR preview.
	 */
	function makeEdit( blockName, restBase, pickerLabel ) {
		return function( props ) {
			var blockProps  = useBlockProps();
			var selectedIds = props.attributes.selectedIds || [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( TermPicker, {
						selectedIds: selectedIds,
						restBase:    restBase,
						label:       pickerLabel,
						onChange:     function( next ) {
							props.setAttributes( { selectedIds: next } );
						},
					} )
				),
				el( 'div', blockProps,
					el( SSR, {
						block:      blockName,
						attributes: props.attributes,
					} )
				)
			);
		};
	}

	/* ── Header Nav (categories) ─────────────────────────────── */
	blocks.registerBlockType( 'kompas/header-nav', {
		edit: makeEdit( 'kompas/header-nav', 'categories', 'Kategorije za navigaciju' ),
		save: function() { return null; },
	} );

	/* ── Header Tags ─────────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/header-tags', {
		edit: makeEdit( 'kompas/header-tags', 'tags', 'Tagovi za navigaciju' ),
		save: function() { return null; },
	} );

	/* ── Footer Categories ───────────────────────────────────── */
	blocks.registerBlockType( 'kompas/footer-categories', {
		edit: makeEdit( 'kompas/footer-categories', 'categories', 'Kategorije u footeru' ),
		save: function() { return null; },
	} );

	/* ── Tabs Najnovije / Najčitanije (no picker needed) ─────── */
	blocks.registerBlockType( 'kompas/tabs-najnovije-najcitanije', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			return el( 'div', blockProps,
				el( SSR, {
					block:      'kompas/tabs-najnovije-najcitanije',
					attributes: props.attributes,
				} )
			);
		},
		save: function() { return null; },
	} );

	/* ── PostPicker: search & select posts (array of IDs) ────── */
	var SearchControl = components.SearchControl;
	var Button        = components.Button;

	function HeroPostPicker( props ) {
		var selectedIds = props.selectedIds || [];
		var maxPosts    = props.max || 7;

		var _s   = useState( '' );     var search      = _s[0]; var setSearch      = _s[1];
		var _r   = useState( [] );     var results     = _r[0]; var setResults     = _r[1];
		var _l   = useState( false );  var loading     = _l[0]; var setLoading     = _l[1];
		var _sp  = useState( [] );     var selPosts    = _sp[0]; var setSelPosts   = _sp[1];

		// Load titles for selected IDs.
		useEffect( function() {
			if ( ! selectedIds.length ) { setSelPosts( [] ); return; }
			apiFetch( { path: '/wp/v2/posts?include=' + selectedIds.join(',') + '&per_page=' + selectedIds.length + '&_fields=id,title' } )
				.then( function( posts ) {
					var ordered = selectedIds.map( function( id ) {
						return posts.find( function( p ) { return p.id === id; } );
					} ).filter( Boolean );
					setSelPosts( ordered );
				} );
		}, [ selectedIds.join(',') ] );

		// Search.
		useEffect( function() {
			if ( search.length < 2 ) { setResults( [] ); return; }
			setLoading( true );
			var t = setTimeout( function() {
				apiFetch( { path: '/wp/v2/posts?search=' + encodeURIComponent( search ) + '&per_page=10&_fields=id,title' } )
					.then( function( p ) { setResults( p ); setLoading( false ); } );
			}, 300 );
			return function() { clearTimeout( t ); };
		}, [ search ] );

		function addPost( post ) {
			if ( selectedIds.length >= maxPosts ) return;
			props.onChange( selectedIds.concat( [ post.id ] ) );
			setSearch( '' ); setResults( [] );
		}
		function removePost( id ) {
			props.onChange( selectedIds.filter( function( x ) { return x !== id; } ) );
		}
		function movePost( idx, dir ) {
			var a = selectedIds.slice(); var t = idx + dir;
			if ( t < 0 || t >= a.length ) return;
			var tmp = a[idx]; a[idx] = a[t]; a[t] = tmp;
			props.onChange( a );
		}

		var filtered = results.filter( function( p ) { return selectedIds.indexOf( p.id ) === -1; } );

		return el( 'div', null,
			selPosts.length > 0 && el( 'div', { style: { marginBottom: '12px' } },
				el( 'p', { style: { fontSize: '11px', textTransform: 'uppercase', fontWeight: 600, color: '#757575', marginBottom: '8px' } },
					'Hero postovi (' + selPosts.length + '/' + maxPosts + '):'
				),
				selPosts.map( function( post, i ) {
					return el( 'div', { key: post.id, style: { display: 'flex', alignItems: 'center', gap: '4px', padding: '6px 8px', marginBottom: '4px', background: '#f0f0f0', borderRadius: '4px', fontSize: '12px' } },
						el( 'span', { style: { color: '#757575', fontSize: '10px', minWidth: '18px' } }, (i+1) + '.' ),
						el( 'span', { style: { flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, post.title.rendered ),
						el( 'div', { style: { display: 'flex', gap: '2px', flexShrink: 0 } },
							el( Button, { icon: 'arrow-up-alt2', size: 'small', disabled: i === 0, onClick: function() { movePost(i,-1); }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
							el( Button, { icon: 'arrow-down-alt2', size: 'small', disabled: i === selPosts.length-1, onClick: function() { movePost(i,1); }, style: { minWidth:'24px', height:'24px', padding:0 } } ),
							el( Button, { icon: 'no-alt', size: 'small', isDestructive: true, onClick: function() { removePost( post.id ); }, style: { minWidth:'24px', height:'24px', padding:0 } } )
						)
					);
				} )
			),
			selectedIds.length < maxPosts && el( SearchControl, { value: search, onChange: setSearch, placeholder: 'Pretraži postove...' } ),
			loading && el( Spinner ),
			!loading && filtered.length > 0 && el( 'div', { style: { border: '1px solid #ddd', borderRadius: '4px', maxHeight: '200px', overflowY: 'auto', background: '#fff' } },
				filtered.map( function( post ) {
					return el( Button, { key: post.id, onClick: function() { addPost(post); }, style: { display:'block', width:'100%', textAlign:'left', padding:'8px 12px', fontSize:'13px', borderBottom:'1px solid #f0f0f0', cursor:'pointer', borderRadius:0, height:'auto' } },
						el( 'span', { style: { color: '#757575', fontSize: '11px', marginRight: '6px' } }, '#' + post.id ),
						post.title.rendered
					);
				} )
			),
			el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
				'Izaberite 7 postova za hero sekciju. Pozicije: 1 velika + 2 horizontalne (levo) | 4 kartice (desno).'
			)
		);
	}

	/* ── Category Grid ──────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/category-grid', {
		edit: function( props ) {
			var blockProps  = useBlockProps();
			var selectedIds = props.attributes.selectedIds || [];

			// Fetch category names for selected IDs.
			var _cats = useState( [] );
			var cats = _cats[0]; var setCats = _cats[1];

			useEffect( function() {
				if ( ! selectedIds.length ) { setCats( [] ); return; }
				apiFetch( { path: '/wp/v2/categories?include=' + selectedIds.join(',') + '&per_page=' + selectedIds.length + '&_fields=id,name' } )
					.then( function( data ) {
						var ordered = selectedIds.map( function( id ) {
							return data.find( function( c ) { return c.id === id; } );
						} ).filter( Boolean );
						setCats( ordered );
					} );
			}, [ selectedIds.join(',') ] );

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( TermPicker, {
						selectedIds: selectedIds,
						restBase:    'categories',
						label:       'Kategorije za grid',
						onChange:     function( next ) {
							props.setAttributes( { selectedIds: next } );
						},
					} )
				),
				el( 'div', blockProps,
					selectedIds.length === 0
						? el( 'div', { style: { padding: '2rem', background: '#f9f9f9', border: '1px dashed #ccc', textAlign: 'center', color: '#777' } },
							el( 'p', { style: { fontSize: '0.9375rem', fontWeight: 600, margin: '0 0 0.5rem' } }, 'Category Grid'),
							el( 'p', { style: { fontSize: '0.8125rem', margin: 0 } }, 'Izaberite kategorije u sidebar-u. Za svaku se prikazuje: 2 velike + 4 male vesti.')
						)
						: cats.map( function( cat ) {
							return el( 'div', { key: cat.id, style: { marginBottom: '2rem' } },
								el( 'div', { style: { borderBottom: '3px solid #c0392b', paddingBottom: '0.5rem', marginBottom: '1rem' } },
									el( 'span', { style: { fontSize: '1rem', fontWeight: 800, textTransform: 'uppercase', letterSpacing: '0.02em' } }, cat.name )
								),
								el( 'div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginBottom: '1rem' } },
									el( 'div', { style: { background: '#eee', height: '140px', display: 'flex', alignItems: 'flex-end', padding: '0.75rem', fontSize: '0.8125rem', fontWeight: 600, color: '#555' } }, 'Велика вест 1' ),
									el( 'div', { style: { background: '#eee', height: '140px', display: 'flex', alignItems: 'flex-end', padding: '0.75rem', fontSize: '0.8125rem', fontWeight: 600, color: '#555' } }, 'Велика вест 2' )
								),
								el( 'div', { style: { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: '0.75rem' } },
									el( 'div', { style: { background: '#f0f0f0', height: '80px', display: 'flex', alignItems: 'flex-end', padding: '0.5rem', fontSize: '0.6875rem', color: '#777' } }, 'Мала вест' ),
									el( 'div', { style: { background: '#f0f0f0', height: '80px', display: 'flex', alignItems: 'flex-end', padding: '0.5rem', fontSize: '0.6875rem', color: '#777' } }, 'Мала вест' ),
									el( 'div', { style: { background: '#f0f0f0', height: '80px', display: 'flex', alignItems: 'flex-end', padding: '0.5rem', fontSize: '0.6875rem', color: '#777' } }, 'Мала вест' ),
									el( 'div', { style: { background: '#f0f0f0', height: '80px', display: 'flex', alignItems: 'flex-end', padding: '0.5rem', fontSize: '0.6875rem', color: '#777' } }, 'Мала вест' )
								)
							);
						} )
				)
			);
		},
		save: function() { return null; },
	} );

	/* ── Mobile Nav (no picker, SSR preview) ────────────────── */
	blocks.registerBlockType( 'kompas/mobile-nav', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			return el( 'div', blockProps,
				el( 'div', {
					style: {
						padding: '1rem',
						background: '#f5f5f5',
						border: '1px dashed #ccc',
						textAlign: 'center',
						fontSize: '0.875rem',
						color: '#777',
					},
				}, 'Mobile navigacija (vidljiva samo na mobilnom)' )
			);
		},
		save: function() { return null; },
	} );

	/* ── Banner Placeholder ────────────────────────────────── */
	var SelectControl = components.SelectControl;

	blocks.registerBlockType( 'kompas/banner', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			var variant    = props.attributes.variant || 'horizontal';
			var isSquare   = variant === 'square';

			var style = {
				border: '1px solid #dddddd',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				background: '#f9f9f9',
				fontSize: '0.875rem',
				fontWeight: 700,
				textTransform: 'uppercase',
				letterSpacing: '0.1em',
				color: '#999',
			};

			if ( isSquare ) {
				style.minHeight = '300px';
			} else {
				style.padding = '2.5rem 2rem';
			}

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Banner podešavanja', initialOpen: true },
						el( SelectControl, {
							label: 'Varijanta',
							value: variant,
							options: [
								{ label: 'Horizontalni', value: 'horizontal' },
								{ label: 'Kvadratni',    value: 'square' },
							],
							onChange: function( v ) {
								props.setAttributes( { variant: v } );
							},
						} )
					)
				),
				el( 'div', blockProps,
					el( 'div', { style: style }, 'БАНЕР' )
				)
			);
		},
		save: function() { return null; },
	} );

	/* ── Kolumne ────────────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/kolumne', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			var postIds    = props.attributes.postIds || [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Kolumne postovi', initialOpen: true },
						el( HeroPostPicker, {
							selectedIds: postIds,
							max: 10,
							onChange: function( next ) {
								props.setAttributes( { postIds: next } );
							}
						} ),
						el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
							'Izaberite postove čiji će se autori prikazati u sekciji Kolumne.'
						)
					)
				),
				el( 'div', blockProps,
					el( SSR, {
						block:      'kompas/kolumne',
						attributes: props.attributes,
					} )
				)
			);
		},
		save: function() { return null; },
	} );

	/* ── Reč Urednika ───────────────────────────────────────── */
	var TextControl = components.TextControl;

	blocks.registerBlockType( 'kompas/rec-urednika', {
		edit: function( props ) {
			var blockProps = useBlockProps();
			var postId     = props.attributes.postId || 0;

			// Reuse HeroPostPicker with max=1, convert single-element array.
			var selectedIds = postId ? [ postId ] : [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Reč urednika', initialOpen: true },
						el( TextControl, {
							label: 'Naslov sekcije',
							value: props.attributes.title || '',
							onChange: function( v ) { props.setAttributes( { title: v } ); },
						} ),
						el( TextControl, {
							label: 'Tekst linka',
							value: props.attributes.linkText || '',
							onChange: function( v ) { props.setAttributes( { linkText: v } ); },
						} ),
						el( TextControl, {
							label: 'Slug kategorije',
							value: props.attributes.categorySlug || '',
							onChange: function( v ) { props.setAttributes( { categorySlug: v } ); },
						} ),
						el( HeroPostPicker, {
							selectedIds: selectedIds,
							max: 1,
							onChange: function( next ) {
								props.setAttributes( { postId: next.length ? next[0] : 0 } );
							}
						} ),
						el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
							'Izaberite post koji će biti prikazan. Ako nije izabran, uzima se najnoviji iz kategorije.'
						)
					)
				),
				el( 'div', blockProps,
					el( SSR, {
						block:      'kompas/rec-urednika',
						attributes: props.attributes,
					} )
				)
			);
		},
		save: function() { return null; },
	} );

	/* ── Archive Layout ──────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/archive-layout', {
		edit: function( props ) {
			var blockProps   = useBlockProps();
			var heroPostIds  = props.attributes.heroPostIds || [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Hero postovi (7)', initialOpen: true },
						el( HeroPostPicker, {
							selectedIds: heroPostIds,
							max: 7,
							onChange: function( next ) {
								props.setAttributes( { heroPostIds: next } );
							}
						} )
					)
				),
				el( 'div', blockProps,
					el( 'div', {
						style: {
							padding: '2rem',
							background: '#f5f5f5',
							border: '1px dashed #ccc',
							textAlign: 'center',
							fontSize: '0.875rem',
							color: '#777',
						},
					},
						'Kompas Archive Layout',
						el( 'br' ),
						heroPostIds.length > 0
							? 'Hero: ' + heroPostIds.length + ' postova izabrano'
							: 'Hero: nije izabran nijedan post'
					)
				)
			);
		},
		save: function() { return null; },
	} );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.serverSideRender,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.apiFetch
);
