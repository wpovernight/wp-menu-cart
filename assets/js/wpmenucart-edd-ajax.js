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
				if ( typeof ajaxResponse !== 'object' || ajaxResponse === null || ! ajaxResponse.data ) {
					return;
				}

				if ( ajaxResponse.data.menu_cart ) {
					$( '.wpmenucartli' ).html( ajaxResponse.data.menu_cart );
				}

				if ( $( '.wpmenucart-slideout' ).length && ajaxResponse.data.mini_cart_slideout ) {
					$( '.wpmenucart-slideout' ).replaceWith( ajaxResponse.data.mini_cart_slideout );
				}

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