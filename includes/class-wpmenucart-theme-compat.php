<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Theme_Compat' ) ) :

	class WpMenuCart_Theme_Compat {

		public function __construct() {
			add_action( 'init', array( $this, 'maybe_hide_storefront_cart' ) );
		}

		/**
		 * Unhook Storefront's own header cart and handheld footer bar cart link.
		 *
		 * @return void
		 */
		public function maybe_hide_storefront_cart(): void {
			if ( ! isset( WPO_Menu_Cart()->main_settings['hide_theme_cart'] ) ) {
				return;
			}

			if ( 'storefront' !== get_template() ) {
				return;
			}

			remove_action( 'storefront_header', 'storefront_header_cart', 60 );

			add_filter( 'storefront_handheld_footer_bar_links', function( $links ) {
				unset( $links['cart'] );
				return $links;
			} );
		}

		/**
		 * Hide Divi's own cart.
		 *
		 * @param  WP_Styles|null $wp_styles WP_Styles instance in block-editor context, null on the frontend.
		 * @return void
		 */
		public static function maybe_hide_divi_cart( ?WP_Styles $wp_styles = null ): void {
			if ( ! isset( WPO_Menu_Cart()->main_settings['hide_theme_cart'] ) ) {
				return;
			}

			$css = '.et-cart-info { display:none !important; }';

			if ( $wp_styles ) {
				$wp_styles->add_inline_style( 'wpmenucart', $css );
			} else {
				wp_add_inline_style( 'wpmenucart', $css );
			}
		}

	}

endif;
