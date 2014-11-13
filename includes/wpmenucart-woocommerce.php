<?php
if ( ! class_exists( 'WPMenuCart_WooCommerce' ) ) {
	class WPMenuCart_WooCommerce {
		/**
		 * Construct.
		 */
		public function __construct() {
		}
	
		public function menu_item() {
			global $woocommerce;
			
			// $woocommerce->cart->get_cart_total() is not a display function,
			// so we add tax if cart prices are set to display incl. tax
			// see https://github.com/woothemes/woocommerce/issues/6701
			if ( $woocommerce->cart->display_cart_ex_tax ) {
				$cart_contents_total = woocommerce_price( $woocommerce->cart->cart_contents_total );
			} else {
				$cart_contents_total = woocommerce_price( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total );
			}
			$cart_contents_total = apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );


			$menu_item = array(
				'cart_url'				=> $woocommerce->cart->get_cart_url(),
				'shop_page_url'			=> get_permalink( woocommerce_get_page_id( 'shop' ) ),
				'cart_contents_count'	=> $woocommerce->cart->get_cart_contents_count(),
				'cart_total'			=> strip_tags( $cart_contents_total ),
			);
		
			return $menu_item;
		}
	}
}