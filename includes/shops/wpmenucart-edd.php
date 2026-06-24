<?php
if ( ! class_exists( 'WPMenuCart_EDD' ) ) {
	class WPMenuCart_EDD {	
		public function menu_item() {
			$menu_item = array(
				'cart_url'            => $this->get_cart_url(),
				'shop_page_url'       => $this->get_shop_url(),
				'cart_contents_count' => edd_get_cart_quantity(),
				'cart_total'          => edd_currency_filter( edd_format_amount( edd_get_cart_total() ) ),
			);

			return apply_filters( 'wpmenucart_menu_item_data', $menu_item );
		}

		/**
		 * Get the cart URL.
		 *
		 * @return string
		 */
		public function get_cart_url(): string {
			return apply_filters( 'wpmenucart_cart_url', edd_get_checkout_uri(), $this );
		}

		/**
		 * Get the shop URL.
		 *
		 * @return string
		 */
		public function get_shop_url(): string {
			return get_home_url();
		}
	}
}