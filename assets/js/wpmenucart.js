/* 
 * JS for WooCommerce and EDD
 * 
 * AJAX not working for you?
 * You can use our custom 'wpmenucart_update_cart_ajax' handle to trigger a refresh
 * with a custom script added to your site (in a child theme or using "Code Snippets")
 * Here's an example:
 * 
 * jQuery( function( $ ) {
 * 	$(document).on('click', '.YOURCLASS', function(){
 * 		$(document).trigger('wpmenucart_update_cart_ajax');
 * 	});
 * });
 */

jQuery( function( $ ) {
	let wpmenucart_ajax_timer;
	let buttons = [
		".edd-add-to-cart",
		"div.cartopt p label.update input#update",
		".add_to_cart_button",
		".woocommerce-cart input.minus",
		".cart_item a.remove",
		"#order_review .opc_cart_item a.remove",
		".woocommerce-cart input.plus",
		".single_add_to_cart_button",
		".emptycart"
	];

	let inputs = [
		"input.edd-item-quantity"
	];

	$(document.body).on('click', buttons.join(','), function(){
		WPMenucart_Timeout();
	});

	$(document.body).on('change', inputs.join(','), function(){
		WPMenucart_Timeout();
	});

	// allow triggering refresh with a custom handle
	$(document).on('wpmenucart_update_cart_ajax', function( event ) {
		WPMenucart_Timeout();
	});
		
	function WPMenucart_Timeout() {
		clearTimeout( wpmenucart_ajax_timer );
		wpmenucart_ajax_timer = setTimeout( WPMenucart_Load_AJAX, 1000);
	}

	function WPMenucart_Load_AJAX() {
		let data = {
			security:	wpmenucart_ajax.nonce,
			action:		"wpmenucart_ajax",
		};

		xhr = $.ajax({
			type:		'POST',
			url:		wpmenucart_ajax.ajaxurl,
			data:		data,
			success:	function( response ) {
				$('.wpmenucartli').html( response );
			}
		});
	}
});