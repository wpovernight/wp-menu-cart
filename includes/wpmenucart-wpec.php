<?php
if ( ! class_exists( 'WPMenuCart_WPEC' ) ) {
	class WPMenuCart_WPEC {
		/**
		 * Construct.
		 */
		public function __construct() {
		}
	
		public function menu_item() {
			global $wpsc_cart, $options;
			$menu_item = array(
				'cart_url'				=> esc_url( get_option( 'shopping_cart_url' ) ),
				'shop_page_url'			=> esc_url( get_option( 'product_list_url' ) ),
				'cart_contents_count'	=> wpsc_cart_item_count(),
				'cart_total'			=> wpsc_cart_total_widget( false, false ,false ),
			);
		
			return apply_filters( 'wpmenucart_menu_item_data', $menu_item );
		}
	}
}