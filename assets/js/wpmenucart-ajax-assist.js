/* This script is intended for sites with server side caching enabled - normally the classes in the menu would follow the cart state */
jQuery( function( $ ) {
	/* Cart Hiding */
	if ( typeof wpmenucart_ajax_assist.shop_plugin !== 'undefined' && wpmenucart_ajax_assist.shop_plugin.toLowerCase() == 'woocommerce' ) {
		// update on page load
		wpmenucart_update_menu_classes();
		// update when cart is updated
		$( document.body ).on( 'adding_to_cart added_to_cart updated_wc_div', wpmenucart_update_menu_classes );
	}

	function wpmenucart_update_menu_classes() {
		const items_in_cart = Cookies.get( 'woocommerce_items_in_cart' );

		if ( items_in_cart > 0 ) {
			$( '.empty-wpmenucart' ).removeClass( 'empty-wpmenucart' );
		} else if ( ! ( wpmenucart_ajax_assist.always_display ) ) {
			$( '.wpmenucartli' ).addClass( 'empty-wpmenucart' );
			$( '.wpmenucart-shortcode' ).addClass( 'empty-wpmenucart' );
		}

		$( document ).trigger( 'wpmenucart_menu_classes_updated', [ items_in_cart ] );
	}
} );