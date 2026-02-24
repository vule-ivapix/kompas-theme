( function( blocks, element, serverSideRender, blockEditor, components, apiFetch ) {
	var el           = element.createElement;
	var useState     = element.useState;
	var useEffect    = element.useEffect;
	var SSR          = serverSideRender;
	var useBlockProps       = blockEditor.useBlockProps;
	var InspectorControls  = blockEditor.InspectorControls;
	var PanelBody           = components.PanelBody;
	var CheckboxControl     = components.CheckboxControl;
	var RangeControl        = components.RangeControl;
	var Spinner             = components.Spinner;
	var MediaUpload         = blockEditor.MediaUpload;
	var MediaUploadCheck    = blockEditor.MediaUploadCheck;

	/**
	 * TermPicker: renders checkboxes for a taxonomy in InspectorControls.
	 */
	function TermPicker( props ) {
		var selectedIds    = props.selectedIds || [];
		var setSelectedIds = props.onChange;
		var restBase       = props.restBase; // 'categories' or 'tags'
		var label          = props.label;
		var maxItems       = props.max || 0; // 0 = unlimited

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

		var atMax = maxItems > 0 && selectedIds.length >= maxItems;

		return el( PanelBody, { title: label + ( maxItems > 0 ? ' (' + selectedIds.length + '/' + maxItems + ')' : '' ), initialOpen: true },
			termList.map( function( term ) {
				var isChecked = selectedIds.indexOf( term.id ) !== -1;
				return el( CheckboxControl, {
					key:      term.id,
					label:    term.name + ' (' + term.count + ')',
					checked:  isChecked,
					disabled: !isChecked && atMax,
					onChange: function( checked ) { toggleTerm( term.id, checked ); },
				} );
			} )
		);
	}

	/**
	 * SortableTermPicker: selected items with ▲▼ reorder + checkbox list to add/remove.
	 */
	function SortableTermPicker( props ) {
		var selectedIds = props.selectedIds || [];
		var onChange    = props.onChange;
		var restBase    = props.restBase;
		var label       = props.label;

		var termsState = useState( [] );
		var loadState  = useState( true );
		var termList   = termsState[0];
		var setTerms   = termsState[1];
		var isLoading  = loadState[0];
		var setLoading = loadState[1];

		useEffect( function() {
			apiFetch( { path: '/wp/v2/' + restBase + '?per_page=100&hide_empty=false&orderby=name&order=asc' } )
				.then( function( data ) { setTerms( data ); setLoading( false ); } )
				.catch( function() { setLoading( false ); } );
		}, [ restBase ] );

		if ( isLoading ) {
			return el( PanelBody, { title: label, initialOpen: true }, el( Spinner ) );
		}

		// id → term object lookup
		var termMap = {};
		termList.forEach( function( t ) { termMap[ t.id ] = t; } );

		function move( idx, dir ) {
			var next = selectedIds.slice();
			var to   = idx + dir;
			if ( to < 0 || to >= next.length ) { return; }
			var tmp    = next[ idx ];
			next[ idx ] = next[ to ];
			next[ to ]  = tmp;
			onChange( next );
		}

		function remove( id ) {
			onChange( selectedIds.filter( function( x ) { return x !== id; } ) );
		}

		function add( id ) {
			if ( selectedIds.indexOf( id ) === -1 ) {
				onChange( selectedIds.concat( [ id ] ) );
			}
		}

		var unselected = termList.filter( function( t ) {
			return selectedIds.indexOf( t.id ) === -1;
		} );

		var sectionLabel = {
			margin: '0 0 5px',
			fontSize: '10px',
			fontWeight: '700',
			textTransform: 'uppercase',
			letterSpacing: '0.06em',
			color: '#757575',
		};
		var rowStyle = {
			display: 'flex',
			alignItems: 'center',
			gap: '3px',
			marginBottom: '4px',
			background: '#f0f0f0',
			padding: '5px 6px',
			borderRadius: '3px',
		};
		var btn = {
			background: '#fff',
			border: '1px solid #bbb',
			borderRadius: '2px',
			padding: '1px 5px',
			cursor: 'pointer',
			fontSize: '11px',
			lineHeight: '1.4',
			minWidth: '22px',
			textAlign: 'center',
		};
		var btnDanger = Object.assign( {}, btn, {
			color: '#b91c1c',
			borderColor: '#f9a8a8',
			marginLeft: 'auto',
			background: '#fff5f5',
		} );

		return el( PanelBody, { title: label, initialOpen: true },

			/* ── Selected items in chosen order ── */
			el( 'p', { style: sectionLabel }, 'Redosled (' + selectedIds.length + ' izabrano)' ),

			selectedIds.length === 0
				? el( 'p', { style: { fontSize: '12px', color: '#aaa', marginBottom: '12px' } }, 'Nije izabrana nijedna stavka.' )
				: el( 'div', { style: { marginBottom: '14px' } },
					selectedIds.map( function( id, idx ) {
						var term = termMap[ id ];
						if ( ! term ) { return null; }
						return el( 'div', { key: id, style: rowStyle },
							el( 'button', {
								style:    Object.assign( {}, btn, { opacity: idx === 0 ? 0.3 : 1 } ),
								disabled: idx === 0,
								title:    'Pomeri gore',
								onClick:  function( e ) { e.preventDefault(); move( idx, -1 ); },
							}, '▲' ),
							el( 'button', {
								style:    Object.assign( {}, btn, { opacity: idx === selectedIds.length - 1 ? 0.3 : 1 } ),
								disabled: idx === selectedIds.length - 1,
								title:    'Pomeri dole',
								onClick:  function( e ) { e.preventDefault(); move( idx, 1 ); },
							}, '▼' ),
							el( 'span', { style: { flex: 1, fontSize: '12px', fontWeight: '500', overflow: 'hidden', whiteSpace: 'nowrap', textOverflow: 'ellipsis' } }, term.name ),
							el( 'button', {
								style:   btnDanger,
								title:   'Ukloni',
								onClick: function( e ) { e.preventDefault(); remove( id ); },
							}, '×' )
						);
					} )
				),

			/* ── Unselected items to add ── */
			unselected.length > 0 && el( 'div', null,
				el( 'p', { style: sectionLabel }, 'Dodaj' ),
				unselected.map( function( term ) {
					return el( CheckboxControl, {
						key:      term.id,
						label:    term.name + ( term.count ? ' (' + term.count + ')' : '' ),
						checked:  false,
						onChange: function( checked ) { if ( checked ) { add( term.id ); } },
					} );
				} )
			)
		);
	}

	/**
	 * Factory: creates an edit component with a sortable or plain TermPicker + SSR preview.
	 * Pass sortable:true for drag-to-reorder support.
	 */
	function makeEdit( blockName, restBase, pickerLabel, sortable ) {
		return function( props ) {
			var blockProps  = useBlockProps();
			var selectedIds = props.attributes.selectedIds || [];
			var Picker      = sortable ? SortableTermPicker : TermPicker;

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( Picker, {
						selectedIds: selectedIds,
						restBase:    restBase,
						label:       pickerLabel,
						onChange:    function( next ) {
							props.setAttributes( { selectedIds: next } );
						},
					} )
				),
				el( 'div', blockProps,
					el( SSR, {
						key:        JSON.stringify( props.attributes ),
						block:      blockName,
						attributes: props.attributes,
					} )
				)
			);
		};
	}

	/* ── Header Nav (categories) – sa redosledom ─────────────── */
	blocks.registerBlockType( 'kompas/header-nav', {
		edit: makeEdit( 'kompas/header-nav', 'categories', 'Kategorije za navigaciju', true ),
		save: function() { return null; },
	} );

	/* ── Header Tags ─────────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/header-tags', {
		edit: makeEdit( 'kompas/header-tags', 'tags', 'Tagovi za navigaciju' ),
		save: function() { return null; },
	} );

	/* ── Footer Categories – sa redosledom ───────────────────── */
	blocks.registerBlockType( 'kompas/footer-categories', {
		edit: function( props ) {
			var blockProps  = useBlockProps();
			var selectedIds = props.attributes.selectedIds || [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( SortableTermPicker, {
						selectedIds: selectedIds,
						restBase:    'categories',
						label:       'Kategorije u footeru',
						onChange:    function( next ) {
							props.setAttributes( { selectedIds: next } );
						},
					} )
				),
				el( 'div', blockProps,
					el( SSR, {
						key:        JSON.stringify( props.attributes ),
						block:      'kompas/footer-categories',
						attributes: props.attributes,
					} )
				)
			);
		},
		save: function() { return null; },
	} );

		/* ── Footer Pages ────────────────────────────────────────── */
		blocks.registerBlockType( 'kompas/footer-pages', {
			edit: function( props ) {
				var blockProps  = useBlockProps();
				var selectedIds = props.attributes.selectedIds || [];

				return el( element.Fragment, null,
					el( InspectorControls, null,
						el( PanelBody, { title: 'Footer stranice', initialOpen: true },
							el( PagePicker, {
								title: 'Footer meni',
								selectedIds: selectedIds,
								max: 8,
								onChange: function( next ) {
									props.setAttributes( { selectedIds: next } );
								},
							} ),
							el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
								'Izaberi stranice koje se prikazuju u donjem footer meniju.'
							)
						)
					),
					el( 'div', blockProps,
						el( SSR, {
							key:        JSON.stringify( props.attributes ),
							block:      'kompas/footer-pages',
							attributes: props.attributes,
						} )
					)
				);
			},
			save: function() { return null; },
		} );

			/* ── Tabs Najnovije / Najčitanije ─────────────────────────── */
			blocks.registerBlockType( 'kompas/tabs-najnovije-najcitanije', {
			edit: function( props ) {
				var blockProps = useBlockProps();
				var count = props.attributes.count || 6;
				var najnovijePostIds = props.attributes.najnovijePostIds || [];
				var najcitanijePostIds = props.attributes.najcitanijePostIds || [];

				return el( element.Fragment, null,
					el( InspectorControls, null,
						el( PanelBody, { title: 'Podešavanje tabova', initialOpen: true },
							el( RangeControl, {
								label: 'Broj postova po tabu',
								value: count,
								min: 1,
								max: 12,
								onChange: function( v ) {
									var nextCount = parseInt( v, 10 ) || 1;
									props.setAttributes( {
										count: nextCount,
										najnovijePostIds: najnovijePostIds.slice( 0, nextCount ),
										najcitanijePostIds: najcitanijePostIds.slice( 0, nextCount ),
									} );
								},
							} ),
							el( HeroPostPicker, {
								title: 'Najnovije (ručni izbor)',
								selectedIds: najnovijePostIds,
								max: count,
								onChange: function( next ) {
									props.setAttributes( { najnovijePostIds: next } );
								},
							} ),
							el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
								'Ako nije izabran nijedan post, prikazuju se najnoviji po datumu.'
							),
							el( HeroPostPicker, {
								title: 'Najčitanije (ručni izbor)',
								selectedIds: najcitanijePostIds,
								max: count,
								onChange: function( next ) {
									props.setAttributes( { najcitanijePostIds: next } );
								},
							} ),
							el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
								'Ako nije izabran nijedan post, prikazuju se najnoviji po datumu.'
							)
						)
					),
					el( 'div', blockProps,
						el( SSR, {
							key:        JSON.stringify( props.attributes ),
							block:      'kompas/tabs-najnovije-najcitanije',
							attributes: props.attributes,
						} )
					)
				);
			},
			save: function() { return null; },
		} );

	/* ── PostPicker: search & select posts (array of IDs) ────── */
	var SearchControl = components.SearchControl;
	var Button        = components.Button;

		function HeroPostPicker( props ) {
			var selectedIds = Array.isArray( props.selectedIds ) ? props.selectedIds : [];
			var maxPosts    = props.max || 7;
			var title       = props.title || 'Izabrani postovi';
			var categoryId  = props.categoryId ? parseInt( props.categoryId, 10 ) : 0;
			var placeholder = props.searchPlaceholder || 'Pretraži postove...';

		var _s   = useState( '' );     var search      = _s[0]; var setSearch      = _s[1];
		var _r   = useState( [] );     var results     = _r[0]; var setResults     = _r[1];
		var _l   = useState( false );  var loading     = _l[0]; var setLoading     = _l[1];
		var _sp  = useState( [] );     var selPosts    = _sp[0]; var setSelPosts   = _sp[1];

		// Load titles for selected IDs.
		useEffect( function() {
			if ( ! selectedIds.length ) { setSelPosts( [] ); return; }
			apiFetch( { path: '/wp/v2/posts?include=' + selectedIds.join(',') + '&per_page=' + selectedIds.length + '&_fields=id,title,categories' } )
				.then( function( posts ) {
					var ordered = selectedIds.map( function( id ) {
						return posts.find( function( p ) { return p.id === id; } );
					} ).filter( Boolean );
					if ( categoryId ) {
						ordered = ordered.filter( function( post ) {
							return Array.isArray( post.categories ) && post.categories.indexOf( categoryId ) !== -1;
						} );
					}
					setSelPosts( ordered );
				} )
				.catch( function() {
					setSelPosts( [] );
				} );
		}, [ selectedIds.join(','), categoryId ] );

		// Search.
		useEffect( function() {
			if ( search.length < 2 ) { setResults( [] ); return; }
			setLoading( true );
			var t = setTimeout( function() {
				var path = '/wp/v2/posts?search=' + encodeURIComponent( search ) + '&per_page=10&_fields=id,title';
				if ( categoryId ) {
					path += '&categories=' + categoryId;
				}
				apiFetch( { path: path } )
					.then( function( p ) { setResults( p ); setLoading( false ); } )
					.catch( function() { setResults( [] ); setLoading( false ); } );
			}, 300 );
			return function() { clearTimeout( t ); };
		}, [ search, categoryId ] );

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
						title + ' (' + selPosts.length + '/' + maxPosts + '):'
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
			selectedIds.length < maxPosts && el( SearchControl, { value: search, onChange: setSearch, placeholder: placeholder } ),
			loading && el( Spinner ),
				!loading && filtered.length > 0 && el( 'div', { style: { border: '1px solid #ddd', borderRadius: '4px', maxHeight: '200px', overflowY: 'auto', background: '#fff' } },
					filtered.map( function( post ) {
						return el( Button, { key: post.id, onClick: function() { addPost(post); }, style: { display:'block', width:'100%', textAlign:'left', padding:'8px 12px', fontSize:'13px', borderBottom:'1px solid #f0f0f0', cursor:'pointer', borderRadius:0, height:'auto' } },
							el( 'span', { style: { color: '#757575', fontSize: '11px', marginRight: '6px' } }, '#' + post.id ),
							post.title.rendered
						);
					} )
				)
			);
			}

		function PagePicker( props ) {
			var selectedIds = props.selectedIds || [];
			var maxPages    = props.max || 8;
			var title       = props.title || 'Izabrane stranice';

			var _s   = useState( '' );     var search      = _s[0]; var setSearch      = _s[1];
			var _r   = useState( [] );     var results     = _r[0]; var setResults     = _r[1];
			var _l   = useState( false );  var loading     = _l[0]; var setLoading     = _l[1];
			var _sp  = useState( [] );     var selPages    = _sp[0]; var setSelPages   = _sp[1];

			useEffect( function() {
				if ( ! selectedIds.length ) { setSelPages( [] ); return; }
				apiFetch( { path: '/wp/v2/pages?include=' + selectedIds.join(',') + '&per_page=' + selectedIds.length + '&_fields=id,title' } )
					.then( function( pages ) {
						var ordered = selectedIds.map( function( id ) {
							return pages.find( function( p ) { return p.id === id; } );
						} ).filter( Boolean );
						setSelPages( ordered );
					} );
			}, [ selectedIds.join(',') ] );

			useEffect( function() {
				if ( search.length < 2 ) { setResults( [] ); return; }
				setLoading( true );
				var t = setTimeout( function() {
					apiFetch( { path: '/wp/v2/pages?search=' + encodeURIComponent( search ) + '&per_page=10&_fields=id,title' } )
						.then( function( p ) { setResults( p ); setLoading( false ); } );
				}, 300 );
				return function() { clearTimeout( t ); };
			}, [ search ] );

			function addPage( page ) {
				if ( selectedIds.length >= maxPages ) return;
				props.onChange( selectedIds.concat( [ page.id ] ) );
				setSearch( '' );
				setResults( [] );
			}
			function removePage( id ) {
				props.onChange( selectedIds.filter( function( x ) { return x !== id; } ) );
			}
			function movePage( idx, dir ) {
				var a = selectedIds.slice();
				var t = idx + dir;
				if ( t < 0 || t >= a.length ) return;
				var tmp = a[idx]; a[idx] = a[t]; a[t] = tmp;
				props.onChange( a );
			}

			var filtered = results.filter( function( p ) { return selectedIds.indexOf( p.id ) === -1; } );

			return el( 'div', null,
				selPages.length > 0 && el( 'div', { style: { marginBottom: '12px' } },
					el( 'p', { style: { fontSize: '11px', textTransform: 'uppercase', fontWeight: 600, color: '#757575', marginBottom: '8px' } },
						title + ' (' + selPages.length + '/' + maxPages + '):'
					),
					selPages.map( function( page, i ) {
						return el( 'div', { key: page.id, style: { display: 'flex', alignItems: 'center', gap: '4px', padding: '6px 8px', marginBottom: '4px', background: '#f0f0f0', borderRadius: '4px', fontSize: '12px' } },
							el( 'span', { style: { color: '#757575', fontSize: '10px', minWidth: '18px' } }, ( i + 1 ) + '.' ),
							el( 'span', { style: { flex: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }, page.title.rendered ),
							el( 'div', { style: { display: 'flex', gap: '2px', flexShrink: 0 } },
								el( Button, { icon: 'arrow-up-alt2', size: 'small', disabled: i === 0, onClick: function() { movePage( i, -1 ); }, style: { minWidth: '24px', height: '24px', padding: 0 } } ),
								el( Button, { icon: 'arrow-down-alt2', size: 'small', disabled: i === selPages.length - 1, onClick: function() { movePage( i, 1 ); }, style: { minWidth: '24px', height: '24px', padding: 0 } } ),
								el( Button, { icon: 'no-alt', size: 'small', isDestructive: true, onClick: function() { removePage( page.id ); }, style: { minWidth: '24px', height: '24px', padding: 0 } } )
							)
						);
					} )
				),
				selectedIds.length < maxPages && el( SearchControl, { value: search, onChange: setSearch, placeholder: 'Pretraži stranice...' } ),
				loading && el( Spinner ),
				!loading && filtered.length > 0 && el( 'div', { style: { border: '1px solid #ddd', borderRadius: '4px', maxHeight: '200px', overflowY: 'auto', background: '#fff' } },
					filtered.map( function( page ) {
						return el( Button, { key: page.id, onClick: function() { addPage( page ); }, style: { display: 'block', width: '100%', textAlign: 'left', padding: '8px 12px', fontSize: '13px', borderBottom: '1px solid #f0f0f0', cursor: 'pointer', borderRadius: 0, height: 'auto' } },
							el( 'span', { style: { color: '#757575', fontSize: '11px', marginRight: '6px' } }, '#' + page.id ),
							page.title.rendered
						);
					} )
				)
			);
		}

			/* ── Category Grid ──────────────────────────────────────── */
			blocks.registerBlockType( 'kompas/category-grid', {
			edit: function( props ) {
				var blockProps  = useBlockProps();
				var selectedIds = Array.isArray( props.attributes.selectedIds ) ? props.attributes.selectedIds : [];
				var postsByCategory = props.attributes.postsByCategory && typeof props.attributes.postsByCategory === 'object' ? props.attributes.postsByCategory : {};
				var perCategory = Math.max( 1, parseInt( props.attributes.postsPerCategory, 10 ) || 6 );
				var _catNames = useState( {} );
				var categoryNames = _catNames[0];
				var setCategoryNames = _catNames[1];

				function getPostsForCategory( catId ) {
					var key = String( catId );
					return Array.isArray( postsByCategory[ key ] ) ? postsByCategory[ key ] : [];
				}

				useEffect( function() {
					if ( ! selectedIds.length ) {
						setCategoryNames( {} );
						return;
					}
					apiFetch( { path: '/wp/v2/categories?include=' + selectedIds.join(',') + '&per_page=' + selectedIds.length + '&_fields=id,name' } )
						.then( function( categories ) {
							var map = {};
							categories.forEach( function( cat ) {
								map[ cat.id ] = cat.name;
							} );
							setCategoryNames( map );
						} )
						.catch( function() {
							setCategoryNames( {} );
						} );
				}, [ selectedIds.join(',') ] );

				return el( element.Fragment, null,
					el( InspectorControls, null,
						el( TermPicker, {
						selectedIds: selectedIds,
						restBase:    'categories',
						label:       'Kategorije za grid',
						onChange:     function( next ) {
							var nextMap = {};
							next.forEach( function( catId ) {
								var key = String( catId );
								if ( Array.isArray( postsByCategory[ key ] ) ) {
									nextMap[ key ] = postsByCategory[ key ];
								}
							} );
							props.setAttributes( {
								selectedIds: next,
								postsByCategory: nextMap,
							} );
							},
						} ),
						selectedIds.map( function( catId ) {
							var catPosts = getPostsForCategory( catId );
							var title = categoryNames[ catId ] ? categoryNames[ catId ] : ( 'Kategorija #' + catId );

							return el( PanelBody, {
								key: 'cat-posts-' + catId,
								title: 'Postovi: ' + title,
								initialOpen: true,
							},
								el( HeroPostPicker, {
									title: 'Ručno izabrani postovi',
									selectedIds: catPosts,
									max: perCategory,
									categoryId: catId,
									searchPlaceholder: 'Pretraži postove u kategoriji...',
									onChange: function( nextPosts ) {
										var nextMap = Object.assign( {}, postsByCategory );
										var key = String( catId );
										if ( nextPosts.length ) {
											nextMap[ key ] = nextPosts;
										} else {
											delete nextMap[ key ];
										}
										props.setAttributes( { postsByCategory: nextMap } );
									},
								} ),
								el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
									'Ako ne izabereš postove, prikazuju se najnoviji iz ove kategorije.'
								)
							);
						} )
					),
					el( 'div', blockProps,
						el( SSR, {
							key:        JSON.stringify( props.attributes ),
							block:      'kompas/category-grid',
							attributes: props.attributes,
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
				var imageUrl   = props.attributes.imageUrl || '';
				var imageId    = props.attributes.imageId || 0;

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
							} ),
							el( MediaUploadCheck, null,
								el( MediaUpload, {
									onSelect: function( media ) {
										props.setAttributes( {
											imageId: media && media.id ? media.id : 0,
											imageUrl: media && media.url ? media.url : '',
											imageAlt: media && media.alt ? media.alt : '',
										} );
									},
									allowedTypes: [ 'image' ],
									value: imageId,
									render: function( obj ) {
										return el( Button, {
											variant: imageUrl ? 'secondary' : 'primary',
											onClick: obj.open,
										}, imageUrl ? 'Promeni sliku' : 'Odaberi sliku' );
									},
								} )
							),
							imageUrl && el( Button, {
								isDestructive: true,
								onClick: function() {
									props.setAttributes( {
										imageId: 0,
										imageUrl: '',
										imageAlt: '',
									} );
								},
								style: { marginTop: '8px' },
							}, 'Ukloni sliku' )
						)
					),
					el( 'div', blockProps,
						el( SSR, {
							key:        JSON.stringify( props.attributes ),
							block:      'kompas/banner',
							attributes: props.attributes,
						} )
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
						key:        JSON.stringify( props.attributes ),
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
						key:        JSON.stringify( props.attributes ),
						block:      'kompas/rec-urednika',
						attributes: props.attributes,
					} )
				)
			);
		},
		save: function() { return null; },
	} );

	/* ── Homepage Hero ──────────────────────────────────────── */
	blocks.registerBlockType( 'kompas/homepage-hero', {
		edit: function( props ) {
			var blockProps   = useBlockProps();
			var heroPostIds  = props.attributes.heroPostIds || [];

			return el( element.Fragment, null,
				el( InspectorControls, null,
					el( PanelBody, { title: 'Hero postovi (6)', initialOpen: true },
						el( HeroPostPicker, {
							selectedIds: heroPostIds,
							max: 6,
							onChange: function( next ) {
								props.setAttributes( { heroPostIds: next } );
							}
						} ),
						el( 'p', { style: { fontSize: '11px', color: '#757575', marginTop: '8px' } },
							'1 velika vest (centar) | 3 sidebar (levo) | 2 horizontalne (centar dole)'
						)
					)
				),
				el( 'div', blockProps,
					el( SSR, {
						key:        JSON.stringify( props.attributes ),
						block:      'kompas/homepage-hero',
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
