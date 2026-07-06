<?php
/**
 * Functions for creating the data to replace the shortcodes in the templates
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Data' ) ) :

	class WpMenuCart_Data {

		public $menu_args;
		public $menu_slug;
		public $item_data;
		public $nav_menu_items;
		public $template_slug;

		public function __construct( $nav_menu_items, $menu_slug, $menu_args, $template_slug = '' ) {
			$this->nav_menu_items = $nav_menu_items;
			$this->menu_slug      = $menu_slug;
			$this->menu_args      = $menu_args;
			$this->template_slug  = $template_slug;
			$this->item_data      = WPO_Menu_Cart()->shop->menu_item();
		}

		/**
		 * Get the aria label text.
		 *
		 * @return string
		 */
		public function slideout_aria_label(): string {
			return apply_filters( 'wpmenucart_slideout_aria_label', __( 'Mini cart', 'wp-menu-cart' ) );
		}

		/**
		 * Get the slideout title text including cart count.
		 *
		 * @return string
		 */
		public function slideout_title(): string {
			$count = absint( $this->item_data['cart_contents_count'] );
			/* translators: %d: number of items in cart */
			$title = sprintf( __( 'Shopping Cart (%d)', 'wp-menu-cart' ), $count );

			return apply_filters( 'wpmenucart_slideout_title', $title, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the close button aria label text.
		 *
		 * @return string
		 */
		public function slideout_close_aria_label(): string {
			return apply_filters( 'wpmenucart_slideout_close_aria_label', __( 'Close mini cart', 'wp-menu-cart' ) );
		}

		public function submenu_ul_class() {
			$classes = apply_filters( 'wpmenucart_submenu_classes', 'sub-menu wpmenucart' );

			if ( 0 === $this->item_data['cart_contents_count'] ) {
				$classes .= ' empty';
			}

			// wp block specific
			$classes .= ' wp-block-navigation__submenu-container';

			return apply_filters( 'wpmenucart_submenu_ul_class', $classes, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		public function submenu_ul_style() {
			$submenu_style = apply_filters( 'wpmenucart_submenu_style', '' );
			return apply_filters( 'wpmenucart_submenu_ul_style', $submenu_style, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		public function submenu_li_class( $submenu_item_data = array() ) {
			// legacy filter
			$classes = apply_filters( 'wpmenucart_submenu_item_li_classes', 'menu-item wpmenucart-submenu-item', $this->item_data );

			// wp block specific
			$classes .= ' wp-block-navigation-item wp-block-navigation-link';

			return apply_filters( 'wpmenucart_submenu_li_class', $classes, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the label text shown while an item is pending removal.
		 *
		 * @return string
		 */
		public function submenu_item_remove_undo_label(): string {
			return apply_filters( 'wpmenucart_submenu_item_remove_undo_label', __( 'The product will be deleted...', 'wp-menu-cart' ) );
		}

		/**
		 * Get the button label for the undo removal action.
		 *
		 * @return string
		 */
		public function submenu_item_remove_undo_button_label(): string {
			return apply_filters( 'wpmenucart_submenu_item_remove_undo_button_label', __( 'Undo', 'wp-menu-cart' ) );
		}

		/**
		 * Get the thumbnail image for a cart item.
		 *
		 * @param  array $submenu_item_data Cart item data.
		 * @return string
		 */
		public function submenu_item_image( array $submenu_item_data ): string {
			$thumbnail = ! empty( $submenu_item_data['item_thumbnail'] ) ? $submenu_item_data['item_thumbnail'] : '';
			return apply_filters( 'wpmenucart_submenu_item_image', $thumbnail, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the display name for a cart item, truncated if needed.
		 *
		 * @param  array $submenu_item_data Cart item data.
		 * @return string
		 */
		public function submenu_item_name( array $submenu_item_data ): string {
			// Remove any HTML formatting from the item name
			$item_name = strip_tags( $submenu_item_data['item_name'] );
			return apply_filters( 'wpmenucart_submenu_item_name', $item_name, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the variation/attribute meta string for a cart item.
		 *
		 * @param  array $submenu_item_data Cart item data.
		 * @return string
		 */
		public function submenu_item_meta( array $submenu_item_data ): string {
			$meta = '';

			// Check if WooCommerce product has variations/metadata
			if ( ! empty( $submenu_item_data['cart_item']['variation'] ) ) {
				$variation_data = $submenu_item_data['cart_item']['variation'];
				$meta_items     = array();

				foreach ( $variation_data as $key => $value ) {
					if ( empty( $value ) ) {
						continue;
					}

					// Clean up the attribute key
					if ( strpos( $key, 'attribute_' ) === 0 ) {
						$label = str_replace( 'attribute_', '', $key );
					} else {
						$label = $key;
					}

					// Remove 'pa_' prefix (Product Attribute taxonomy prefix)
					$label = preg_replace( '/^pa[_-]/', '', $label );

					// Convert to readable format
					$label = str_replace( array( '-', '_' ), ' ', $label );
					$label = ucwords( $label );

					// Get taxonomy term name if it exists (for better display)
					if ( taxonomy_exists( 'pa_' . sanitize_title( $label ) ) ) {
						$term = get_term_by( 'slug', $value, 'pa_' . sanitize_title( str_replace( ' ', '-', strtolower( $label ) ) ) );
						if ( $term && ! is_wp_error( $term ) ) {
							$value = $term->name;
						}
					}

					$meta_items[] = $label . ': ' . ucfirst( $value );
				}

				if ( ! empty( $meta_items ) ) {
					$meta = implode( ', ', $meta_items );
				}
			}

			return apply_filters( 'wpmenucart_submenu_item_meta', $meta, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the quantity for a cart item.
		 *
		 * @param  array $submenu_item_data Cart item data.
		 * @return int
		 */
		public function submenu_item_quantity( array $submenu_item_data ): int {
			$quantity = ! empty( $submenu_item_data['item_quantity'] ) ? $submenu_item_data['item_quantity'] : 1;
			return apply_filters( 'wpmenucart_submenu_item_quantity', $quantity, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the price for a cart item.
		 *
		 * @param  array $submenu_item_data Cart item data.
		 * @return string
		 */
		public function submenu_item_price( array $submenu_item_data ): string {
			$price = ! empty( $submenu_item_data['item_price'] ) ? $submenu_item_data['item_price'] : '';
			return apply_filters( 'wpmenucart_submenu_item_price', $price, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings );
		}

		public function submenu_item_remove( $submenu_item_data ) {
			if ( ! empty( $submenu_item_data['cart_item']['key'] ) ) {      // WC
				$key = $submenu_item_data['cart_item']['key'];
			} elseif ( ! empty( $submenu_item_data['cart_item']['id'] ) ) { // EDD
				$key = $submenu_item_data['cart_item']['id'];
			} else {
				$key = null;
			}

			// Render empty div; icon provided via CSS background-image to avoid sanitization stripping inline SVG
			$submenu_item_remove = '<div class="wpmenucart-product-remove" data-key="' . esc_attr( $key ?? '' ) . '" data-source="' . esc_attr( $this->template_slug ) . '" aria-label="' . esc_attr__( 'Remove cart item', 'wp-menu-cart' ) . '"></div>';

			// Don't show remove button if a shop is not active
			if ( ! WPO_Menu_Cart()->is_shop_active() ) {
				$submenu_item_remove = null;
			}

			return apply_filters( 'wpmenucart_submenu_item_remove', $submenu_item_remove, $this->item_data, $submenu_item_data, WPO_Menu_Cart()->main_settings, $this->template_slug );
		}

		/**
		 * Get the "View Cart" button text.
		 *
		 * @return string
		 */
		public function view_cart_button_text() {
			$text = __( 'View Cart', 'wp-menu-cart' );
			return apply_filters( 'wpmenucart_view_cart_button_text', $text, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the "Checkout" button text.
		 *
		 * @return string
		 */
		public function checkout_button_text() {
			$text = __( 'Checkout', 'wp-menu-cart' );
			return apply_filters( 'wpmenucart_checkout_button_text', $text, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the "Start Shopping" button text.
		 *
		 * @return string
		 */
		public function start_shopping_button_text() {
			$text = __( 'Start Shopping', 'wp-menu-cart' );
			return apply_filters( 'wpmenucart_start_shopping_button_text', $text, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the empty cart title text.
		 *
		 * @return string
		 */
		public function empty_cart_title_text() {
			$text = __( 'Your cart is empty', 'wp-menu-cart' );
			return apply_filters( 'wpmenucart_empty_cart_title_text', $text, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the empty cart subtitle text.
		 *
		 * @return string
		 */
		public function empty_cart_subtitle_text() {
			$text = __( 'Nothing to show here right now', 'wp-menu-cart' );
			return apply_filters( 'wpmenucart_empty_cart_subtitle_text', $text, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		public function cart_url() {
			$url = apply_filters( 'wpmenucart_fullurl', $this->item_data['cart_url'] );
			return apply_filters( 'wpmenucart_cart_url', $url, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		public function shop_url() {
			$url = apply_filters( 'wpmenucart_emptyurl', $this->item_data['shop_page_url'] );
			return apply_filters( 'wpmenucart_shop_url', $url, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the checkout URL.
		 *
		 * @return string
		 */
		public function checkout_url() {
			$url = apply_filters( 'wpmenucart_checkouturl', $this->item_data['checkout_url'] );
			return apply_filters( 'wpmenucart_checkout_url', $url, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the cart total label text.
		 *
		 * @return string
		 */
		public function cart_total_label(): string {
			return apply_filters( 'wpmenucart_cart_total_label', __( 'Total', 'wp-menu-cart' ) );
		}

		/**
		 * Get the cart total price.
		 *
		 * @return mixed
		 */
		public function cart_total() {
			$total_price = $this->item_data['cart_total'] ?? 0;
			return apply_filters( 'wpmenucart_cart_total', $total_price, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

		/**
		 * Get the cart contents count.
		 *
		 * @return int
		 */
		public function cart_contents_count(): int {
			$count = $this->item_data['cart_contents_count'] ?? 0;
			return apply_filters( 'wpmenucart_cart_contents_count', (int) $count, $this->item_data, WPO_Menu_Cart()->main_settings );
		}

	}

endif; // class_exists
