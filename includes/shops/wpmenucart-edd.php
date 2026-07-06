<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WPMenuCart_EDD' ) ) {

	class WPMenuCart_EDD {

		/**
		 * Construct.
		 */
		public function __construct() {}

		/**
		 * Menu item data.
		 *
		 * @return array
		 */
		public function menu_item(): array {
			$menu_item = array(
				'cart_url'            => $this->get_cart_url(),
				'shop_page_url'       => $this->get_shop_url(),
				'checkout_url'        => $this->get_checkout_url(),
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

		/**
		 * Get the EDD checkout URL.
		 *
		 * @return string
		 */
		public function get_checkout_url(): string {
			return edd_get_checkout_uri();
		}

		/**
		 * Submenu items data.
		 * 
		 * @return array
		 */
		public function submenu_items(): array {
			$cart_items    = edd_get_cart_contents();
			$submenu_items = array();

			if ( ! empty( $cart_items ) && count( $cart_items ) > 0 ) {
				foreach ( $cart_items as $key => $item ) {
					$item_thumbnail = get_the_post_thumbnail( $item['id'], apply_filters( 'edd_checkout_image_size', array( 25,25 ) ) );
					$item_name      = function_exists('edd_get_cart_item_name') ? edd_get_cart_item_name( $item ) : get_the_title( $item['id'] );
					$item_name      = apply_filters( 'wpmenucart_submenu_item_name', $item_name, $item );
					$item_quantity  = edd_get_cart_item_quantity( $item['id'], $item['options'] );
					$item_price     = edd_cart_item_price( $item['id'], $item['options'] );
				
					// Item permalink if product visible
					$item_permalink = esc_url( get_permalink( $item['id'] ) );
		
					$submenu_items[] = array(
						'item_thumbnail' => $item_thumbnail,
						'item_name'      => $item_name,
						'item_quantity'  => $item_quantity,
						'item_price'     => $item_price,
						'item_permalink' => $item_permalink,
						'cart_item'      => $item,
					);
				}
			} else {
				$submenu_items = array();
			}

			return apply_filters( 'wpmenucart_submenu_items_data', $submenu_items );
		}

	}

}