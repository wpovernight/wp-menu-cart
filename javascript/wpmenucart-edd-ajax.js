jQuery( function( $ ) {
	// reload when item is added or removed
	$( document.body ).on( 'edd_cart_item_removed edd_cart_item_added', function( event, response ) {
		var data = {
			security:	wpmenucart_ajax.nonce,
			action:		"wpmenucart_ajax",
		};

		xhr = $.ajax({
			type:		'POST',
			url:		wpmenucart_ajax.ajaxurl,
			data:		data,
			success:	function( response ) {
				$('.wpmenucartli').html( response );
				$('div.wpmenucart-shortcode span.reload_shortcode').html( response );
			}
		});

		// update empty class for menu item
		if ( 'cart_quantity' in response && parseInt( response.cart_quantity ) > 0 ) {
			$('.empty-wpmenucart').removeClass('empty-wpmenucart');
		} else if ( !(wpmenucart_ajax.always_display) ) {
			$('.wpmenucartli').addClass('empty-wpmenucart');
			$('.wpmenucart-shortcode').addClass('empty-wpmenucart');
		}
	});
});