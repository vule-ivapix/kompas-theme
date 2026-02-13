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
