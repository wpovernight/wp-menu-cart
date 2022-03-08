( function( blocks, element, $ ) {

	const el = element.createElement;

	const { registerBlockType } = blocks;

	const iconCart = el(
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

	function createBlock( $menuCartItem ) {
		let children = [];
		$.each( $menuCartItem.children(), function( i, v ) {
			let child     = $( v );
			let childType = child.prop( 'tagName' ).toLowerCase();

			if ( childType == 'i' ) {
				children.push( el(
					childType,
					{
						'className':  child.attr( 'class' ),
						'role':       child.attr( 'role' ),
						'aria-label': child.attr( 'aria-label' ),
						'key':        i,
					}
				) );
			} else if ( childType == 'span' ) {
				children.push( el(
					childType,
					{
						'className': child.attr( 'class' ),
						'key':       i,
					},
					child.text()
				) );
			}
		} );

		let blockElem = el(
			$menuCartItem.prop( 'tagName' ).toLowerCase(),
			{
				'className': $menuCartItem.attr( 'class' ),
				'href':      $menuCartItem.attr( 'href' ),
				'title':     $menuCartItem.attr( 'title' ),
			},
			children
		);

		registerBlockType( 'wpo/wpmenucart', {
			title:    'Cart',
			icon:     iconCart,
			category: 'widgets',
			keywords: [ 'cart' ],
			edit:     function( props ) {
				return (
					blockElem
				);
			},
			save:     function( props ) {
				return (
					blockElem
				);
			},
		} );
	}

	function loadMenuCartBlock() {
		let data = {
			security: wpmenucart_block.nonce,
			action:	  'wpmenucart_ajax',
		};

		xhr = $.ajax( {
			type:    'POST',
			url:     wpmenucart_block.ajaxurl,
			data:    data,
			success: function( response ) {
				createBlock( $( response ) );
			}
		} );
	}

	// load
	loadMenuCartBlock();

} )(
	window.wp.blocks,
	window.wp.element,
	jQuery,
);