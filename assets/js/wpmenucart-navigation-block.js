( function( blocks, element, i18n, serverSideRender ) {

	let __                = i18n.__;
	let el                = element.createElement;
	let registerBlockType = blocks.registerBlockType;
	let createBlock       = blocks.createBlock;

	// svg icon
	let iconCart = el(
		'svg',
		{
			width:  20,
			height: 20,
		},
		el( 'path',
			{
				d: "M15.55 13c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.37-.66-.11-1.48-.87-1.48H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.44C4.52 15.37 5.48 17 7 17h12v-2H7l1.1-2h7.45zM6.16 6h12.15l-2.76 5H8.53L6.16 6zM7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"
			}
		)
	);

	// navigation block
	let navigationBlockSettings = {
		title:    __( 'Cart', 'wp-menu-cart' ),
		icon:     iconCart,
		parent:   [ 'core/navigation' ],
		keywords: [ 'cart' ],
		support:  {
			html: true,
		},
		transforms: {
			from: [
				{
					type:      'block',
					blocks:    [ 'core/navigation-link' ],
					transform: () => createBlock( 'wpo/wpmenucart-navigation' )
				}
			]
		},
		edit: function( props ) {
			return el(
				serverSideRender,
				{
					block:      'wpo/wpmenucart-navigation',
					className:  'wpmenucart-navigation-block',
					attributes: props.attributes,
				}
			);
		},
		save: function() {
			// return null to render from php
			return null;
		},
	}
	registerBlockType( 'wpo/wpmenucart-navigation', navigationBlockSettings );

} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender,
);