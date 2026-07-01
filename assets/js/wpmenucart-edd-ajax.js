jQuery( function( $ ) {
	// reload when item is added or removed
	$( document.body ).on( 'edd_cart_item_removed edd_cart_item_added', function( event, response ) {
		var data = {
			security: wpmenucart_ajax.nonce,
			action:   wpmenucart_ajax.action,
		};

		xhr = $.ajax({
			type:    'POST',
			url:     wpmenucart_ajax.ajaxurl,
			data:    data,
			success: function( ajaxResponse ) {
				$( '.wpmenucartli' ).html( ajaxResponse );
				$( 'div.wpmenucart-shortcode span.reload_shortcode' ).html( ajaxResponse );
				$( document ).trigger( 'wpmenucart_ajax_loaded', [ ajaxResponse ] );
			}
		});

		// update empty class for menu item
		if ( 'cart_quantity' in response && parseInt( response.cart_quantity ) > 0 ) {
			$( '.empty-wpmenucart' ).removeClass( 'empty-wpmenucart' );
		} else if ( ! ( wpmenucart_ajax.always_display ) ) {
			$( '.wpmenucartli' ).addClass( 'empty-wpmenucart' );
			$( '.wpmenucart-shortcode' ).addClass( 'empty-wpmenucart' );
		}

		$( document ).trigger( 'wpmenucart_edd_cart_updated', [ response ] );
	});
});