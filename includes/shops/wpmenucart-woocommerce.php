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

			$total_price_type = WPO_Menu_Cart()->main_settings['total_price_type'] ?? '';
			
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

		/**
		 * Submenu items data.
		 * 
		 * @return array
		 */
		public function submenu_items(): array {
			global $woocommerce;
			// make sure cart and session loaded! https://wordpress.org/support/topic/activation-breaks-customise?replies=10#post-7908988
			if ( empty( $woocommerce->session ) ) {
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				$woocommerce->session = new $session_class();
			}

			if ( empty( $woocommerce->cart ) ) {
				$woocommerce->cart = new WC_Cart();
			}

			$cart          = $woocommerce->cart->get_cart();
			$submenu_items = array();

			if ( count( $cart ) > 0 ) {
				foreach ( $cart as $cart_item_key => $cart_item ) {
					$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

					if ( isset( $cart_item['product_id'] ) ) {
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
					} else {
						$product_id = method_exists( $_product, 'get_id' ) ? $_product->get_id() : $_product->id;
					}

					// item visibility filter for third party plugins compatibility
					if ( false === apply_filters( 'wpo_wpmenucart_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						continue;
					}

					if ( $_product->exists() && $cart_item['quantity'] > 0 ) {
						$item_quantity  = apply_filters( 'wpo_wpmenucart_cart_item_quantity', esc_attr( $cart_item['quantity'] ), $cart_item, $cart_item_key );
						$item_price     = apply_filters( 'woocommerce_cart_item_price', $woocommerce->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
						$product_name   = method_exists( $_product, 'get_name' ) ? $_product->get_name() : $_product->get_title();
						$product_name   = apply_filters( 'wpo_wpmenucart_cart_item_name', $product_name, $cart_item, $cart_item_key );
						$item_name      = apply_filters( 'woocommerce_cart_item_name', $product_name, $cart_item, $cart_item_key );
						$item_thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
						$item_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

						$submenu_items[] = array(
							'item_thumbnail' => $item_thumbnail,
							'item_name'      => $item_name,
							'item_quantity'  => $item_quantity,
							'item_price'     => $item_price,
							'item_permalink' => $item_permalink,
							'cart_item'      => $cart_item,
						);
					
					}
				}
			} else {
				$submenu_items = array();
			}

			return apply_filters( 'wpmenucart_submenu_items_data', $submenu_items );
		}

	}

}