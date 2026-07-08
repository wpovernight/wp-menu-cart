<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Assets' ) ) :

	class WpMenuCart_Assets {

		/**
		 * @var string
		 */
		public $asset_suffix;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->asset_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_assets' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );

			// Gutenberg blocks.
			add_action( 'wp_default_styles', array( $this, 'load_block_editor_styles' ), 99 );
			add_action( 'init', array( $this, 'register_blocks_scripts' ) );

			add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', array( $this, 'wc_block_support_script' ) );
			add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', array( $this, 'wc_block_support_script' ) );
		}

		/**
		 * Get asset url.
		 * 
		 * @param string $name Name of the file.
		 * @param string $type Type of the file.
		 * 
		 * @return string
		 */
		public function get_asset_url( string $name, string $type = 'css' ): string {
			return WPO_Menu_Cart()->plugin_url() . '/assets/' . $type . '/' . $name . $this->asset_suffix . '.' . $type;
		}

		/**
		 * Get asset path.
		 * 
		 * @param string $name Name of the file.
		 * @param string $type Type of the file.
		 * 
		 * @return string
		 */
		public function get_asset_path( string $name, string $type = 'css' ): string {
			return WPO_Menu_Cart()->plugin_path() . '/assets/' . $type . '/' . $name . $this->asset_suffix . '.' . $type;
		}

		/**
		 * Allow wpmenucart-main.css to be overriden via the theme.
		 * 
		 * @return string
		 */
		public function get_main_css_url(): string {
			return file_exists( get_stylesheet_directory() . '/wpmenucart-main.css' ) ? get_stylesheet_directory_uri() . '/wpmenucart-main.css' : $this->get_asset_url( 'wpmenucart-main' );
		}

		/**
		 * In order to avoid issues with relative font paths, we parse the CSS file to print it inline.
		 * 
		 * @return string
		 */
		public function get_parsed_font_css(): string {
			ob_start();

			if ( file_exists( $this->get_asset_path( 'wpmenucart-font' ) ) ) {
				include( $this->get_asset_path( 'wpmenucart-font' ) );
			}

			$font_css = str_replace( '../fonts', WPO_Menu_Cart()->plugin_url() . '/assets/fonts', ob_get_clean() );

			return $font_css;
		}

		/**
		 * Get AJAX action
		 *
		 * @return string
		 */
		public function get_ajax_action(): string {
			return apply_filters( 'wpmenucart_ajax_action', 'wpmenucart_ajax' );
		}

		/**
		 * Load frontend assets.
		 * 
		 * @return void
		 */
		public function load_frontend_assets(): void {
			if ( isset( WPO_Menu_Cart()->main_settings['icon_display'] ) ) {
				wp_enqueue_style(
					'wpmenucart-icons',
					$this->get_asset_url( 'wpmenucart-icons' ),
					array(),
					WPMENUCART_VERSION
				);
				wp_add_inline_style( 'wpmenucart-icons', $this->get_parsed_font_css() );
			}

			wp_enqueue_style(
				'wpmenucart',
				$this->get_main_css_url(),
				array(),
				WPMENUCART_VERSION
			);

			// Hide built-in theme carts
			if ( isset( WPO_Menu_Cart()->main_settings['hide_theme_cart'] ) ) {
				wp_add_inline_style( 'wpmenucart', '.et-cart-info { display:none !important; } .site-header-cart { display:none !important; }' );
			}

			if ( isset( WPO_Menu_Cart()->main_settings['builtin_ajax'] ) ) {
				wp_enqueue_script(
					'wpmenucart',
					$this->get_asset_url( 'wpmenucart', 'js' ),
					array( 'jquery' ),
					WPMENUCART_VERSION,
					true
				);

				// get URL to WordPress ajax handling page  
				if ( in_array( WPO_Menu_Cart()->main_settings['shop_plugin'], array( 'Easy Digital Downloads', 'Easy Digital Downloads Pro' ) ) && function_exists( 'edd_get_ajax_url' ) ) {
					// use EDD function to prevent SSL issues http://git.io/V7w76A
					$ajax_url = edd_get_ajax_url();
				} else {
					$ajax_url = admin_url( 'admin-ajax.php' );
				}

				wp_localize_script(
					'wpmenucart',
					'wpmenucart_ajax',
					array(
						'ajaxurl'        => $ajax_url,
						'nonce'          => wp_create_nonce( 'wpmenucart' ),
						'action'         => $this->get_ajax_action(),
						'always_display' => isset( WPO_Menu_Cart()->main_settings['always_display'] ) ? '1' : '0',
					)
				);
			}

			// Load Stylesheet if twentytwelve is active
			if ( 'Twenty Twelve' === wp_get_theme()->get( 'Name' ) ) {
				wp_enqueue_style(
					'wpmenucart-twentytwelve',
					$this->get_asset_url( 'wpmenucart-twentytwelve' ),
					array(),
					WPMENUCART_VERSION
				);
			}

			// Load Stylesheet if twentyfourteen is active
			if ( 'Twenty Fourteen' === wp_get_theme()->get( 'Name' ) ) {
				wp_enqueue_style(
					'wpmenucart-twentyfourteen',
					$this->get_asset_url( 'wpmenucart-twentyfourteen' ),
					array(),
					WPMENUCART_VERSION
				);
			}

			// extra script that improves AJAX behavior when 'Always display cart' is disabled
			if ( ! isset( WPO_Menu_Cart()->main_settings['always_display'] ) ) {
				wp_enqueue_script(
					'wpmenucart-ajax-assist',
					$this->get_asset_url( 'wpmenucart-ajax-assist', 'js' ),
					array( 'jquery' ),
					WPMENUCART_VERSION,
					true
				);
				wp_localize_script(
					'wpmenucart-ajax-assist',
					'wpmenucart_ajax_assist',
					array(
						'shop_plugin'    => isset( WPO_Menu_Cart()->main_settings['shop_plugin'] ) ? WPO_Menu_Cart()->main_settings['shop_plugin'] : '',
						'always_display' => isset( WPO_Menu_Cart()->main_settings['always_display'] ) ? WPO_Menu_Cart()->main_settings['always_display'] : '',
					)
				);
			}

			if ( isset( WPO_Menu_Cart()->main_settings['show_on_cart_checkout_page'] ) && function_exists( 'is_checkout' ) && function_exists( 'is_cart' ) && ( is_checkout() || is_cart() ) && version_compare( WC_VERSION, '7.7', '>' ) ) {
				wp_enqueue_script( 'wc-cart-fragments' );
			}

			if ( WPO_Menu_Cart()->is_shop_active() ) {
				wp_enqueue_script(
					'wpmenucart-remove',
					$this->get_asset_url( 'wpmenucart-remove', 'js' ),
					array( 'jquery' ),
					WPMENUCART_VERSION,
					true
				);
				wp_localize_script( 'wpmenucart-remove', 'wpmenucart_ajax', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpmenucart' ),
				) );
			}

			// Slideout assets
			if ( WPO_Menu_Cart()->main->is_cart_mode_active( 'sidebar' ) ) {
				wp_enqueue_style(
					'wpmenucart-slideout',
					$this->get_asset_url( 'wpmenucart-slideout' ),
					array(),
					WPMENUCART_VERSION
				);

				$desktop_width   = intval( WPO_Menu_Cart()->main_settings['desktop_sidebar_width'] ?? 360 );
				$desktop_overlay = round( intval( WPO_Menu_Cart()->main_settings['desktop_overlay_opacity'] ?? 40 ) / 100, 2 );
				$mobile_width    = intval( WPO_Menu_Cart()->main_settings['mobile_sidebar_width'] ?? 360 );
				$mobile_overlay  = round( intval( WPO_Menu_Cart()->main_settings['mobile_overlay_opacity'] ?? 40 ) / 100, 2 );
				
				// Inject variables into :root for immediate availability and AJAX persistence
				$vars = "
					:root {
						--wpmenucart-desktop-width: {$desktop_width}px;
						--wpmenucart-desktop-overlay: {$desktop_overlay};
						--wpmenucart-mobile-width: {$mobile_width}px;
						--wpmenucart-mobile-overlay: {$mobile_overlay};
					}
				";
				wp_add_inline_style( 'wpmenucart-slideout', $vars );

				wp_enqueue_script(
					'wpmenucart-slideout',
					$this->get_asset_url( 'wpmenucart-slideout', 'js' ),
					array( 'jquery' ),
					WPMENUCART_VERSION,
					true
				);

				wp_localize_script(
					'wpmenucart-slideout',
					'wpmenucart_slideout',
					array(
						'mobile_breakpoint' => 768,
					)
				);
			}
		}

		/**
		 * Load admin assets.
		 * 
		 * @return void
		 */
		public function load_admin_assets(): void {
			$screen = get_current_screen();

			if ( $screen && in_array( $screen->id, array( 'woocommerce_page_wpo_wpmenucart_options_page', 'settings_page_wpo_wpmenucart_options_page' ) ) ) {
				wp_enqueue_style(
					'wpmenucart-settings-css',
					$this->get_asset_url( 'wpmenucart-settings' ),
					array(),
					WPMENUCART_VERSION
				);

				wp_enqueue_script(
					'wpmenucart-settings-js',
					$this->get_asset_url( 'wpmenucart-settings', 'js' ),
					array( 'jquery' ),
					WPMENUCART_VERSION,
					true
				);
			}
		}

		/**
		 * Load Block Editor CSS.
		 * 
		 * @param WP_Styles $wp_styles
		 * 
		 * @return void
		 */
		public function load_block_editor_styles( WP_Styles $wp_styles ): void {
			$wp_edit_blocks = $wp_styles->query( 'wp-edit-blocks', 'registered' );
			$handles        = array(
				'wpmenucart-icons',
				'wpmenucart',
			);

			if ( ! $wp_edit_blocks ) {
				return;
			}

			// add handle css as 'wp-edit-blocks' dependency
			foreach ( $handles as $handle ) {
				$style = $wp_styles->query( $handle, 'registered' );
				if ( ! $style ) {
					$wp_styles->add(
						'wpmenucart-icons',
						$this->get_asset_url( 'wpmenucart-icons' ),
						array(),
						WPMENUCART_VERSION
					);
					$wp_styles->add(
						'wpmenucart',
						$this->get_main_css_url(),
						array(),
						WPMENUCART_VERSION
					);
				}
				if ( $wp_styles->query( $handle, 'registered' ) && ! in_array( $handle, $wp_edit_blocks->deps, true ) ) {
					$wp_edit_blocks->deps[] = $handle;
				}
			}

			// add inline font css
			$wp_styles->add_inline_style( 'wp-edit-blocks', $this->get_parsed_font_css() );

			// Hide built-in theme carts in the block editor.
			if ( isset( WPO_Menu_Cart()->main_settings['hide_theme_cart'] ) ) {
				$wp_styles->add_inline_style( 'wpmenucart', '.et-cart-info { display:none !important; } .site-header-cart { display:none !important; }' );
			}
		}

		/**
		 * Register block scripts.
		 *
		 * @return void
		 */
		public function register_blocks_scripts(): void {
			if ( function_exists( 'register_block_type' ) ) {
				wp_register_script(
					'wpmenucart-navigation-block',
					$this->get_asset_url( 'wpmenucart-navigation-block', 'js' ),
					array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
					WPMENUCART_VERSION,
					true
				);

				register_block_type( 'wpo/wpmenucart-navigation', array(
					'editor_script'   => 'wpmenucart-navigation-block',
					'render_callback' => array( WPO_Menu_Cart()->main, 'navigation_block_output' ),
				) );
			}
		}

		/**
		 * Enqueue script after WooCommerce Cart and Checkout block.
		 * 
		 * @return void
		 */
		public function wc_block_support_script(): void {
			wp_enqueue_script(
				'wpmenucart-wc-block-support',
				$this->get_asset_url( 'wpmenucart-wc-block-support', 'js' ),
				array( 'jquery' ),
				WPMENUCART_VERSION,
				true
			);

			wp_localize_script(
				'wpmenucart-wc-block-support',
				'wpmenucart_cart_ajax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpmenucart' ),
					'action'  => $this->get_ajax_action()
				)
			);
		}

		/**
		 * Load EDD ajax helper.
		 * 
		 * @return void
		 */
		public function load_edd_ajax(): void {
			wp_enqueue_script(
				'wpmenucart-edd-ajax',
				$this->get_asset_url( 'wpmenucart-edd-ajax', 'js' ),
				array( 'jquery' ),
				WPMENUCART_VERSION,
				true
			);

			wp_localize_script(
				'wpmenucart-edd-ajax',
				'wpmenucart_ajax',
				array(
					'ajaxurl'        => function_exists( 'edd_get_ajax_url' ) ? edd_get_ajax_url() : admin_url( 'admin-ajax.php' ),
					'nonce'          => wp_create_nonce( 'wpmenucart' ),
					'action'         => $this->get_ajax_action(),
					'always_display' => isset( WPO_Menu_Cart()->main_settings['always_display'] ) ? WPO_Menu_Cart()->main_settings['always_display'] : '',
				)
			);
		}

	}

endif;
