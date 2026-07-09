jQuery( function( $ ) {

	let wpmenucart_ajax_timer;

	function WPMenucart_Load_AJAX() {
		let data = {
			security: wpmenucart_cart_ajax.nonce,
			action:   wpmenucart_cart_ajax.action,
		};

		xhr = $.ajax( {
			type:    'POST',
			url:     wpmenucart_cart_ajax.ajaxurl,
			data:    data,
			success: function( response ) {
				if ( typeof response !== 'object' || response === null || ! response.data ) {
					return;
				}

				if ( response.data.menu_cart ) {
					$( '.wpmenucartli' ).html( response.data.menu_cart );
				}

				if ( $( '.wpmenucart-slideout' ).length && response.data.mini_cart_slideout ) {
					$( '.wpmenucart-slideout' ).replaceWith( response.data.mini_cart_slideout );
				}

				$( document ).trigger( 'wpmenucart_ajax_loaded', [ response ] );
			}
		} );
	}

	wp.hooks.addAction(
		'experimental__woocommerce_blocks-cart-set-item-quantity',
		'wpmenucart-cart',
		function() {
			clearTimeout( wpmenucart_ajax_timer );
			wpmenucart_ajax_timer = setTimeout( WPMenucart_Load_AJAX, 2000 );
		}
	);

	wp.hooks.addAction(
		'experimental__woocommerce_blocks-cart-remove-item',
		'wpmenucart-cart',
		function() {
			clearTimeout( wpmenucart_ajax_timer );
			wpmenucart_ajax_timer = setTimeout( WPMenucart_Load_AJAX, 2000 );
		}
	);

	wp.hooks.addAction(
		'experimental__woocommerce_blocks-cart-add-item',
		'wpmenucart-cart',
		function() {
			clearTimeout( wpmenucart_ajax_timer );
			wpmenucart_ajax_timer = setTimeout( WPMenucart_Load_AJAX, 2000 );
		}
	);

	$( '#wc-block-components-totals-coupon__form' ).submit( function() {
		clearTimeout( wpmenucart_ajax_timer );
		wpmenucart_ajax_timer = setTimeout( WPMenucart_Load_AJAX, 2000 );
	} );

	const { registerCheckoutFilters } = window.wc.blocksCheckout;
	registerCheckoutFilters( 'wpmenucart-checkout-filter', {
		showApplyCouponNotice: ( value, extensions, { couponCode } ) => {
			WPMenucart_Load_AJAX();
			return value;
		},
		showRemoveCouponNotice: ( value, _, { couponCode } ) => {
			WPMenucart_Load_AJAX();
			return value;
		},
	} );

} );
