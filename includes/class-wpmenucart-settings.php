<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Settings' ) ) :

	class WpMenuCart_Settings {

		const OPTION_NAME        = 'wpo_wpmenucart_main_settings';
		const PAGE_DISPLAY_MODES = 'wpo_wpmenucart_display_modes';
		const PAGE_ICON_STYLE    = 'wpo_wpmenucart_icon_style';
		const PAGE_GENERAL       = 'wpo_wpmenucart_general_settings';

		/**
		 * @var WpMenuCart_Settings_Callbacks
		 */
		public $callbacks;

		public function __construct() {
			include_once 'class-wpmenucart-settings-callbacks.php';

			$this->callbacks = new WpMenuCart_Settings_Callbacks();

			add_action( 'admin_init', array( $this, 'main_settings' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );

			add_filter( 'plugin_action_links_' . WPO_Menu_Cart()->plugin_basename, array( $this, 'add_settings_link' ) );
		}

		/**
		 * Resolve the callback to use for a given settings-related method name,
		 * covering field callbacks, section callbacks, and the sanitize callback.
		 *
		 * @param  string $method
		 * @return callable
		 */
		public function resolve_callback( string $method ): array {
			return apply_filters( 'wpo_wpmenucart_settings_callback', array( $this->callbacks, $method ), $method );
		}

		/**
		 * Register settings fields and sections.
		 *
		 * @return void
		 */
		public function main_settings(): void {
			$option_group  = self::OPTION_NAME;
			$option_name   = self::OPTION_NAME;
			$option_values = get_option( $option_name, array() );

			register_setting( $option_group, $option_name, $this->resolve_callback( 'validate' ) );

			// Register defaults when settings are empty.
			if ( empty( $option_values ) ) {
				$this->default_settings();
			}

			// Convert old menu_name_1 format to array.
			if ( isset( $option_values['menu_name_1'] ) ) {
				$option_values['menu_slugs'] = array( '1' => $option_values['menu_name_1'] );
				update_option( $option_name, $option_values );
			}

			// Register Sections
			$sections = apply_filters( 'wpo_wpmenucart_main_settings_sections', array(
				'cart_display_modes'  => array(
					'title'    => '<span class="wpmenucart-section__icon" aria-hidden="true">' . $this->callbacks->get_svg( 'cart-display-modes.svg' ) . '</span> ' . __( 'Cart Display Modes', 'wp-menu-cart' ),
					'callback' => function() use ( $option_values ) {
						$this->callbacks->cart_display_modes_section( $option_values );
					},
					'page'     => self::PAGE_DISPLAY_MODES,
				),
				'menu_icon_style'     => array(
					'title'    => '<span class="wpmenucart-section__icon" aria-hidden="true">' . $this->callbacks->get_svg( 'general.svg' ) . '</span> ' . __( 'Menu Icon Style', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'section' ),
					'page'     => self::PAGE_ICON_STYLE,
				),
				'general_settings'    => array(
					'title'    => '<span class="wpmenucart-section__icon" aria-hidden="true">' . $this->callbacks->get_svg( 'general.svg' ) . '</span> ' . __( 'General Settings', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'section' ),
					'page'     => self::PAGE_GENERAL,
				),
			) );

			foreach ( $sections as $id => $section ) {
				add_settings_section( $id, $section['title'], $section['callback'], $section['page'] );
			}

			$parent_theme = wp_get_theme( get_template() );

			$fields = array(
				'shop_plugin'                => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'E-commerce Plugin', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'shop_select' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'shop_plugin',
						'options'     => (array) $this->get_shop_plugins(),
						'description' => __( 'Select which e-commerce plugin you would like Menu Cart to work with.', 'wp-menu-cart' ),
					),
				),
				'block_theme_enabled'        => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Current theme is block type', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'block_theme_enabled',
						'disabled'    => true,
						'default'     => 1,
						'description' => sprintf(
							/* translators: 1. theme name, 2. here docs link */
							__( 'Your current theme, %1$s, is a block theme, therefore, you need to configure the cart menu using the navigation block. Please follow the instructions to do it %2$s.', 'wp-menu-cart' ),
							'<strong>' . WPO_Menu_Cart()->get_current_theme_name() . '</strong>',
							'<a href="https://docs.wpovernight.com/wp-menu-cart/cart-block/" target="_blank">' . __( 'here', 'wp-menu-cart' ) . '</a>'
						),
					),
					'show_if'  => WPO_Menu_Cart()->is_block_theme(),
				),
				'hide_theme_cart'            => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Hide theme shopping cart icon', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'hide_theme_cart',
					),
					'show_if'  => ! empty( $parent_theme ) && in_array( $parent_theme->get( 'Name' ), array( 'Storefront', 'Divi' ) ),
				),
				'always_display'             => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Always display cart', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'always_display',
						'description' => __( "Always display cart, even if it's empty.", 'wp-menu-cart' ),
					),
				),
				'show_on_cart_checkout_page' => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Show on cart & checkout page', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'show_on_cart_checkout_page',
						'description' => __( 'To avoid distracting your customers with duplicate information we do not display the menu cart item on the cart & checkout pages by default', 'wp-menu-cart' ),
					),
					'show_if'  => WPO_Menu_Cart()->is_shop_active( array(), 'WooCommerce' ),
				),
				'icon_display'               => array(
					'section'  => 'menu_icon_style',
					'page'     => self::PAGE_ICON_STYLE,
					'title'    => __( 'Display shopping cart icon', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'icon_display',
					),
				),
				'cart_icon'                  => array(
					'section'  => 'menu_icon_style',
					'page'     => self::PAGE_ICON_STYLE,
					'title'    => __( 'Choose a cart icon.', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'icons_radio_element_callback' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'cart_icon',
						'options'     => array(
							'0'  => '0',
							'1'  => '1',
							'2'  => '2',
							'3'  => '3',
							'4'  => '4',
							'5'  => '5',
							'6'  => '6',
							'7'  => '7',
							'8'  => '8',
							'9'  => '9',
							'10' => '10',
							'11' => '11',
							'12' => '12',
							'13' => '13',
						),
					),
				),
				'cart_icon_color'            => array(
					'section'  => 'menu_icon_style',
					'page'     => self::PAGE_ICON_STYLE,
					'title'    => __( 'Override icon color', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'cart_icon_color',
						'disabled'    => true,
						'pro'         => true,
					),
				),
				'custom_icon'                => array(
					'section'  => 'menu_icon_style',
					'page'     => self::PAGE_ICON_STYLE,
					'title'    => __( 'Custom Icon', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'media_upload_callback' ),
					'args'     => array(
						'option_name'          => $option_name,
						'id'                   => 'custom_icon',
						'uploader_button_text' => __( 'Set image', 'wp-menu-cart' ),
						'uploader_title'       => __( 'Select or upload a custom menu cart icon.', 'wp-menu-cart' ),
						'remove_button_text'   => __( 'Remove image', 'wp-menu-cart' ),
						'description'          => __( 'Upload a custom menu cart icon here if you do not want to use one of the icons above. Make sure you resize the icon before uploading. Icon should usually be 15-30px tall.', 'wp-menu-cart' ),
						'disabled'             => true,
						'pro'                  => true,
					),
				),
				'items_display'              => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Contents of the menu cart item', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'radio_button' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'items_display',
						'options'     => array(
							'1' => __( 'Items Only', 'wp-menu-cart' ),
							'2' => __( 'Price Only', 'wp-menu-cart' ),
							'3' => __( 'Both price and items', 'wp-menu-cart' ),
						),
					),
				),
				'total_price_type'           => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Price to display', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'select' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'total_price_type',
						'options'     => array(
							'total'          => __( 'Cart total (including discounts)', 'wp-menu-cart' ),
							'subtotal'       => __( 'Subtotal (total of products)', 'wp-menu-cart' ),
							'checkout_total' => __( 'Checkout total (including discounts, fees & shipping)', 'wp-menu-cart' ),
						),
						'default'     => 'total',
					),
					'show_if'  => class_exists( 'WooCommerce' ),
				),
				'custom_class'               => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Enter a custom CSS class (optional)', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'text_input' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'custom_class',
						'disabled'    => true,
						'pro'         => true,
						'size'        => 30,
					),
				),
				'wpml_string_translation'    => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Use WPML String Translation', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'wpml_string_translation',
					),
					'show_if'  => function_exists( 'icl_register_string' ),
				),
				'builtin_ajax'               => array(
					'section'  => 'general_settings',
					'page'     => self::PAGE_GENERAL,
					'title'    => __( 'Use custom AJAX', 'wp-menu-cart' ),
					'callback' => $this->resolve_callback( 'checkbox' ),
					'args'     => array(
						'option_name' => $option_name,
						'id'          => 'builtin_ajax',
						'description' => __( 'Enable this option to use the custom AJAX / live update functions instead of the default ones from your shop plugin. Only use when you have issues with AJAX!', 'wp-menu-cart' ),
					),
					'show_if'  => apply_filters( 'wpo_wpmenucart_enable_builtin_ajax_setting', ( class_exists( 'WooCommerce' ) && isset( $option_values['builtin_ajax'] ) ) || class_exists( 'Easy_Digital_Downloads' ) ),
				),
			);

			$fields = apply_filters( 'wpo_wpmenucart_main_settings_fields', $fields, $option_name );

			foreach ( $fields as $field_id => $field ) {
				// The fixed show_if logic: Show if 'show_if' isn't set, or if it evaluates to true.
				if ( ! isset( $field['show_if'] ) || $field['show_if'] ) {
					add_settings_field(
						$field_id,
						$field['title'],
						$field['callback'],
						$field['page'] ?? $option_group,
						$field['section'],
						$field['args']
					);
				}
			}
		}

		/**
		 * Add the settings page to the WooCommerce (or Settings) menu.
		 *
		 * @return void
		 */
		public function add_menu_page(): void {
			if ( class_exists( 'WooCommerce' ) ) {
				$parent_slug = 'woocommerce';
			} else {
				$parent_slug = 'options-general.php';
			}

			$page_hook = add_submenu_page(
				$parent_slug,
				__( 'Menu Cart', 'wp-menu-cart' ),
				__( 'Menu Cart Setup', 'wp-menu-cart' ),
				'manage_options',
				'wpo_wpmenucart_options_page',
				array( $this, 'render_settings_page' )
			);

			add_action( 'admin_print_styles-' . $page_hook, array( $this, 'enqueue_admin_styles' ) );
		}

		/**
		 * Add settings link to the plugins list page.
		 *
		 * @param  array $links
		 *
		 * @return array
		 */
		public function add_settings_link( array $links ): array {
			$settings_link = '<a href="admin.php?page=wpo_wpmenucart_options_page">' . __( 'Settings', 'wp-menu-cart' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		/**
		 * Enqueue icon and font CSS on the settings page.
		 *
		 * @return void
		 */
		public function enqueue_admin_styles(): void {
			wp_enqueue_style(
				'wpmenucart-admin',
				WPO_Menu_Cart()->assets->get_asset_url( 'wpmenucart-icons' ),
				array(),
				WPMENUCART_VERSION,
			);

			wp_enqueue_style(
				'wpmenucart-font',
				WPO_Menu_Cart()->assets->get_asset_url( 'wpmenucart-font' ),
				array(),
				WPMENUCART_VERSION
			);
		}

		/**
		 * Render the full settings page shell including tab navigation.
		 *
		 * @return void
		 */
		public function render_settings_page(): void {
			settings_errors();

			$current_tab = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : 'cart_design_behavior';

			/**
			 * Filter the settings tabs.
			 *
			 * Each entry is tab_slug => tab_label. Extensions add their own tabs here.
			 *
			 * @param array  $tabs        Tab slug => label pairs.
			 * @param string $current_tab The currently active tab slug.
			 */
			$settings_tabs = apply_filters(
				'wpo_wpmenucart_settings_tabs',
				array(
					'cart_design_behavior' => __( 'Cart Design & Behavior', 'wp-menu-cart' ),
					'global_settings'      => __( 'Global Settings', 'wp-menu-cart' ),
				),
				$current_tab
			);

			// Guard against an unknown tab being requested.
			if ( ! array_key_exists( $current_tab, $settings_tabs ) ) {
				$current_tab = 'cart_design_behavior';
			}
			?>
			<div class="wrap">
				<div class="wpo_wpmenucart_settings">
					<h2><?php esc_html_e( 'WP Menu Cart', 'wp-menu-cart' ); ?></h2>

					<?php do_action( 'wpo_wpmenucart_before_settings_tabs', $current_tab ); ?>
					<?php do_action_deprecated( 'wpo_wpmenucart_before_settings_content', array( $current_tab ), '3.0.1', 'wpo_wpmenucart_before_settings_tabs' ); ?>

					<h2 class="nav-tab-wrapper">
						<?php
						foreach ( $settings_tabs as $tab_slug => $tab_label ) {
							$tab_url = add_query_arg(
								array(
									'page' => 'wpo_wpmenucart_options_page',
									'tab'  => $tab_slug,
								),
								admin_url( 'admin.php' )
							);
							printf(
								'<a href="%s" class="nav-tab%s">%s</a>',
								esc_url( $tab_url ),
								( $current_tab === $tab_slug ) ? ' nav-tab-active' : '',
								esc_html( $tab_label )
							);
						}
						?>
					</h2>

					<?php do_action( 'wpo_wpmenucart_after_settings_tabs', $current_tab ); ?>

					<?php $this->render_subtab_nav( $current_tab ); ?>

					<div class="wpo_wpmenucart_settings_container">
						<?php do_action( 'wpo_wpmenucart_before_settings_tab_content', $current_tab ); ?>
						<?php do_action_deprecated( 'wpo_wpmenucart_settings_content', array( $current_tab ), '3.0.1', 'wpo_wpmenucart_settings_tab_content_' . $current_tab ); ?>

						<div class="wpo_wpmenucart_settings_tab">
							<?php $this->render_tab_content( $current_tab ); ?>
						</div>

						<?php do_action( 'wpo_wpmenucart_after_settings_tab_content', $current_tab ); ?>
						<?php do_action_deprecated( 'wpo_wpmenucart_after_settings_content', array( $current_tab ), '3.0.1', 'wpo_wpmenucart_after_settings_tab_content' ); ?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Render the subtab nav for a given top-level tab.
		 *
		 * Falls back to a hook for any tab not registered here, so a tab
		 * added via wpo_wpmenucart_settings_tabs can still have its own
		 * subtab nav.
		 *
		 * @param  string $tab The current top-level tab slug.
		 * @return void
		 */
		protected function render_subtab_nav( string $tab ): void {
			switch ( $tab ) {
				case 'cart_design_behavior':
					$this->render_cart_design_behavior_subtab_nav();
					break;
				case 'global_settings':
					$this->render_global_settings_subtab_nav();
					break;
				default:
					do_action( 'wpo_wpmenucart_settings_subtab_nav_' . $tab );
			}
		}

		/**
		 * Render the main content for a given top-level tab, same fallback
		 * logic as render_subtab_nav().
		 *
		 * @param  string $tab The current top-level tab slug.
		 * @return void
		 */
		protected function render_tab_content( string $tab ): void {
			switch ( $tab ) {
				case 'cart_design_behavior':
					$this->render_cart_design_behavior_tab();
					break;
				case 'global_settings':
					$this->render_global_settings_tab();
					break;
				default:
					do_action( 'wpo_wpmenucart_settings_tab_content_' . $tab );
			}
		}

		/**
		 * Render the Cart Design & Behavior subtab nav.
		 *
		 * @return void
		 */
		public function render_cart_design_behavior_subtab_nav(): void {
			?>
			<div class="nav-tab-wrapper wpmenucart-subtab-nav">
				<a href="#" class="nav-tab nav-tab-active" data-subtab="display_modes"><?php esc_html_e( 'Cart Display Modes', 'wp-menu-cart' ); ?></a>
				<a href="#" class="nav-tab" data-subtab="icon_style"><?php esc_html_e( 'Menu Icon Style', 'wp-menu-cart' ); ?></a>
				<?php do_action( 'wpo_wpmenucart_cart_design_behavior_subtab_nav' ); ?>
			</div>
			<?php
		}

		/**
		 * Render the Cart Design & Behavior tab content: a subtab nav plus
		 * panels for Cart Display Modes and Menu Icon Style, switched
		 * client-side. Both subtabs save to the same option, so they share
		 * one form.
		 *
		 * @return void
		 */
		public function render_cart_design_behavior_tab(): void {
			$this->maybe_render_nav_error_notice();
			?>
			<form method="post" action="options.php" id="wpo-wpmenucart-settings">
				<?php settings_fields( self::OPTION_NAME ); ?>

				<div class="wpmenucart-subtab-panel wpmenucart-subtab-panel--active" data-subtab="display_modes">
					<?php do_settings_sections( self::PAGE_DISPLAY_MODES ); ?>
				</div>

				<div class="wpmenucart-subtab-panel" data-subtab="icon_style">
					<?php do_settings_sections( self::PAGE_ICON_STYLE ); ?>
				</div>

				<?php
				do_action( 'wpo_wpmenucart_cart_design_behavior_subtab_panels' );

				submit_button();
				?>
			</form>

			<?php do_action( 'wpo_wpmenucart_cart_design_behavior_after_subtab_panels' ); ?>

			<?php
			if ( apply_filters( 'wpo_wpmenucart_show_upgrade_ad', true ) ) {
				$this->render_pro_ad();
			}
		}

		/**
		 * Render the Global Settings subtab nav.
		 *
		 * @return void
		 */
		public function render_global_settings_subtab_nav(): void {
			?>
			<div class="nav-tab-wrapper wpmenucart-subtab-nav">
				<a href="#" class="nav-tab nav-tab-active" data-subtab="general"><?php esc_html_e( 'General', 'wp-menu-cart' ); ?></a>
				<?php do_action( 'wpo_wpmenucart_global_settings_subtab_nav' ); ?>
			</div>
			<?php
		}

		/**
		 * Render the Global Settings tab content.
		 *
		 * @return void
		 */
		public function render_global_settings_tab(): void {
			?>
			<form method="post" action="options.php" id="wpo-wpmenucart-settings">
				<?php settings_fields( self::OPTION_NAME ); ?>

				<div class="wpmenucart-subtab-panel wpmenucart-subtab-panel--active" data-subtab="general">
					<?php do_settings_sections( self::PAGE_GENERAL ); ?>
				</div>

				<?php
				do_action( 'wpo_wpmenucart_global_settings_subtab_panels' );

				submit_button();
				?>
			</form>

			<?php
			do_action( 'wpo_wpmenucart_global_settings_after_subtab_panels' );
		}

		/**
		 * Show a nav error notice or migration info notice above the settings form.
		 *
		 * @return void
		 */
		protected function maybe_render_nav_error_notice(): void {
			if ( ! $this->callbacks->get_menu_array() && ! WPO_Menu_Cart()->is_block_theme() ) {
				?>
				<div class="notice notice-error inline">
					<p><?php echo wp_kses_post( 'You need to create a menu before you can use Menu Cart. Go to <strong>Appearance > Menus</strong> and create a menu to add the cart to.', 'wp-menu-cart' ); ?></p>
				</div>
				<?php
			}

			if ( get_option( 'wpo_wpmenucart_nav_menu_migrated' ) && ! WPO_Menu_Cart()->is_block_theme() ) {
				WPO_Menu_Cart()->render_dismissible_notice(
					'wpo-wpmenucart-nav-menu-notice',
					wp_kses_post( sprintf(
						/* translators: %s: link to Appearance > Menus */
						__( 'The Menu Cart item is now added via %s, just like any other menu item.', 'wp-menu-cart' ),
						'<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Appearance &gt; Menus', 'wp-menu-cart' ) . '</a>'
					) ),
					'wpo_wpmenucart_nav_menu_notice_dismissed'
				);

				if ( apply_filters( 'wpo_wpmenucart_show_multiple_menus_notice', true ) ) : ?>
					<div class="notice notice-info inline">
						<p class="description"><?php echo wp_kses_post( sprintf(
							/* translators: %s: Menu Cart Pro link */
							__( 'Adding the cart to multiple menus is available in %s.', 'wp-menu-cart' ),
							'<a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartmultiplemenus" target="_blank" rel="noopener noreferrer">Menu Cart Pro</a>'
						) ); ?></p>
					</div>
				<?php endif;
			}
		}

		/**
		 * Render the Pro upgrade ad shown at the bottom of the Main tab.
		 *
		 * @return void
		 */
		protected function render_pro_ad(): void {
			?>
			<div class="menucart-pro-ad menucart-pro-ad-small">
				<?php esc_html_e( 'Want To Stand Out?', 'wp-menu-cart' ); ?> <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartgopro"><?php esc_html_e( 'Go Pro.', 'wp-menu-cart' ); ?></a>
				<ul style="font-size: 12px;list-style-type:circle;margin-left: 20px">
					<li><?php esc_html_e( 'Unlimited Menus', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Choice of 14 icons', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Packed with customization options', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Access to Shortcode', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Top Notch Support', 'wp-menu-cart' ); ?></li>
				</ul>
			</div>
			<div class="menucart-pro-ad menucart-pro-ad-big">
				<img src="<?php echo esc_url( WPO_Menu_Cart()->plugin_url() . '/assets/images/wpo-helper.png' ); ?>" class="wpo-helper">
				<h2><?php esc_html_e( 'Sell In Style With Menu Cart Pro!', 'wp-menu-cart' ); ?></h2>
				<br>
				<?php esc_html_e( 'Go Pro with Menu Cart Pro. Includes all the great standard features found in this free version plus:', 'wp-menu-cart' ); ?>
				<br>
				<ul style="list-style-type:circle;margin-left: 40px">
					<li><?php esc_html_e( 'A choice of over 10 cart icons', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'A fully featured cart details flyout', 'wp-menu-cart' ); ?></li>
					<li><?php echo wp_kses_post( 'Ability to add cart + flyout to an <strong>unlimited</strong> amount of menus', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Adjust the content & URLs via the settings', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Enter custom styles and apply custom classes via the settings', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'WPML compatible', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Automatic updates on any great new features', 'wp-menu-cart' ); ?></li>
					<li><?php esc_html_e( 'Put the cart anywhere with the [wpmenucart] shortcode', 'wp-menu-cart' ); ?></li>
				</ul>
				<?php
				printf(
					/* translators: 1,2: <a> tags */
					esc_html__( 'Need to see more? %1$sClick here%2$s to check it out. Add a product to your cart and watch what happens!', 'wp-menu-cart' ),
					'<a href="' . esc_url( 'https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadmore' ) . '">',
					'</a>'
				);
				?>
				<br><br>
				<a class="button button-primary" style="text-align: center;margin: 0px auto" href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadbuy"><?php esc_html_e( 'Buy Now', 'wp-menu-cart' ); ?></a>
			</div>
			<?php
		}

		/**
		 * Set default option values.
		 *
		 * @return void
		 */
		public function default_settings(): void {
			$active_shop_plugins = WPO_Menu_Cart()->get_active_shops();
			// array_key_first returns 'WooCommerce', 'Easy Digital Downloads', etc.
			$first_active        = ! empty( $active_shop_plugins ) ? array_key_first( $active_shop_plugins ) : '';

			$default = array(
				'desktop_cart_mode'       => 'none',
				'mobile_cart_mode'        => 'none',
				'desktop_sidebar_width'   => 360,
				'desktop_overlay_opacity' => 40,
				'mobile_sidebar_width'    => 360,
				'mobile_overlay_opacity'  => 40,
				'always_display'          => '',
				'icon_display'            => '1',
				'items_display'           => '3',
				'custom_class'            => '',
				'cart_icon'               => '0',
				'shop_plugin'             => $first_active,
				'builtin_ajax'            => '',
				'hide_theme_cart'         => 1,
			);

			update_option( self::OPTION_NAME, $default );
		}

		/**
		 * Get array of active shop plugins formatted for the select field.
		 *
		 * @return array plugin_folder => plugin_name
		 */
		public function get_shop_plugins(): array {
			$active_shop_plugins          = WPO_Menu_Cart()->get_active_shops();
			$filtered_active_shop_plugins = array();

			foreach ( $active_shop_plugins as $key => $value ) {
				// Use the human-readable name as both key and label.
				$filtered_active_shop_plugins[ $key ] = $key;
			}

			return $filtered_active_shop_plugins;
		}

	}

endif; // class_exists
