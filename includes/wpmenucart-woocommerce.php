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

			// make sure cart is loaded! https://wordpress.org/support/topic/activation-breaks-customise?replies=10#post-7908988
			if (empty($woocommerce->cart)) {
				$woocommerce->cart = new WC_Cart();
			}
			
			// $woocommerce->cart->get_cart_total() is not a display function,
			// so we add tax if cart prices are set to display incl. tax
			// see https://github.com/woothemes/woocommerce/issues/6701
			if ( $woocommerce->cart->display_cart_ex_tax ) {
				$cart_contents_total = wc_price( $woocommerce->cart->cart_contents_total );
			} else {
				$cart_contents_total = wc_price( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total );
			}
			$cart_contents_total = apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );


			$menu_item = array(
				'cart_url'				=> $this->cart_url(),
				'shop_page_url'			=> $this->shop_url(),
				'cart_contents_count'	=> $woocommerce->cart->get_cart_contents_count(),
				'cart_total'			=> strip_tags( $cart_contents_total ),
			);
		
			return $menu_item;
		}

		public function cart_url() {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.5.2', '>=' ) ) {
				return wc_get_cart_url();
			} else {
				$cart_page_id = woocommerce_get_page_id('cart');
				if ( $cart_page_id ) {
					return apply_filters( 'woocommerce_get_cart_url', get_permalink( $cart_page_id ) );
				} else {
					return '';
				}
			}
		}

		public function shop_url() {
			if ( version_compare( WOOCOMMERCE_VERSION, '2.5.2', '>=' ) ) {
				return wc_get_page_permalink( 'shop' );
			} else {
				return get_permalink( woocommerce_get_page_id( 'shop' ) );
			}
		}
	}
}