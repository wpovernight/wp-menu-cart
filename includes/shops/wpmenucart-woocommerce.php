<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WPMenuCart_WooCommerce' ) ) {

	class WPMenuCart_WooCommerce {

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
			$this->maybe_load_cart(); // make sure cart is loaded! https://wordpress.org/support/topic/activation-breaks-customise?replies=10#post-7908988

			$menu_item = array(
				'cart_url'            => $this->get_cart_url(),
				'shop_page_url'       => $this->get_shop_url(),
				'checkout_url'        => $this->get_checkout_url(),
				'cart_total'          => $this->get_cart_total(),
				'cart_contents_count' => $this->get_cart_contents_count(),
			);

			return apply_filters( 'wpmenucart_menu_item_data', $menu_item );
		}

		/**
		 * Maybe load cart.
		 *
		 * @return void
		 */
		public function maybe_load_cart(): void {
			if ( function_exists( 'WC' ) ) {
				if ( function_exists( 'wc_load_cart' ) && did_action( 'before_woocommerce_init' ) ) {
					wc_load_cart(); // loads session, customer & cart - WC 3.6.4+ 
				} else {
					if ( empty( WC()->cart ) ) {
						WC()->cart = new WC_Cart();
					}
				}
				WC()->cart->get_cart(); // force cart contents refresh
			} else {
				global $woocommerce;
				if ( empty( $woocommerce->cart ) ) {
					$woocommerce->cart = new WC_Cart();
				}
			}
		}

		/**
		 * Get the cart URL.
		 *
		 * Taking into account the changes made to `wc_get_cart_url()` in WooCommerce version 9.3.0,
		 * the `wc_get_cart_url()` may return the current URL during AJAX requests,
		 * which could result in incorrect URLs like `/?wc-ajax=add_to_cart`.
		 *
		 * To ensure the correct cart page URL is returned, the `wc_get_page_permalink( 'cart' )`
		 * has been used instead, with a filter to allow further customizations.
		 *
		 * @return string The filtered cart URL.
		 */
		public function get_cart_url(): string {
			$wc_cart_url = apply_filters( 'woocommerce_get_cart_url', wc_get_page_permalink( 'cart' ) );
			return apply_filters( 'wpmenucart_cart_url', $wc_cart_url, $this );
		}

		/**
		 * Get the shop page URL.
		 *
		 * @return string
		 */
		public function get_shop_url(): string {
			return wc_get_page_permalink( 'shop' );
		}

		/**
		 * Get the WooCommerce checkout URL.
		 *
		 * @return string
		 */
		public function get_checkout_url(): string {
			return wc_get_page_permalink( 'checkout' );
		}

		/**
		 * Get the wc format cart total.
		 *
		 * @return string
		 */
		public function get_cart_total(): string {
			$cart_contents_total = wc_price( 0 );
			
			if ( ! WC()->cart ) {
				return $cart_contents_total;
			}
			
			WC()->cart->calculate_totals();

			$total_price_type = WPO_Menu_Cart()->main_settings['total_price_type'] ?: '';
			
			if ( 'subtotal' === $total_price_type ) {
				if ( WC()->cart->display_prices_including_tax() ) {
					$cart_contents_total = wc_price( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() );
				} else {
					$cart_contents_total = wc_price( WC()->cart->get_subtotal() );
				}
			} elseif ( 'checkout_total' === $total_price_type ) {
				$cart_contents_total = wc_price( WC()->cart->get_total('edit') );
			} else {
				if ( WC()->cart->display_prices_including_tax() ) {
					$cart_contents_total = wc_price( WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() );
				} else {
					$cart_contents_total = wc_price( WC()->cart->get_cart_contents_total() );
				}
			}
			
			return $cart_contents_total;
		}

		/**
		 * Get the cart content count.
		 *
		 * @return int
		 */
		public function get_cart_contents_count(): int {
			if ( function_exists( 'WC' ) ) {
				return WC()->cart->get_cart_contents_count();
			} else {
				return $GLOBALS['woocommerce']->cart->get_cart_contents_count();
			}
		}

	}

}