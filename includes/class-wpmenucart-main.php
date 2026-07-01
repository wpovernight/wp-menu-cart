<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Main' ) ) :

	class WpMenuCart_Main {

		/**
		 * @var array
		 */
		public $menu_items;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->filter_nav_menus();

			// AJAX
			add_action( 'wp_ajax_wpmenucart_ajax', array( $this, 'wpmenucart_ajax' ), 0 );
			add_action( 'wp_ajax_nopriv_wpmenucart_ajax', array( $this, 'wpmenucart_ajax' ), 0 );
		}

		/**
		 * Ajax method to return menu item
		 *
		 * @return void
		 */
		public function wpmenucart_ajax(): void {
			check_ajax_referer( 'wpmenucart', 'security' );

			$variable = $this->wpmenucart_menu_item();
			echo wp_kses_post( $variable );
			die();
		}

		/**
		 * Add filters to selected menus to add cart item <li>.
		 *
		 * Legacy slug-based approach, kept as a fallback for sites that have not
		 * yet migrated to the nav menu item approach. Skipped once migration has run
		 * and skipped for any menu that already has a real cart item to avoid
		 * rendering the cart twice.
		 * 
		 * @return null|void
		 */
		public function filter_nav_menus() {
			// Exit if no shop class is active.
			if ( ! isset( WPO_Menu_Cart()->shop ) ) {
				return;
			}

			// Exit once migration to real nav menu items has run.
			if ( get_option( 'wpo_wpmenucart_nav_menu_migrated' ) ) {
				return;
			}

			// Exit if no menus set.
			if ( ! isset( WPO_Menu_Cart()->main_settings['menu_slugs'] ) || empty( WPO_Menu_Cart()->main_settings['menu_slugs'] ) ) {
				return;
			}

			$menu_slug = WPO_Menu_Cart()->main_settings['menu_slugs'][1];

			if ( '0' === $menu_slug ) {
				return;
			}

			// Skip if a real Menu Cart item already exists in this menu.
			if ( isset( WPO_Menu_Cart()->nav_menu ) && WPO_Menu_Cart()->nav_menu->menu_has_cart_item( $menu_slug ) ) {
				return;
			}

			add_filter( 'wp_nav_menu_' . $menu_slug . '_items', array( $this, 'add_itemcart_to_menu' ), 10, 2 );
		}

		/**
		 * Add Menu Cart to menu.
		 * 
		 * @param string $items Menu list items.
		 *
		 * @return string
		 */
		public function add_itemcart_to_menu( string $items ): string {
			$common_classes = $this->get_common_li_classes( $items );
			$menu_item_li   = $this->generate_menu_item_li( $common_classes, 'classic' );

			if ( apply_filters( 'wpmenucart_prepend_menu_item', false ) ) {
				$items = apply_filters( 'wpmenucart_menu_item_wrapper', $menu_item_li ) . $items;
			} else {
				$items .= apply_filters( 'wpmenucart_menu_item_wrapper', $menu_item_li );
			}

			return $items;
		}

		/**
		 * Create HTML for Menu Cart item.
		 * 
		 * @return string
		 */
		public function wpmenucart_menu_item(): string {
			$item_data = WPO_Menu_Cart()->shop->menu_item();

			// Check empty cart settings
			if ( 0 === $item_data['cart_contents_count'] && ! isset( WPO_Menu_Cart()->main_settings['always_display'] ) && ! WPO_Menu_Cart()->is_block_editor() ) {
				$empty_menu_item = '<a class="wpmenucart-contents empty-wpmenucart" style="display:none">&nbsp;</a>';
				return $empty_menu_item;
			}

			if ( isset( WPO_Menu_Cart()->main_settings['wpml_string_translation'] ) && function_exists( 'icl_t' ) ) {
				//use WPML
				$viewing_cart   = icl_t( 'WP Menu Cart', 'hover text', 'View your shopping cart' );
				$start_shopping = icl_t( 'WP Menu Cart', 'empty hover text', 'Start shopping' );
				$cart_contents  = $item_data['cart_contents_count'] .' '. ( 1 === $item_data['cart_contents_count'] ?  icl_t( 'WP Menu Cart', 'item text', 'item' ) :  icl_t( 'WP Menu Cart', 'items text', 'items' ) );
			} else {
				//use regular WP i18n
				$viewing_cart   = __( 'View your shopping cart', 'wp-menu-cart' );
				$start_shopping = __( 'Start shopping', 'wp-menu-cart' );
				/* translators: item count */
				$cart_contents  = sprintf( _n( '%d item', '%d items', $item_data['cart_contents_count'], 'wp-menu-cart' ), $item_data['cart_contents_count'] );
			}

			$this->menu_items['menu']['cart_contents'] = $cart_contents;

			if ( 0 === $item_data['cart_contents_count'] ) {
				$menu_item_href    = apply_filters( 'wpmenucart_emptyurl', $item_data['shop_page_url'] );
				$menu_item_title   = apply_filters( 'wpmenucart_emptytitle', $start_shopping );
				$menu_item_classes = 'wpmenucart-contents empty-wpmenucart-visible';
			} else {
				$menu_item_href    = apply_filters( 'wpmenucart_fullurl', $item_data['cart_url'] );
				$menu_item_title   = apply_filters( 'wpmenucart_fulltitle', $viewing_cart );
				$menu_item_classes = 'wpmenucart-contents';
			}

			$this->menu_items['menu']['menu_item_href']  = $menu_item_href;
			$this->menu_items['menu']['menu_item_title'] = $menu_item_title;

			if ( defined( 'UBERMENU_VERSION' ) && ( version_compare( UBERMENU_VERSION, '3.0.0' ) >= 0 ) ) {
				$menu_item_classes .= ' ubermenu-target';
			}

			$menu_item = '<a class="' . $menu_item_classes . '" href="' . $menu_item_href . '" title="' . $menu_item_title . '">';

			$menu_item_a_content = '';
			if ( isset( WPO_Menu_Cart()->main_settings['icon_display'] ) ) {
				// Only icon 0 is available in free.
				$icon                 = '0';
				$menu_item_icon       = '<i class="wpmenucart-icon-shopping-cart-' . $icon . '" role="img" aria-label="' . __( 'Cart','wp-menu-cart' ) . '"></i>';
				$menu_item_a_content .= $menu_item_icon;
			} else {
				$menu_item_icon = '';
			}

			$items_display = WPO_Menu_Cart()->main_settings['items_display'] ?? 3;

			switch ( $items_display ) {
				case 1: //items only
					$menu_item_a_content .= '<span class="cartcontents">' . $cart_contents . '</span>';
					break;
				case 2: //price only
					$menu_item_a_content .= '<span class="amount">' . $item_data['cart_total'] . '</span>';
					break;
				case 3: //items & price
					$menu_item_a_content .= '<span class="cartcontents">' . $cart_contents . '</span><span class="amount">' . $item_data['cart_total'] . '</span>';
					break;
			}

			$menu_item_a_content = apply_filters( 'wpmenucart_menu_item_a_content', $menu_item_a_content, $menu_item_icon, $cart_contents, $item_data );

			$this->menu_items['menu']['menu_item_a_content'] = $menu_item_a_content;

			$menu_item .= $menu_item_a_content . '</a>';

			$menu_item = apply_filters( 'wpmenucart_menu_item_a', $menu_item, $item_data, WPO_Menu_Cart()->main_settings, $menu_item_a_content, $viewing_cart, $start_shopping, $cart_contents );

			if ( ! empty( $menu_item ) ) {
				return $menu_item;
			}
		}

		/**
		 * Get a flat list of common classes from all menu items in a menu
		 * @param  string      $items nav_menu HTML containing all <li> menu items
		 * @return string|null        flat (imploded) list of common classes
		 */
		public function get_common_li_classes( string $items ) {
			if ( empty( $items ) || ! class_exists( 'DOMDocument' ) ) {
				return '';
			}

			$libxml_previous_state = libxml_use_internal_errors( true ); // enable user error handling

			$dom_items = new DOMDocument;
			$dom_items->loadHTML( $items );
			$lis = $dom_items->getElementsByTagName( 'li' );

			if ( empty( $lis ) ) {
				libxml_clear_errors();
				libxml_use_internal_errors( $libxml_previous_state );
				return;
			}

			foreach ( $lis as $li ) {
				if ( 'ul' !== $li->parentNode->tagName )
					$li_classes[] = explode( ' ', $li->getAttribute( 'class' ) );
			}

			// Uncomment to dump DOM errors / warnings
			//$errors = libxml_get_errors();
			//print_r ($errors);
			
			// clear errors and reset to previous error handling state
			libxml_clear_errors();
			libxml_use_internal_errors( $libxml_previous_state );

			if ( ! empty( $li_classes ) ) {
				$common_li_classes = array_shift( $li_classes );
				foreach ( $li_classes as $li_class ) {
					$common_li_classes = array_intersect( $li_class, $common_li_classes );
				}
				$common_li_classes_flat = implode( ' ', $common_li_classes );
			} else {
				$common_li_classes_flat = '';
			}
			return $common_li_classes_flat;
		}

		/**
		 * Gets the menu item <li>
		 *
		 * @param  string $classes
		 * @param  string $context  can be 'classic' or 'block'
		 *
		 * @return string
		 */
		public function generate_menu_item_li( string $classes, string $context = 'classic' ): string {
			$classes .= ' wpmenucartli';

			if ( function_exists( 'is_checkout' ) && function_exists( 'is_cart' ) && ( is_checkout() || is_cart() ) && empty( WPO_Menu_Cart()->main_settings['show_on_cart_checkout_page'] ) ) {
				$classes .= ' hidden-wpmenucart';
			}

			if ( 'classic' === $context ) {
				$classes .= ' menu-item';
			} elseif ( 'block' === $context ) {
				$classes .= ' wp-block-navigation-item wp-block-navigation-link';
			}

			$item_data = WPO_Menu_Cart()->shop->menu_item();
			if ( 0 === $item_data['cart_contents_count'] && ! isset( WPO_Menu_Cart()->main_settings['always_display'] ) && ! WPO_Menu_Cart()->is_block_editor() ) {
				$classes .= ' empty-wpmenucart';
			}

			$classes                                          = apply_filters( 'wpmenucart_menu_item_classes', $classes );
			$this->menu_items['menu']['menu_item_li_classes'] = $classes;

			// DEPRECATED: These filters are now deprecated in favour of the more precise filters in the functions!
			$menu_item_li = apply_filters_deprecated( 'wpmenucart_menu_item_filter', array( $this->wpmenucart_menu_item() ), '2.5.3', '' );

			return '<li class="' . $classes . '" id="wpmenucartli">' . $menu_item_li . '</li>';
		}

		/**
		 * Determine whether the menu cart should render in the current context.
		 *
		 * @return bool
		 */
		public function should_render_menucart(): bool {
			$render = true;

			if (
				function_exists( 'is_checkout' ) &&
				function_exists( 'is_cart' ) &&
				( is_checkout() || is_cart() ) &&
				empty( WPO_Menu_Cart()->main_settings['show_on_cart_checkout_page'] )
			) {
				$render = false;
			}

			return apply_filters( 'wpmenucart_should_render', $render );
		}

		/**
		 * Ajaxify Menu Cart.
		 * 
		 * @param array $fragments Existing WC fragments
		 * 
		 * @return array
		 */
		public function woocommerce_ajax_fragments( array $fragments ): array {
			if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
				define( 'WOOCOMMERCE_CART', true );
			}

			$fragments['a.wpmenucart-contents'] = $this->wpmenucart_menu_item();

			return apply_filters( 'wpmenucart_menu_item_fragments', $fragments );
		}

		/**
		 * Output the navigation block.
		 *
		 * @param array $atts Block attributes
		 * 
		 * @return string
		 */
		public function navigation_block_output( array $atts ): string {
			$menu = sprintf( '<ul>%s</ul>', WPO_Menu_Cart()->main->generate_menu_item_li( '', 'block' ) );

			if ( WPO_Menu_Cart()->is_block_editor() ) {
				// deactivate links when using the full site or block editor to prevent navigating away from the editor
				$menu = preg_replace( '/(<[^>]+) href=".*?"/i', '$1', $menu );
			}

			return $menu;
		}

	}

endif;
