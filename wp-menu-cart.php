<?php
/**
 * Plugin Name: WP Menu Cart
 * Plugin URI: https://wpovernight.com/downloads/menu-cart-pro/
 * Description: Extension for your e-commerce plugin (WooCommerce, WP-Ecommerce, Easy Digital Downloads, Eshop or Jigoshop) that places a cart icon with number of items and total cost in the menu bar. Activate the plugin, set your options and you're ready to go! Will automatically conform to your theme styles.
 * Version: 2.10.4
 * Author: WP Overnight
 * Author URI: https://wpovernight.com/
 * License: GPLv2 or later
 * License URI: https://opensource.org/licenses/gpl-license.php
 * Text Domain: wp-menu-cart
 * WC requires at least: 2.0.0
 * WC tested up to: 6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart' ) && ! class_exists( 'WPO_Menu_Cart_Pro' ) ) :

class WpMenuCart {	 

	protected     $plugin_version   = '2.10.4';
	public static $plugin_slug;
	public static $plugin_basename;
	public        $suffix;

	/**
	 * Construct.
	 */
	public function __construct() {
		self::$plugin_slug = basename(dirname(__FILE__));
		self::$plugin_basename = plugin_basename(__FILE__);

		$this->options = get_option('wpmenucart');

		$this->define( 'WPMENUCART_VERSION', $this->plugin_version );

		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// load the localisation & classes
		add_action( 'plugins_loaded', array( &$this, 'languages' ), 0 ); // or use init?
		add_filter( 'load_textdomain_mofile', array( $this, 'textdomain_fallback' ), 10, 2 );
		add_action( 'init', array( &$this, 'wpml' ), 0 );
		add_action( 'init', array( $this, 'load_classes' ) );

		// enqueue scripts & styles
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts_styles' ) );                   // load frontend scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_font_in_block_editor' ) );          // load font in block editor
		add_action( 'init', array( &$this, 'register_cart_navigation_block' ) );                      // register cart navigation block
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_admin_block_editor_styles' ), 99 ); // load admin block editor styles

		// AJAX
		add_action( 'wp_ajax_wpmenucart_ajax', array( &$this, 'wpmenucart_ajax' ), 0 );
		add_action( 'wp_ajax_nopriv_wpmenucart_ajax', array( &$this, 'wpmenucart_ajax' ), 0 );

		// add filters to selected menus to add cart item <li>
		add_action( 'init', array( $this, 'filter_nav_menus' ) );
		// $this->filter_nav_menus();
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load classes
	 * @return void
	 */
	public function load_classes() {
		include_once( 'includes/wpmenucart-settings.php' );
		$this->settings = new WpMenuCart_Settings();

		if ( $this->good_to_go() ) {
			if (isset($this->options['shop_plugin'])) {
				if ( false === $this->is_shop_active( $this->options['shop_plugin'] ) ) {
					return;
				}
				switch ($this->options['shop_plugin']) {
					case 'woocommerce':
						include_once( 'includes/wpmenucart-woocommerce.php' );
						$this->shop = new WPMenuCart_WooCommerce();
						if ( !isset($this->options['builtin_ajax']) ) {
							if ( defined('WOOCOMMERCE_VERSION') && version_compare( WOOCOMMERCE_VERSION, '2.7', '>=' ) ) {
								add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'woocommerce_ajax_fragments' ) );
							} else {
								add_filter( 'add_to_cart_fragments', array( $this, 'woocommerce_ajax_fragments' ) );
							}
						}
						break;
					case 'jigoshop':
						include_once( 'includes/wpmenucart-jigoshop.php' );
						$this->shop = new WPMenuCart_Jigoshop();
						if ( !isset($this->options['builtin_ajax']) ) {
							add_filter( 'add_to_cart_fragments', array( &$this, 'woocommerce_ajax_fragments' ) );
						}
						break;
					case 'wp-e-commerce':
						include_once( 'includes/wpmenucart-wpec.php' );
						$this->shop = new WPMenuCart_WPEC();
						break;
					case 'eshop':
						include_once( 'includes/wpmenucart-eshop.php' );
						$this->shop = new WPMenuCart_eShop();
						break;
					case 'easy-digital-downloads':
						include_once( 'includes/wpmenucart-edd.php' );
						$this->shop = new WPMenuCart_EDD();
						if ( !isset($this->options['builtin_ajax']) ) {
							add_action("wp_enqueue_scripts", array( &$this, 'load_edd_ajax' ), 0 );
						}
						break;
				}
				if ( isset( $this->options['builtin_ajax'] ) || in_array( $this->options['shop_plugin'], array( 'WP e-Commerce', 'wp-e-commerce', 'eShop', 'eshop' ) ) ) {
					add_action( 'wp_enqueue_scripts', array( &$this, 'load_custom_ajax' ), 0 );
				}

			}
		}
	}

	/**
	 * Check if a shop is active or if conflicting old versions of the plugin are active
	 * @return boolean
	 */
	public function good_to_go() {
		$wpmenucart_shop_check = get_option( 'wpmenucart_shop_check' );
		$active_plugins = $this->get_active_plugins();

		// check for shop plugins
		if ( !$this->is_shop_active() && $wpmenucart_shop_check != 'hide' ) {
			add_action( 'admin_notices', array ( $this, 'need_shop' ) );
			return FALSE;
		}

		// check for old versions
		if ( count( $this->get_active_old_versions() ) > 0 ) {
			add_action( 'admin_notices', array ( $this, 'woocommerce_version_active' ) );
			return FALSE;
		}

		// we made it! good to go :o)
		return TRUE;
	}

	/**
	 * Return true if one ore more shops are activated.
	 * @return boolean
	 */
	public function is_shop_active( $shop = '' ) {
		if ( empty($shop) ) {
			if ( count( $this->get_active_shops() ) > 0 ) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			switch ( $shop ) {
				case 'woocommerce':
					return function_exists('WC');
					break;
				case 'easy-digital-downloads':
					return function_exists('EDD');
					break;
				case 'jigoshop':
					return class_exists('jigoshop_cart');
					break;
				case 'wp-e-commerce':
					return function_exists('wpsc_cart_item_count');
					break;
				case 'eshop':
					return !empty($GLOBALS['eshopoptions']);
					break;
				default:
					return false;
					break;
			}
		}
	}

	/**
	 * Get an array of all active plugins, including multisite
	 * @return array active plugin paths
	 */
	public static function get_active_plugins() {
		$active_plugins = (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		if (is_multisite()) {
			// get_site_option( 'active_sitewide_plugins', array() ) returns a 'reversed list'
			// like [hello-dolly/hello.php] => 1369572703 so we do array_keys to make the array
			// compatible with $active_plugins
			$active_sitewide_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			// merge arrays and remove doubles
			$active_plugins = (array) array_unique( array_merge( $active_plugins, $active_sitewide_plugins ) );
		}

		return $active_plugins;
	}
	
	/**
	 * Get array of active shop plugins
	 * 
	 * @return array plugin name => plugin path
	 */
	public static function get_active_shops() {
		$active_plugins = self::get_active_plugins();

		$shop_plugins = array (
			'WooCommerce'				=> 'woocommerce/woocommerce.php',
			'Jigoshop'					=> 'jigoshop/jigoshop.php',
			'WP e-Commerce'				=> 'wp-e-commerce/wp-shopping-cart.php',
			'eShop'						=> 'eshop/eshop.php',
			'Easy Digital Downloads'	=> 'easy-digital-downloads/easy-digital-downloads.php',
		);
		
		// filter shop plugins & add shop names as keys
		$active_shop_plugins = array_intersect( $shop_plugins, $active_plugins );

		return $active_shop_plugins;
	}

	/**
	 * Get array of active old WooCommerce Menu Cart plugins
	 * 
	 * @return array plugin paths
	 */
	public function get_active_old_versions() {
		$active_plugins = $this->get_active_plugins();
		
		$old_versions = array (
			'woocommerce-menu-bar-cart/wc_cart_nav.php',				//first version
			'woocommerce-menu-bar-cart/woocommerce-menu-cart.php',		//last free version
			'woocommerce-menu-cart/woocommerce-menu-cart.php',			//never actually released? just in case...
			'woocommerce-menu-cart-pro/woocommerce-menu-cart-pro.php',	//old pro version
		);
			
		$active_old_plugins = array_intersect( $old_versions, $active_plugins );
				
		return $active_old_plugins;
	}	

	/**
	 * Fallback admin notices
	 *
	 * @return string Fallack notice.
	 */
	public function need_shop() {
		$error = __( 'WP Menu Cart could not detect an active shop plugin. Make sure you have activated at least one of the supported plugins.' , 'wp-menu-cart' );
		$message = sprintf('<div class="error"><p>%1$s <a href="%2$s">%3$s</a></p></div>', $error, add_query_arg( 'hide_wpmenucart_shop_check', 'true' ), __( 'Hide this notice', 'wp-menu-cart' ) );
		echo $message;
	}

	public function woocommerce_version_active() {
		$error = __( 'An old version of WooCommerce Menu Cart is currently activated, you need to disable or uninstall it for WP Menu Cart to function properly' , 'wp-menu-cart' );
		$message = '<div class="error"><p>' . $error . '</p></div>';
		echo $message;
	}

	/**
	 * Load translations.
	 */
	public function languages() {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'wp-menu-cart' );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/wp-menu-cart/wp-menu-cart-LOCALE.mo
		 * 	 	- wp-menu-cart/languages/wp-menu-cart-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wp-menu-cart-LOCALE.mo
		 */
		unload_textdomain( 'wp-menu-cart');
		load_textdomain( 'wp-menu-cart', WP_LANG_DIR . '/wp-menu-cart/wp-menu-cart-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp-menu-cart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Maintain textdomain compatibility between main plugin (wp-menu-cart) and WooCommerce version (woocommerce-menu-bar-cart)
	 * so that wordpress.org language packs can be used for both
	 */
	public function textdomain_fallback( $mofile, $textdomain ) {
		$main_domain = 'wp-menu-cart';
		$wc_domain = 'woocommerce-menu-bar-cart';

		// check if this is filtering the mofile for this plugin
		if ( $textdomain === $main_domain ) {
			$wc_mofile = str_replace( "{$textdomain}-", "{$wc_domain}-", $mofile ); // with trailing dash to target file and not folder
			if ( file_exists( $wc_mofile ) ) {
				if (!is_callable('copy')) {
					$copy = false;
				} elseif ( !file_exists( $mofile ) ) {
					$copy = true;
				} else { // can copy but file already exists
					$wc_file_date   = filemtime($wc_mofile);
					$main_file_date = filemtime($mofile);
					// check if wc file is newer
					if ( $wc_file_date && $main_file_date && ( $wc_file_date > $main_file_date ) ) {
						$copy = true;
					} else {
						$copy = false;
					}
				}
				// we have a wc override - copy and use it
				if ( $copy && $success = copy( $wc_mofile, $mofile ) ) {
					// copy .po too if available
					$wc_pofile = substr_replace($wc_mofile,".po",-3);
					if (file_exists($wc_pofile)) {
						copy($wc_pofile,substr_replace($mofile,".po",-3));
					}
					return $mofile;
				}
				return $wc_mofile;
			}
		}

		return $mofile;
	}

	/**
	* Register strings for WPML String Translation
	*/
	public function wpml() {
		if ( isset($this->options['wpml_string_translation']) && function_exists( 'icl_register_string' ) ) {
			icl_register_string('WP Menu Cart', 'item text', 'item');
			icl_register_string('WP Menu Cart', 'items text', 'items');
			icl_register_string('WP Menu Cart', 'empty cart text', 'your cart is currently empty');
			icl_register_string('WP Menu Cart', 'hover text', 'View your shopping cart');
			icl_register_string('WP Menu Cart', 'empty hover text', 'Start shopping');
		}
	}

	/**
	 * Load custom ajax
	 */
	public function load_custom_ajax() {
		wp_enqueue_script(
			'wpmenucart',
			plugins_url( '/assets/js/wpmenucart'.$this->suffix.'.js' , __FILE__ ),
			array( 'jquery' ),
			WPMENUCART_VERSION,
			true
		);

		// get URL to WordPress ajax handling page  
		if ( $this->options['shop_plugin'] == 'easy-digital-downloads' && function_exists( 'edd_get_ajax_url' ) ) {
			// use EDD function to prevent SSL issues http://git.io/V7w76A
			$ajax_url = edd_get_ajax_url();
		} else {
			$ajax_url = admin_url( 'admin-ajax.php' );
		}

		wp_localize_script(
			'wpmenucart',
			'wpmenucart_ajax',
			array(
				'ajaxurl' => $ajax_url,
				'nonce' => wp_create_nonce('wpmenucart')
			)
		);
	}

	/**
	 * Load EDD ajax helper
	 */
	public function load_edd_ajax() {
		wp_enqueue_script(
			'wpmenucart-edd-ajax',
			plugins_url( '/assets/js/wpmenucart-edd-ajax'.$this->suffix.'.js', __FILE__ ),
			array( 'jquery' ),
			WPMENUCART_VERSION
		);

		wp_localize_script(
			'wpmenucart-edd-ajax',
			'wpmenucart_ajax',
			array(  
				'ajaxurl'        => function_exists( 'edd_get_ajax_url' ) ? edd_get_ajax_url() : admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce('wpmenucart'),
				'always_display' => isset($this->options['always_display']) ? $this->options['always_display'] : '',
			)
		);
	}

	/*
	 * In order to avoid issues with relative font paths, we parse the CSS file to print it inline
	 */
	public function get_font_css() {
		ob_start();
		if ( file_exists( plugin_dir_path( __FILE__ ) . 'assets/css/wpmenucart-font'.$this->suffix.'.css' ) ) {
			include( plugin_dir_path( __FILE__ ) . 'assets/css/wpmenucart-font'.$this->suffix.'.css' ) ;
		}
		$font_css = str_replace( '../fonts', plugins_url( '/assets/fonts', __FILE__ ), ob_get_clean() );

		return $font_css;
	}

	/*
	 * Allow wpmenucart-main.css to be overriden via the theme
	 */
	public function get_main_css_url() {
		return file_exists( get_stylesheet_directory() . '/wpmenucart-main.css' ) ? get_stylesheet_directory_uri() . '/wpmenucart-main.css' : plugins_url( '/assets/css/wpmenucart-main'.$this->suffix.'.css', __FILE__ );
	}

	/**
	 * Load CSS
	 */
	public function load_scripts_styles() {
		if ( isset( $this->options['icon_display'] ) ) {
			wp_enqueue_style( 'wpmenucart-icons', plugins_url( '/assets/css/wpmenucart-icons'.$this->suffix.'.css', __FILE__ ), array(), WPMENUCART_VERSION, 'all' );
			wp_add_inline_style( 'wpmenucart-icons', $this->get_font_css() );
		}
		
		wp_enqueue_style( 'wpmenucart', $this->get_main_css_url(), array(), WPMENUCART_VERSION, 'all' );

		// Hide built-in theme carts
		if ( isset($this->options['hide_theme_cart']) ) {
			wp_add_inline_style( 'wpmenucart', '.et-cart-info { display:none !important; } .site-header-cart { display:none !important; }' );
		}

		//Load Stylesheet if twentytwelve is active
		if ( wp_get_theme() == 'Twenty Twelve' ) {
			wp_enqueue_style( 'wpmenucart-twentytwelve', plugins_url( '/assets/css/wpmenucart-twentytwelve'.$this->suffix.'.css', __FILE__ ), array(), WPMENUCART_VERSION, 'all' );
		}

		//Load Stylesheet if twentyfourteen is active
		if ( wp_get_theme() == 'Twenty Fourteen' ) {
			wp_enqueue_style( 'wpmenucart-twentyfourteen', plugins_url( '/assets/css/wpmenucart-twentyfourteen'.$this->suffix.'.css', __FILE__ ), array(), WPMENUCART_VERSION, 'all' );
		}

		//Load Stylesheet if twentyfourteen is active
		if ( wp_get_theme() == 'Twenty Fourteen' ) {
			wp_enqueue_style( 'wpmenucart-twentyfourteen', plugins_url( '/assets/css/wpmenucart-twentyfourteen'.$this->suffix.'.css', __FILE__ ), array(), WPMENUCART_VERSION, 'all' );
		}		

		// extra script that improves AJAX behavior when 'Always display cart' is disabled
		wp_enqueue_script(
			'wpmenucart-ajax-assist',
			plugins_url( '/assets/js/wpmenucart-ajax-assist'.$this->suffix.'.js', __FILE__ ),
			array( 'jquery' ),
			WPMENUCART_VERSION
		);
		wp_localize_script(
			'wpmenucart-ajax-assist',
			'wpmenucart_ajax_assist',
			array(  
				'shop_plugin'    => isset( $this->options['shop_plugin'] ) ? $this->options['shop_plugin'] : '',
				'always_display' => isset( $this->options['always_display'] ) ? $this->options['always_display'] : '',
			)
		);
	}

	public function load_font_in_block_editor() {
		wp_add_inline_style( 'wp-edit-blocks', $this->get_font_css() );
	}

	/*
	 * Inpired by this WC function https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/c631ea42feb01f7598540ba68758c7086ff5350e/src/AssetsController.php#L232-L250
	 */
	public function load_admin_block_editor_styles() {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			wp_register_style( 'wpmenucart-icons', plugins_url( '/assets/css/wpmenucart-icons'.$this->suffix.'.css', __FILE__ ), array(), WPMENUCART_VERSION, 'all' );
			wp_register_style( 'wpmenucart', $this->get_main_css_url(), array(), WPMENUCART_VERSION, 'all' );
		}

		$wp_styles       = wp_styles();
		$wc_blocks_style = $wp_styles->query( 'wc-blocks-style', 'registered' ); // to be used to attach our styles as dependencies
		$handles         = array(
			'wpmenucart-icons',
			'wpmenucart',
		);

		if ( ! $wc_blocks_style ) {
			return;
		}

		foreach ( $handles as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) && ! in_array( $handle, $wc_blocks_style->deps, true ) ) {
				$wc_blocks_style->deps[] = $handle;
			}
		}
	}

	public function register_cart_navigation_block() {
		wp_register_script(
			'wpmenucart-navigation-block',
			plugins_url( '/assets/js/wpmenucart-navigation-block'.$this->suffix.'.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			WPMENUCART_VERSION
		);

		register_block_type( 'wpo/wpmenucart-navigation', array(
			'editor_script'   => 'wpmenucart-navigation-block',
			'render_callback' => array( $this, 'cart_navigation_block_output' ),
		) );
	}

	public function cart_navigation_block_output( $atts ) {
		$this->shop->is_block_editor = true;
		return $this->wpmenucart_menu_item();
	}

	/**
	 * Add filters to selected menus to add cart item <li>
	 */
	public function filter_nav_menus() {
		// exit if no shop class is active
		if ( !isset($this->shop) )
			return;

		// exit if no menus set
		if ( !isset( $this->options['menu_slugs'] ) || empty( $this->options['menu_slugs'] ) )
			return;

		if ( $this->options['menu_slugs'][1] != '0' ) {
			add_filter( 'wp_nav_menu_' . $this->options['menu_slugs'][1] . '_items', array( &$this, 'add_itemcart_to_menu' ) , 10, 2 );
		}
	}
	
	/**
	 * Add Menu Cart to menu
	 * 
	 * @return menu items + Menu Cart item
	 */
	public function add_itemcart_to_menu( $items ) {
		// WooCommerce specific: check if woocommerce cart object is actually loaded
		if ( isset($this->options['shop_plugin']) && $this->options['shop_plugin'] == 'woocommerce' ) {
			if ( function_exists( 'WC' ) ) {
				if ( empty( WC()->cart ) ) {
					return $items; // nothing to load data from, return menu without cart item
				}
			} else {
				global $woocommerce;
				if ( empty($woocommerce) || !is_object($woocommerce) || !isset($woocommerce->cart) || !is_object($woocommerce->cart) ) {
					return $items; // nothing to load data from, return menu without cart item
				}
			}
		}

		$classes = 'menu-item wpmenucartli wpmenucart-display-'.$this->options['items_alignment'];

		if ($this->get_common_li_classes($items) != '') {
			$classes .= ' ' . $this->get_common_li_classes($items);
		}

		if ( function_exists( 'is_checkout' ) && function_exists( 'is_cart' ) && ( is_checkout() || is_cart() ) && empty($this->options['show_on_cart_checkout_page']) ) {
			$classes .= ' hidden-wpmenucart';
		}

		// Filter for <li> item classes
		/* Usage (in the themes functions.php):
		add_filter('wpmenucart_menu_item_classes', 'add_wpmenucart_item_class', 1, 1);
		function add_wpmenucart_item_class ($classes) {
			$classes .= ' yourclass';
			return $classes;
		}
		*/
		$classes = apply_filters( 'wpmenucart_menu_item_classes', $classes );
		$this->menu_items['menu']['menu_item_li_classes'] = $classes;

		// DEPRECATED: These filters are now deprecated in favour of the more precise filters in the functions!
		$wpmenucart_menu_item = apply_filters( 'wpmenucart_menu_item_filter', $this->wpmenucart_menu_item() );

		$item_data = $this->shop->menu_item();

		$menu_item_li = '<li class="'.$classes.'" id="wpmenucartli">' . $wpmenucart_menu_item . '</li>';

		if ( apply_filters('wpmenucart_prepend_menu_item', false) ) {
			$items = apply_filters( 'wpmenucart_menu_item_wrapper', $menu_item_li ) . $items;
		} else {
			$items .= apply_filters( 'wpmenucart_menu_item_wrapper', $menu_item_li );
		}

		return $items;
	}

	/**
	 * Get a flat list of common classes from all menu items in a menu
	 * @param  string $items nav_menu HTML containing all <li> menu items
	 * @return string        flat (imploded) list of common classes
	 */
	public function get_common_li_classes($items) {
		if (empty($items)) return '';
		if (!class_exists('DOMDocument')) return '';
		
		$libxml_previous_state = libxml_use_internal_errors(true); // enable user error handling

		$dom_items = new DOMDocument;
		$dom_items->loadHTML( $items );
		$lis = $dom_items->getElementsByTagName('li');
		
		if (empty($lis)) {
			libxml_clear_errors();
			libxml_use_internal_errors($libxml_previous_state);
			return;
		}
		
		foreach($lis as $li) {
			if ($li->parentNode->tagName != 'ul')
				$li_classes[] = explode( ' ', $li->getAttribute('class') );
		}
		
		// Uncomment to dump DOM errors / warnings
		//$errors = libxml_get_errors();
		//print_r ($errors);
		
		// clear errors and reset to previous error handling state
		libxml_clear_errors();
		libxml_use_internal_errors($libxml_previous_state);
		
		if ( !empty($li_classes) ) {
			$common_li_classes = array_shift($li_classes);
			foreach ($li_classes as $li_class) {
				$common_li_classes = array_intersect($li_class, $common_li_classes);
			}
			$common_li_classes_flat = implode(' ', $common_li_classes);
		} else {
			$common_li_classes_flat = '';
		}
		return $common_li_classes_flat;
	}

	/**
	 * Ajaxify Menu Cart
	 */
	public function woocommerce_ajax_fragments( $fragments ) {
		if ( ! defined('WOOCOMMERCE_CART') ) {
			define( 'WOOCOMMERCE_CART', true );
		}

		$fragments['a.wpmenucart-contents'] = $this->wpmenucart_menu_item();
		return $fragments;
	}

	/**
	 * Create HTML for Menu Cart item
	 */
	public function wpmenucart_menu_item() {
		$item_data = $this->shop->menu_item();

		// Check empty cart settings
		if ($item_data['cart_contents_count'] == 0 && ( !isset($this->options['always_display']) ) ) {
			$empty_menu_item = '<a class="wpmenucart-contents empty-wpmenucart" style="display:none">&nbsp;</a>';
			return $empty_menu_item;
		}
		
		if ( isset($this->options['wpml_string_translation']) && function_exists( 'icl_t' ) ) {
			//use WPML
			$viewing_cart = icl_t('WP Menu Cart', 'hover text', 'View your shopping cart');
			$start_shopping = icl_t('WP Menu Cart', 'empty hover text', 'Start shopping');
			$cart_contents = $item_data['cart_contents_count'] .' '. ( $item_data['cart_contents_count'] == 1 ?  icl_t('WP Menu Cart', 'item text', 'item') :  icl_t('WP Menu Cart', 'items text', 'items') );
		} else {
			//use regular WP i18n
			$viewing_cart = __('View your shopping cart', 'wp-menu-cart');
			$start_shopping = __('Start shopping', 'wp-menu-cart');
			/* translators: item count */
			$cart_contents = sprintf(_n('%d item', '%d items', $item_data['cart_contents_count'], 'wp-menu-cart'), $item_data['cart_contents_count']);
		}	

		$this->menu_items['menu']['cart_contents'] = $cart_contents;

		if ($item_data['cart_contents_count'] == 0) {
			$menu_item_href = apply_filters ('wpmenucart_emptyurl', $item_data['shop_page_url'] );
			$menu_item_title = apply_filters ('wpmenucart_emptytitle', $start_shopping );
			$menu_item_classes = 'wpmenucart-contents empty-wpmenucart-visible';
		} else {
			$menu_item_href = apply_filters ('wpmenucart_fullurl', $item_data['cart_url'] );
			$menu_item_title = apply_filters ('wpmenucart_fulltitle', $viewing_cart );
			$menu_item_classes = 'wpmenucart-contents';
		}

		$this->menu_items['menu']['menu_item_href'] = $menu_item_href;
		$this->menu_items['menu']['menu_item_title'] = $menu_item_title;

		if(defined('UBERMENU_VERSION') && (version_compare(UBERMENU_VERSION, '3.0.0') >= 0)){
			$menu_item_classes .= ' ubermenu-target';
		}

		$menu_item = '<a class="'.$menu_item_classes.'" href="'.$menu_item_href.'" title="'.$menu_item_title.'">';
		
		$menu_item_a_content = '';	
		if (isset($this->options['icon_display'])) {
			$icon = isset($this->options['cart_icon']) ? $this->options['cart_icon'] : '0';
			$menu_item_icon = '<i class="wpmenucart-icon-shopping-cart-'.$icon.'" role="img" aria-label="'.__( 'Cart','woocommerce' ).'"></i>';
			$menu_item_a_content .= $menu_item_icon;
		} else {
			$menu_item_icon = '';
		}
		
		switch ($this->options['items_display']) {
			case 1: //items only
				$menu_item_a_content .= '<span class="cartcontents">'.$cart_contents.'</span>';
				break;
			case 2: //price only
				$menu_item_a_content .= '<span class="amount">'.$item_data['cart_total'].'</span>';
				break;
			case 3: //items & price
				$menu_item_a_content .= '<span class="cartcontents">'.$cart_contents.'</span><span class="amount">'.$item_data['cart_total'].'</span>';
				break;
		}
		$menu_item_a_content = apply_filters ('wpmenucart_menu_item_a_content', $menu_item_a_content, $menu_item_icon, $cart_contents, $item_data );

		$this->menu_items['menu']['menu_item_a_content'] = $menu_item_a_content;

		$menu_item .= $menu_item_a_content . '</a>';
		
		$menu_item = apply_filters ('wpmenucart_menu_item_a', $menu_item,  $item_data, $this->options, $menu_item_a_content, $viewing_cart, $start_shopping, $cart_contents);

		if( !empty( $menu_item ) ) return $menu_item;		
	}
	
	public function wpmenucart_ajax() {
		check_ajax_referer( 'wpmenucart', 'security' );

		$variable = $this->wpmenucart_menu_item();
		echo $variable;
		die();
	}

}

$wpMenuCart = new WpMenuCart();

/**
 * Hide notifications
 */

if ( ! empty( $_GET['hide_wpmenucart_shop_check'] ) ) {
	update_option( 'wpmenucart_shop_check', 'hide' );
}

endif; // class_exists
