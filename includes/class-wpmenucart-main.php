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

			// AJAX remove cart item
			if ( WPO_Menu_Cart()->is_shop_active() ) {
				add_action( 'wp_ajax_wpmenucart_ajax_remove_cart_item', array( $this, 'remove_cart_item' ) );
				add_action( 'wp_ajax_nopriv_wpmenucart_ajax_remove_cart_item', array( $this, 'remove_cart_item' ) );
			}

			// Mini cart slide-out panel
			add_action( 'wp_footer', array( $this, 'display_mini_cart_slideout' ) );
		}

		/**
		 * Ajax method to return menu item
		 *
		 * @return void
		 */
		public function wpmenucart_ajax(): void {
			check_ajax_referer( 'wpmenucart', 'security' );

			$response['menu_cart'] = $this->wpmenucart_menu_item();

			if ( $this->is_cart_mode_active( 'sidebar' ) ) {
				$response['mini_cart_slideout'] = $this->get_mini_cart_slideout();
			}

			wp_send_json_success( $response );
			die();
		}

		/**
		 * Ajax method to remove cart item.
		 *
		 * @return void
		 */
		public function remove_cart_item(): void {
			check_ajax_referer( 'wpmenucart', 'security' );

			if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
				define( 'WOOCOMMERCE_CART', true );
			}

			if ( isset( $_REQUEST['action'] ) && 'wpmenucart_ajax_remove_cart_item' == $_REQUEST['action'] && ! empty( $_REQUEST['key'] ) ) {

				$cart_item_key = sanitize_text_field( wp_unslash( $_REQUEST['key'] ) );
				$source        = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : '';

				/**
				 * Whether a removal request should actually proceed, based on
				 * which template the remove button came from.
				 *
				 * @param bool   $allow  Whether removal should proceed.
				 * @param string $source The template_slug the request claims to be from.
				 */
				if ( ! apply_filters( 'wpo_wpmenucart_allow_cart_item_removal', true, $source ) ) {
					wp_send_json_error( array( 'message' => __( 'Item removal is not allowed here.', 'wp-menu-cart' ) ) );
					die();
				}

				$output = false;

				if ( 'WC' === WPO_Menu_Cart()->get_active_shop() ) {
					$output = WC()->cart->remove_cart_item( $cart_item_key );
				} elseif ( 'EDD' === WPO_Menu_Cart()->get_active_shop() ) {
					$cart_contents = edd_get_cart_contents();

					if ( ! empty( $cart_contents ) ) {
						foreach ( $cart_contents as $key => $cart_item ) {
							if ( ! empty( $cart_item ) && isset( $cart_item['id'] ) && (string) $cart_item['id'] === $cart_item_key ) {
								edd_remove_from_cart( $key );
								$output = true;
								break;
							}
						}
					}
				}
			
				if ( $output ) {
					$response['menu_cart'] = $this->wpmenucart_menu_item();
					// Include slide-out content for sidebar mode
					if ( WPO_Menu_Cart()->main->is_cart_mode_active( 'sidebar' ) ) {
						$response['mini_cart_slideout'] = $this->get_mini_cart_slideout();
					}

					$response = apply_filters( 'wpo_wpmenucart_remove_cart_item_ajax_response', $response );

					wp_send_json_success( $response );
				} else {
					wp_send_json_error( $output );
				}
			}

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
				if ( 'ul' !== $li->parentNode->tagName ) {
					$li_classes[] = explode( ' ', $li->getAttribute( 'class' ) );
				}
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

			$settings     = WPO_Menu_Cart()->main_settings;
			$desktop_mode = $settings['desktop_cart_mode'] ?? 'none';
			$mobile_mode  = $settings['mobile_cart_mode'] ?? 'none';

			$classes .= ' desktop-active-mode-' . $desktop_mode;
			$classes .= ' mobile-active-mode-' . $mobile_mode;

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
		 * Base check to determine whether the menu cart should render in the current context.
		 *
		 * @return bool
		 */
		public function should_render_base(): bool {
			$is_ajax     = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' ) && DOING_AJAX;
			$render      = true;
			$shop_plugin = WPO_Menu_Cart()->main_settings['shop_plugin'] ?: '';

			if ( false === WPO_Menu_Cart()->is_shop_active( array(), $shop_plugin ) ) {
				$render = false;
			} elseif ( 'WooCommerce' === $shop_plugin ) {
				if ( is_admin() && ( isset( $_GET['action'] ) && 'elementor' == $_GET['action'] ) ) {
					$render = false;
				} elseif ( ! $is_ajax && function_exists( 'WC' ) && ( is_checkout() || is_cart() ) && empty( WPO_Menu_Cart()->main_settings['show_on_cart_checkout_page'] ) ) {
					$render = false;
				}
			}

			return $render;
		}

		/**
		 * Determine whether the menu cart should render in the current context.
		 *
		 * @return bool
		 */
		public function should_render_menucart(): bool {
			return apply_filters( 'wpmenucart_should_render', $this->should_render_base() );
		}

		/**
		 * Check whether a given cart mode is active on either desktop or mobile.
		 *
		 * @param  string $mode
		 * @return bool
		 */
		public function is_cart_mode_active( string $mode ): bool {
			$settings = WPO_Menu_Cart()->main_settings;
			$desktop  = isset( $settings['desktop_cart_mode'] ) ? $settings['desktop_cart_mode'] : 'none';
			$mobile   = isset( $settings['mobile_cart_mode'] )  ? $settings['mobile_cart_mode']  : 'none';

			return $mode === $desktop || $mode === $mobile;
		}

		/**
		 * Output the mini cart slideout panel in the footer when sidebar mode is active.
		 *
		 * @return void
		 */
		public function display_mini_cart_slideout(): void {
			if ( true === $this->should_render_menucart() && $this->is_cart_mode_active( 'sidebar' ) ) {
				echo wp_kses( $this->get_mini_cart_slideout(), $this->get_slideout_allowed_html() );
			}
		}

		/**
		 * Get the rendered HTML for the mini cart slideout panel.
		 *
		 * @param  string $nav_menu_items Nav menu items HTML.
		 * @param  array  $args           Template arguments.
		 * @return string
		 */
		public function get_mini_cart_slideout( string $nav_menu_items = '', array $args = array() ): string {
			$slideout = new WpMenuCart_Template( 'menucart-slideout', $nav_menu_items, $args );
			$slideout = $slideout->get_output();

			return $slideout;
		}

		/**
		 * Get the allowed HTML tags and attributes for the mini cart slideout panel.
		 *
		 * @return array
		 */
		private function get_slideout_allowed_html(): array {
		    return array(
		        'div'    => array( 'class' => true, 'role' => true, 'aria-modal' => true, 'aria-label' => true, 'data-key' => true, 'data-cart-count' => true ),
		        'ul'     => array( 'class' => true, 'style' => true ),
		        'li'     => array( 'class' => true ),
		        'h2'     => array( 'class' => true ),
		        'h3'     => array( 'class' => true ),
		        'p'      => array( 'class' => true ),
		        'span'   => array( 'class' => true ),
		        'button' => array( 'type' => true, 'class' => true, 'aria-label' => true ),
		        'a'      => array( 'href' => true, 'class' => true ),
		        'img'    => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true ),
		    );
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

			if ( $this->is_cart_mode_active( 'sidebar' ) ) {
				$fragments['.wpmenucart-slideout'] = $this->get_mini_cart_slideout();
			}

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
			$menu = sprintf( '<ul>%s</ul>', $this->generate_menu_item_li( '', 'block' ) );

			if ( WPO_Menu_Cart()->is_block_editor() ) {
				// deactivate links when using the full site or block editor to prevent navigating away from the editor
				$menu = preg_replace( '/(<[^>]+) href=".*?"/i', '$1', $menu );
			}

			return $menu;
		}

	}

endif;
