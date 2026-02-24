/**
 * Kompas – inline format "Не преводи"
 *
 * Registruje dugme u Gutenberg toolbaru (pojavljuje se pri selekciji teksta).
 * Označeni tekst se obmotava u <span class="kompas-neprevedi"> i
 * script-toggle.js ga preskače – ostaje u pismu u kojem je ukucan.
 */
( function() {
	'use strict';

	var registerFormatType  = wp.richText.registerFormatType;
	var toggleFormat        = wp.richText.toggleFormat;
	var RichTextToolbarButton = wp.blockEditor.RichTextToolbarButton;
	var el = wp.element.createElement;

	/* Ikonica – jednostavan SVG "ЋИР" */
	var NePrevediIcon = el(
		'svg',
		{ width: 24, height: 24, viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg', fill: 'currentColor', 'aria-hidden': true, focusable: false },
		el( 'text', { x: 3, y: 17, fontSize: '11', fontWeight: '700', fontFamily: 'sans-serif', letterSpacing: '-0.5' }, 'НЛ' ),
		el( 'line', { x1: 3, y1: 20, x2: 21, y2: 20, stroke: 'currentColor', strokeWidth: 1.5 } )
	);

	registerFormatType( 'kompas/neprevedi', {
		title:     'Не преводи (задржи писмо)',
		tagName:   'span',
		className: 'kompas-neprevedi',

		edit: function( props ) {
			return el( RichTextToolbarButton, {
				icon:     NePrevediIcon,
				title:    'Не преводи',
				isActive: props.isActive,
				onClick:  function() {
					props.onChange(
						toggleFormat( props.value, { type: 'kompas/neprevedi' } )
					);
				},
				shortcutType:        'primary',
				shortcutCharacter:   'l',
			} );
		},
	} );
} )();
