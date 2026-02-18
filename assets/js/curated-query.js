( function () {
	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var addFilter = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SearchControl = wp.components.SearchControl;
	var Button = wp.components.Button;
	var Spinner = wp.components.Spinner;
	var apiFetch = wp.apiFetch;

	// Extend core/query block attributes to include postIn.
	addFilter(
		'blocks.registerBlockType',
		'kompas/curated-query-attrs',
		function ( settings, name ) {
			if ( name !== 'core/query' ) {
				return settings;
			}
			return Object.assign( {}, settings, {
				attributes: Object.assign( {}, settings.attributes, {
					query: Object.assign( {}, settings.attributes.query, {
						default: Object.assign(
							{},
							settings.attributes.query.default || {},
							{ postIn: '' }
						),
					} ),
				} ),
			} );
		}
	);

	/**
	 * PostPicker component — search dropdown for selecting posts.
	 */
	function PostPicker( props ) {
		var selectedIds = props.value
			? props.value
					.split( ',' )
					.map( function ( s ) {
						return parseInt( s.trim(), 10 );
					} )
					.filter( function ( n ) {
						return ! isNaN( n ) && n > 0;
					} )
			: [];

		var _searchState = useState( '' );
		var search = _searchState[0];
		var setSearch = _searchState[1];

		var _resultsState = useState( [] );
		var results = _resultsState[0];
		var setResults = _resultsState[1];

		var _loadingState = useState( false );
		var loading = _loadingState[0];
		var setLoading = _loadingState[1];

		var _selectedPostsState = useState( [] );
		var selectedPosts = _selectedPostsState[0];
		var setSelectedPosts = _selectedPostsState[1];

		// Load selected post titles on mount / when IDs change.
		useEffect(
			function () {
				if ( selectedIds.length === 0 ) {
					setSelectedPosts( [] );
					return;
				}
				apiFetch( {
					path:
						'/wp/v2/posts?include=' +
						selectedIds.join( ',' ) +
						'&per_page=' +
						selectedIds.length +
						'&_fields=id,title',
				} ).then( function ( posts ) {
					// Maintain the order of selectedIds.
					var ordered = selectedIds
						.map( function ( id ) {
							return posts.find( function ( p ) {
								return p.id === id;
							} );
						} )
						.filter( Boolean );
					setSelectedPosts( ordered );
				} );
			},
			[ props.value ]
		);

		// Search posts via REST.
		useEffect(
			function () {
				if ( search.length < 2 ) {
					setResults( [] );
					return;
				}
				setLoading( true );
				var timeoutId = setTimeout( function () {
					apiFetch( {
						path:
							'/wp/v2/posts?search=' +
							encodeURIComponent( search ) +
							'&per_page=10&_fields=id,title',
					} ).then( function ( posts ) {
						setResults( posts );
						setLoading( false );
					} );
				}, 300 );
				return function () {
					clearTimeout( timeoutId );
				};
			},
			[ search ]
		);

		function addPost( post ) {
			var newIds = selectedIds.concat( [ post.id ] );
			props.onChange( newIds.join( ', ' ) );
			setSearch( '' );
			setResults( [] );
		}

		function removePost( postId ) {
			var newIds = selectedIds.filter( function ( id ) {
				return id !== postId;
			} );
			props.onChange( newIds.length ? newIds.join( ', ' ) : '' );
		}

		function movePost( index, direction ) {
			var newIds = selectedIds.slice();
			var target = index + direction;
			if ( target < 0 || target >= newIds.length ) return;
			var temp = newIds[ index ];
			newIds[ index ] = newIds[ target ];
			newIds[ target ] = temp;
			props.onChange( newIds.join( ', ' ) );
		}

		// Filter out already-selected from search results.
		var filteredResults = results.filter( function ( post ) {
			return selectedIds.indexOf( post.id ) === -1;
		} );

		return el(
			'div',
			{ className: 'kompas-post-picker' },

			// Selected posts list.
			selectedPosts.length > 0 &&
				el(
					'div',
					{
						style: {
							marginBottom: '12px',
						},
					},
					el(
						'p',
						{
							style: {
								fontSize: '11px',
								textTransform: 'uppercase',
								fontWeight: 600,
								color: '#757575',
								marginBottom: '8px',
							},
						},
						'Izabrani postovi (' + selectedPosts.length + '):'
					),
					selectedPosts.map( function ( post, index ) {
						return el(
							'div',
							{
								key: post.id,
								style: {
									display: 'flex',
									alignItems: 'center',
									gap: '4px',
									padding: '6px 8px',
									marginBottom: '4px',
									background: '#f0f0f0',
									borderRadius: '4px',
									fontSize: '12px',
								},
							},
							el(
								'span',
								{
									style: {
										color: '#757575',
										fontSize: '10px',
										minWidth: '18px',
									},
								},
								index + 1 + '.'
							),
							el(
								'span',
								{
									style: {
										flex: 1,
										overflow: 'hidden',
										textOverflow: 'ellipsis',
										whiteSpace: 'nowrap',
									},
								},
								post.title.rendered
							),
							el(
								'div',
								{
									style: {
										display: 'flex',
										gap: '2px',
										flexShrink: 0,
									},
								},
								el(
									Button,
									{
										icon: 'arrow-up-alt2',
										size: 'small',
										label: 'Gore',
										disabled: index === 0,
										onClick: function () {
											movePost( index, -1 );
										},
										style: { minWidth: '24px', height: '24px', padding: 0 },
									}
								),
								el(
									Button,
									{
										icon: 'arrow-down-alt2',
										size: 'small',
										label: 'Dole',
										disabled: index === selectedPosts.length - 1,
										onClick: function () {
											movePost( index, 1 );
										},
										style: { minWidth: '24px', height: '24px', padding: 0 },
									}
								),
								el(
									Button,
									{
										icon: 'no-alt',
										size: 'small',
										isDestructive: true,
										label: 'Ukloni',
										onClick: function () {
											removePost( post.id );
										},
										style: { minWidth: '24px', height: '24px', padding: 0 },
									}
								)
							)
						);
					} )
				),

			// Search input.
			el( SearchControl, {
				label: 'Pretraži postove',
				value: search,
				onChange: setSearch,
				placeholder: 'Pretraži po naslovu...',
			} ),

			// Loading spinner.
			loading && el( Spinner, null ),

			// Search results dropdown.
			! loading &&
				filteredResults.length > 0 &&
				el(
					'div',
					{
						style: {
							border: '1px solid #ddd',
							borderRadius: '4px',
							maxHeight: '200px',
							overflowY: 'auto',
							background: '#fff',
						},
					},
					filteredResults.map( function ( post ) {
						return el(
							Button,
							{
								key: post.id,
								onClick: function () {
									addPost( post );
								},
								style: {
									display: 'block',
									width: '100%',
									textAlign: 'left',
									padding: '8px 12px',
									fontSize: '13px',
									borderBottom: '1px solid #f0f0f0',
									cursor: 'pointer',
									borderRadius: 0,
									height: 'auto',
								},
							},
							el(
								'span',
								{ style: { color: '#757575', fontSize: '11px', marginRight: '6px' } },
								'#' + post.id
							),
							post.title.rendered
						);
					} )
				),

			// Help text.
			el(
				'p',
				{
					style: {
						fontSize: '11px',
						color: '#757575',
						marginTop: '8px',
					},
				},
				'Pretražite i izaberite postove. Ako je lista prazna, koristi se podrazumevani redosled po datumu.'
			)
		);
	}

	// Add sidebar control with PostPicker.
	var withCuratedQueryControls = createHigherOrderComponent(
		function ( BlockEdit ) {
			return function ( props ) {
				if ( props.name !== 'core/query' ) {
					return el( BlockEdit, props );
				}

				var query = props.attributes.query || {};
				var postIn = query.postIn || '';

				return el(
					Fragment,
					{},
					el( BlockEdit, props ),
					el(
						InspectorControls,
						{},
						el(
							PanelBody,
							{
								title: 'Ručni izbor postova',
								initialOpen: true,
							},
							el( PostPicker, {
								value: postIn,
								onChange: function ( value ) {
									props.setAttributes( {
										query: Object.assign( {}, query, {
											postIn: value,
										} ),
									} );
								},
							} )
						)
					)
				);
			};
		},
		'withCuratedQueryControls'
	);

	addFilter(
		'editor.BlockEdit',
		'kompas/curated-query-controls',
		withCuratedQueryControls
	);
} )();
