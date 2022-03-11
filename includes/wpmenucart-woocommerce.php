<?php
if ( ! class_exists( 'WPMenuCart_WooCommerce' ) ) {
	class WPMenuCart_WooCommerce {
		/**
		 * Construct.
		 */
		public function __construct() {
		}
	
		public function menu_item() {
			$this->maybe_load_session();  // in backend (full site editor) the wc session is null
			$this->maybe_load_customer();
			$this->maybe_load_cart();     // make sure cart is loaded! https://wordpress.org/support/topic/activation-breaks-customise?replies=10#post-7908988

			$menu_item = array(
				'cart_url'				=> $this->cart_url(),
				'shop_page_url'			=> $this->shop_url(),
				'cart_total'			=> strip_tags( $this->get_cart_total() ),
				'cart_contents_count'	=> $this->get_cart_contents_count(),
			);
		
			return apply_filters( 'wpmenucart_menu_item_data', $menu_item );
		}

		public function maybe_load_session() {
			if ( function_exists( 'WC' ) && empty( WC()->session ) ) {
				WC()->frontend_includes();
				$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
				WC()->session  = new $session_class();
				WC()->session->init();
			}
		}

		public function maybe_load_customer() {
			if ( function_exists( 'WC' ) && empty( WC()->customer ) ) {
				WC()->customer = new WC_Customer( get_current_user_id(), true );
			}
		}

		public function maybe_load_cart() {
			if ( function_exists( 'WC' ) ) {
				if ( empty( WC()->cart ) ) {
					WC()->cart = new WC_Cart();
					WC()->cart->get_cart(); // force cart contents refresh
				}
			} else {
				global $woocommerce;
				if ( empty( $woocommerce->cart ) ) {
					$woocommerce->cart = new WC_Cart();
				}
			}
		}

		public function cart_url() {
			if ( defined('WOOCOMMERCE_VERSION') && version_compare( WOOCOMMERCE_VERSION, '2.5.2', '>=' ) ) {
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
			if ( defined('WOOCOMMERCE_VERSION') && version_compare( WOOCOMMERCE_VERSION, '2.5.2', '>=' ) ) {
				return wc_get_page_permalink( 'shop' );
			} else {
				return get_permalink( woocommerce_get_page_id( 'shop' ) );
			}
		}

		public function get_cart_contents_count() {
			if ( function_exists( 'WC' ) && ! empty( WC()->cart ) ) {
				return WC()->cart->get_cart_contents_count();
			} else {
				return $GLOBALS['woocommerce']->cart->get_cart_contents_count();
			}
		}

		public function get_cart_total() {
			$settings = get_option('wpmenucart');
			if ( defined('WC_VERSION') && version_compare( WC_VERSION, '3.3', '>=' ) ) {
				if (isset($settings['total_price_type']) && $settings['total_price_type'] == 'subtotal') {
					if ( WC()->cart->display_prices_including_tax() ) {
						$cart_contents_total = wc_price( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() );
					} else {
						$cart_contents_total = wc_price( WC()->cart->get_subtotal() );
					}
				} elseif (isset($settings['total_price_type']) && $settings['total_price_type'] == 'checkout_total') {
					$cart_contents_total = wc_price( WC()->cart->get_total('edit') );
				} else {
					if ( WC()->cart->display_prices_including_tax() ) {
						$cart_contents_total = wc_price( WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() );
					} else {
						$cart_contents_total = wc_price( WC()->cart->get_cart_contents_total() );
					}
				}
			} else {
				// Backwards compatibility
				global $woocommerce;
				
				// $woocommerce->cart->get_cart_total() is not a display function,
				// so we add tax if cart prices are set to display incl. tax
				// see https://github.com/woothemes/woocommerce/issues/6701
				if (isset($settings['total_price_type']) && $settings['total_price_type'] == 'subtotal') {
					// Display varies depending on settings
					if ( $woocommerce->cart->display_cart_ex_tax ) {
						$cart_contents_total = wc_price( $woocommerce->cart->subtotal_ex_tax );
					} else {
						$cart_contents_total = wc_price( $woocommerce->cart->subtotal );
					}
				} else {
					if ( $woocommerce->cart->display_cart_ex_tax ) {
						$cart_contents_total = wc_price( $woocommerce->cart->cart_contents_total );
					} else {
						$cart_contents_total = wc_price( $woocommerce->cart->cart_contents_total + $woocommerce->cart->tax_total );
					}
				}
				$cart_contents_total = apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );
			}

			return $cart_contents_total;
		}
	}
}