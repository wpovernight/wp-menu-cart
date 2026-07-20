<?php
/**
 * Plugin Name:          WP Menu Cart
 * Plugin URI:           https://wpovernight.com/downloads/menu-cart-pro/
 * Description:          Extension for your e-commerce plugin (WooCommerce or Easy Digital Downloads) that places a cart icon with number of items and total cost in the menu bar. Activate the plugin, set your options and you're ready to go! Will automatically conform to your theme styles.
 * Version:              3.1.0
 * Author:               WP Overnight
 * Author URI:           https://wpovernight.com/
 * License:              GPLv2 or later
 * License URI:          https://opensource.org/licenses/gpl-license.php
 * Text Domain:          wp-menu-cart
 * WC requires at least: 4.0
 * WC tested up to:      10.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart' ) ) :

class WpMenuCart {

	/**
	 * @var string
	 */
	protected $plugin_version = '3.1.0';

	/**
	 * @var string
	 */
	public $version_php = '7.4';

	/**
	 * @var string
	 */
	public $version_woo = '4.0';

	/**
	 * @var string
	 */
	public $version_edd = '2.8.7';

	/**
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * @var string
	 */
	public $plugin_basename;

	/**
	 * @var array
	 */
	public $main_settings;

	/**
	 * @var WpMenuCart_Settings
	 */
	public $settings;

	/**
	 * @var WPMenuCart_WooCommerce|WPMenuCart_EDD
	 */
	public $shop;

	/**
	 * @var WpMenuCart_Nav_Menu
	 */
	public $nav_menu;

	/**
	 * @var WpMenuCart_Assets
	 */
	public $assets;

	/**
	 * @var WpMenuCart_Main
	 */
	public $main;

	/**
	 * @var WpMenuCart_Conflict_Detector
	 */
	public $conflict_detector;

	/**
	 * @var WpMenuCart
	 */
	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->plugin_slug     = basename( dirname( __FILE__ ) );
		$this->plugin_basename = plugin_basename( __FILE__ );
		$this->main_settings   = get_option( 'wpo_wpmenucart_main_settings', array() );

		$this->define( 'WPMENUCART_VERSION', $this->plugin_version );

		// Run option key migration before anything else loads.
		add_action( 'init', array( $this, 'maybe_migrate_options' ), 1 );

		// load the localisation & classes
		add_action( 'init', array( $this, 'wpml' ), 0 );
		add_action( 'init', array( $this, 'translations' ), 8 );
		add_action( 'init', array( $this, 'load_classes' ), 9 );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}

		add_filter( 'load_textdomain_mofile', array( $this, 'textdomain_fallback' ), 10, 2 );

		// HPOS compatibility
		add_action( 'before_woocommerce_init', array( $this, 'woocommerce_hpos_compatible' ) );
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
	 * Migrate settings from the legacy option key 'wpmenucart' to 'wpo_wpmenucart_main_settings'.
	 * 
	 * Runs once on init priority 1 so all subsequent hooks read the new key. A version flag
	 * prevents the migration from running more than once.
	 * 
	 * @return void
	 */
	public function maybe_migrate_options(): void {
		if ( ! get_option( 'wpo_wpmenucart_options_migrated' ) ) {
			$legacy = get_option( 'wpmenucart' );

			if ( ! empty( $legacy ) && false === get_option( 'wpo_wpmenucart_main_settings' ) ) {
				// Translate shop_plugin slug from old format to new format.
				$shop_plugin_map = array(
					'woocommerce'                => 'WooCommerce',
					'easy-digital-downloads'     => 'Easy Digital Downloads',
					'easy-digital-downloads-pro' => 'Easy Digital Downloads Pro',
				);

				// Fields that belong elsewhere and should not be copied into main settings.
				$dont_copy = array( 'custom_class', 'wpml_string_translation' );

				$main_settings = array();

				foreach ( $legacy as $key => $value ) {
					if ( in_array( $key, $dont_copy, true ) ) {
						continue;
					}

					if ( 'shop_plugin' === $key && ! empty( $shop_plugin_map[ $value ] ) ) {
						$value = $shop_plugin_map[ $value ];
					}

					$main_settings[ $key ] = $value;
				}

				update_option( 'wpo_wpmenucart_main_settings', $main_settings );
			}

			update_option( 'wpo_wpmenucart_options_migrated', true );

			// Refresh the in-memory copy so the rest of this request sees the migrated value.
			$this->main_settings = get_option( 'wpo_wpmenucart_main_settings', array() );
		}
	}

	/**
	 * Instantiate classes when dependencies are satisfied.
	 *
	 * @return void|null
	 */
	public function load_classes() {
		if ( ! $this->good_to_go() ) {
			return;
		}

		$this->includes();
	}

	/**
	 * Load the main plugin classes and functions
	 * 
	 * @return void
	 */
	public function includes(): void {
		include_once( 'includes/class-wpmenucart-nav-menu.php' );
		$this->nav_menu = new WpMenuCart_Nav_Menu();
		$this->nav_menu->maybe_migrate_menu_slugs();

		include_once( 'includes/class-wpmenucart-assets.php' );
		$this->assets = new WpMenuCart_Assets();

		if ( isset( $this->main_settings['shop_plugin'] ) && $this->is_shop_active( array(), $this->main_settings['shop_plugin'] ) ) {
			switch ( $this->main_settings['shop_plugin'] ) {
				case 'WooCommerce':
					include_once( 'includes/shops/wpmenucart-woocommerce.php' );
					$this->shop = new WPMenuCart_WooCommerce();
					break;
				case 'Easy Digital Downloads':
				case 'Easy Digital Downloads Pro':
					include_once( 'includes/shops/wpmenucart-edd.php' );
					$this->shop = new WPMenuCart_EDD();
					break;
			}
		}

		include_once( 'includes/class-wpmenucart-main.php' );
		$this->main = new WpMenuCart_Main();

		if ( ! isset( $this->main_settings['builtin_ajax'] ) ) {
			switch ( $this->get_active_shop() ) {
				case 'WC':
					add_filter( 'woocommerce_add_to_cart_fragments', array( $this->main, 'woocommerce_ajax_fragments' ) );
					break;
				case 'EDD':
					add_action( 'wp_enqueue_scripts', array( $this->assets, 'load_edd_ajax' ), 0 );
					break;
			}
		}

		include_once( 'includes/class-wpmenucart-conflict-detector.php' );
		$this->conflict_detector = new WpMenuCart_Conflict_Detector();

		include_once( 'includes/class-wpmenucart-settings.php' );
		$this->settings = new WpMenuCart_Settings();

		include_once( 'includes/class-wpmenucart-template.php' );
		include_once( 'includes/class-wpmenucart-data.php' );
	}

	/**
	 * Check environment requirements.
	 *
	 * @return bool
	 */
	public function good_to_go() {
		$wpmenucart_shop_check = get_option( 'wpmenucart_shop_check' );
		$active_plugins        = $this->get_active_plugins();

		if ( version_compare( PHP_VERSION, $this->version_php, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'required_php_version' ) );
			return false;
		}

		// check for shop plugins
		if ( ! $this->is_shop_active( $active_plugins ) && 'hide' !== $wpmenucart_shop_check ) {
			add_action( 'admin_notices', array( $this, 'need_shop' ) );
			return false;
		} elseif ( $this->is_shop_active() && ! $this->valid_shop_versions( $active_plugins ) ) {
			return false;
		}

		// check for old versions
		if ( count( $this->get_active_old_versions( $active_plugins ) ) > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Return true if one or more shops are activated.
	 * 
	 * @param array  $active_plugins
	 * @param string $shop
	 *
	 * @return bool
	 */
	public function is_shop_active( array $active_plugins = array(), string $shop = '' ): bool {
		if ( empty( $shop ) ) {
			if ( count( $this->get_active_shops( $active_plugins ) ) > 0 ) {
				return true;
			} else {
				return false;
			}
		} else {
			switch ( $shop ) {
				case 'WooCommerce':
					return function_exists( 'WC' );
				case 'Easy Digital Downloads':
				case 'Easy Digital Downloads Pro':
					return function_exists( 'EDD' );
				default:
					return false;
				break;
			}
		}
	}

	/**
	 * Get active shop plugin
	 * 
	 * @return string
	 */
	public function get_active_shop(): string {
		$shop = '';

		if ( $this->shop instanceof WPMenuCart_WooCommerce ) {
			$shop = 'WC';
		} elseif ( $this->shop instanceof WPMenuCart_EDD ) {
			$shop = 'EDD';
		}

		return $shop;
	}

	/**
	 * Handles version checking.
	 *
	 * @return void
	 */
	public function do_install(): void {
		$version_setting   = 'wpo_wpmenucart_free_version';
		$installed_version = get_option( $version_setting );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, $this->plugin_version, '<' ) ) {
			if ( $installed_version ) {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $version_setting, $this->plugin_version );
		}
	}

	/**
	 * Plugin upgrade method.
	 *
	 * @param string $installed_version the currently installed ('old') version
	 * @return void
	 */
	protected function upgrade( string $installed_version ): void {
		// Reserved for future version-gated migrations.
	}

	/**
	 * Checks if active shops have the required version.
	 *
	 * @param array $active_plugins
	 *
	 * @return bool
	 */
	public function valid_shop_versions( array $active_plugins ): bool {
		$active_shops = $this->get_active_shops( $active_plugins );

		if ( isset( $active_shops['WooCommerce'] ) && defined( 'WC_VERSION' ) && ! version_compare( WC_VERSION, $this->version_woo, '>=' ) ) {
			add_action( 'admin_notices', function() {
				$this->required_shop_version_notice( 'WooCommerce', $this->version_woo );
			} );

			return false;
		}

		if (
			( isset( $active_shops['Easy Digital Downloads'] ) || isset( $active_shops['Easy Digital Downloads Pro'] ) )
			&& defined( 'EDD_VERSION' )
			&& ! version_compare( EDD_VERSION, $this->version_edd, '>=' )
		) {
			add_action( 'admin_notices', function() {
				$this->required_shop_version_notice( 'Easy Digital Downloads', $this->version_edd );
			} );

			return false;
		}

		return true;
	}

	/**
	 * Admin notice for required shop plugin version.
	 *
	 * @param string $shop
	 * @param string $version
	 * @return void
	 */
	public function required_shop_version_notice( string $shop, string $version ): void {
		$error_message = sprintf(
			/* translators: 1. Shop name, 2. Shop version */
			esc_html__( 'WP Menu Cart requires %1$s version %2$s or higher to be installed & activated!', 'wp-menu-cart' ),
			esc_attr( $shop ),
			esc_attr( $version )
		);
		printf( '<div class="notice notice-error"><p>%s</p></div>', $error_message );
	}

	/**
	 * Get an array of all active plugins, including multisite.
	 *
	 * @return array active plugin paths
	 */
	public function get_active_plugins(): array {
		$active_plugins = (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		if ( is_multisite() ) {
			$active_sitewide_plugins = (array) array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins          = (array) array_unique( array_merge( $active_plugins, $active_sitewide_plugins ) );
		}

		return $active_plugins;
	}

	/**
	 * Get array of active shop plugins.
	 *
	 * @return array plugin name => plugin path
	 */
	public function get_active_shops( $active_plugins = array() ) {
		if ( empty( $active_plugins ) ) {
			$active_plugins = $this->get_active_plugins();
		}

		$shop_plugins = array(
			'WooCommerce'                => 'woocommerce/woocommerce.php',
			'Easy Digital Downloads'     => 'easy-digital-downloads/easy-digital-downloads.php',
			'Easy Digital Downloads Pro' => 'easy-digital-downloads-pro/easy-digital-downloads.php',
		);

		// filter shop plugins & add shop names as keys
		$active_shop_plugins = array_intersect( $shop_plugins, $active_plugins );

		return $active_shop_plugins;
	}

	/**
	 * Get array of active old WooCommerce Menu Cart plugins.
	 *
	 * @return array plugin paths
	 */
	public function get_active_old_versions( $active_plugins = array() ) {
		if ( empty( $active_plugins ) ) {
			$active_plugins = $this->get_active_plugins();
		}

		$old_versions = array(
			'woocommerce-menu-bar-cart/wc_cart_nav.php',               //first version
			'woocommerce-menu-bar-cart/woocommerce-menu-cart.php',     //last free version
			'woocommerce-menu-cart/woocommerce-menu-cart.php',         //never actually released? just in case...
			'woocommerce-menu-cart-pro/woocommerce-menu-cart-pro.php', //old pro version
		);

		$active_old_plugins = array_intersect( $old_versions, $active_plugins );

		return $active_old_plugins;
	}

	/**
	 * Fallback admin notices
	 *
	 * @return void
	 */
	public function need_shop(): void {
		$error = __( 'WP Menu Cart could not detect an active shop plugin. Make sure you have activated at least one of the supported plugins.', 'wp-menu-cart' );
		printf(
			'<div class="notice notice-error"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html( $error ),
			esc_url( wp_nonce_url( add_query_arg( 'hide_wpmenucart_shop_check', 'true' ), 'need_shop_notice_nonce' ) ),
			esc_html__( 'Hide this notice', 'wp-menu-cart' )
		);

		// Hide notice.
		if ( isset( $_GET['hide_wpmenucart_shop_check'] ) && isset( $_REQUEST['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'need_shop_notice_nonce' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'wp-menu-cart' ) );
			}

			update_option( 'wpmenucart_shop_check', 'hide' );
			wp_safe_redirect( remove_query_arg( array( 'hide_wpmenucart_shop_check', '_wpnonce' ) ) );
			exit;
		}
	}

	/**
	 * Old conflicting plugin notice.
	 *
	 * @return void
	 */
	public function woocommerce_version_active() {
		$error = __( 'An old version of WooCommerce Menu Cart is currently activated, you need to disable or uninstall it for WP Menu Cart to function properly.', 'wp-menu-cart' );
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $error ) );
	}

	/**
	 * PHP version requirement notice
	 * 
	 * @return void
	 */
	public function required_php_version(): void {
		$error = sprintf(
			/* translators: 1. PHP version */
			__( 'WP Menu Cart requires PHP %s or higher.', 'wp-menu-cart' ),
			$this->version_php
		);
		$how_to_update = __( 'How to update your PHP version', 'wp-menu-cart' );
		printf(
			'<div class="notice notice-error"><p>%s</p><p><a href="%s" target="_blank" rel="noopener">%s</a></p></div>',
			esc_html( $error ),
			esc_url( 'http://docs.wpovernight.com/general/how-to-update-your-php-version/' ),
			esc_html( $how_to_update )
		);
	}

	/**
	 * Declares WooCommerce HPOS compatibility.
	 *
	 * @return void
	 */
	public function woocommerce_hpos_compatible() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Load the translation / textdomain files
	 *
	 * @return void
	 */
	public function translations(): void {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'wp-menu-cart' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * - WP_LANG_DIR/wp-menu-cart/wp-menu-cart-LOCALE.mo
		 * - wp-menu-cart/languages/wp-menu-cart-LOCALE.mo (which if not found falls back to:)
		 * - WP_LANG_DIR/plugins/wp-menu-cart-LOCALE.mo
		 */
		unload_textdomain( 'wp-menu-cart');
		load_textdomain( 'wp-menu-cart', $dir . '/wp-menu-cart/wp-menu-cart-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp-menu-cart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Maintain textdomain compatibility between main plugin (wp-menu-cart) and WooCommerce version (woocommerce-menu-bar-cart)
	 * so that wordpress.org language packs can be used for both
	 */
	public function textdomain_fallback( $mofile, $textdomain ) {
		$main_domain = 'wp-menu-cart';
		$wc_domain   = 'woocommerce-menu-bar-cart';

		// check if this is filtering the mofile for this plugin
		if ( $textdomain === $main_domain ) {
			$wc_mofile = str_replace( "{$textdomain}-", "{$wc_domain}-", $mofile ); // with trailing dash to target file and not folder
			if ( file_exists( $wc_mofile ) ) {
				if ( ! is_callable( 'copy' ) ) {
					$copy = false;
				} elseif ( ! file_exists( $mofile ) ) {
					$copy = true;
				} else { // can copy but file already exists
					$wc_file_date   = filemtime( $wc_mofile );
					$main_file_date = filemtime( $mofile );
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
					$wc_pofile = substr_replace( $wc_mofile, ".po", -3 );
					if ( file_exists( $wc_pofile ) ) {
						copy( $wc_pofile, substr_replace( $mofile, ".po", -3 ) );
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
		if ( isset( $this->main_settings['wpml_string_translation'] ) && function_exists( 'icl_register_string' ) ) {
			icl_register_string('WP Menu Cart', 'item text', 'item');
			icl_register_string('WP Menu Cart', 'items text', 'items');
			icl_register_string('WP Menu Cart', 'empty cart text', 'your cart is currently empty');
			icl_register_string('WP Menu Cart', 'hover text', 'View your shopping cart');
			icl_register_string('WP Menu Cart', 'empty hover text', 'Start shopping');
		}
	}

	/**
	 * Check if current request is a rest request
	 *
	 * @return bool
	 */
	public function is_rest_request(): bool {
		return defined( 'REST_REQUEST' ) && REST_REQUEST;
	}

	/**
	 * Check if rest request is block editor.
	 *
	 * @return bool
	 */
	public function is_block_editor(): bool {
		if ( $this->is_rest_request() ) {
			$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
			if ( false !== strpos( $route, 'wpo/wpmenucart-navigation' ) || false !== strpos( $route, '/navigation' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if current theme is block theme
	 *
	 * @return bool
	 */
	public function is_block_theme(): bool {
		$theme = wp_get_theme();
		if ( ! empty( $theme ) && is_callable( array( $theme, 'is_block_theme' ) ) ) {
			return $theme->is_block_theme();
		}
		return false;
	}

	/**
	 * Get current theme name
	 *
	 * @return string|false
	 */
	public function get_current_theme_name() {
		$theme = wp_get_theme();
		if ( ! empty( $theme ) && is_callable( array( $theme, 'display' ) ) ) {
			return $theme->display( 'Name' );
		}
		return false;
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Output a dismissible admin notice with a server-side dismiss link.
	 *
	 * @param  string $id       Unique HTML ID for the notice element.
	 * @param  string $message  Already-escaped HTML message string.
	 * @param  string $meta_key User meta key used to persist the dismissal.
	 * @param  bool   $inline   Whether to add the inline class (for in-page notices).
	 *
	 * @return void
	 */
	public function render_dismissible_notice( string $id, string $message, string $meta_key, bool $inline = false ): void {
		if ( get_user_meta( get_current_user_id(), $meta_key, true ) ) {
			return;
		}

		$nonce_action = 'wpo_wpmenucart_dismiss_notice_' . $meta_key;

		if ( isset( $_GET['wpo_wpmenucart_dismiss_notice'] ) && $meta_key === $_GET['wpo_wpmenucart_dismiss_notice'] ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), $nonce_action ) ) {
				update_user_meta( get_current_user_id(), $meta_key, true );
				return;
			}
		}

		$dismiss_url = wp_nonce_url(
			add_query_arg( 'wpo_wpmenucart_dismiss_notice', $meta_key ),
			$nonce_action
		);

		$classes = 'notice notice-info';
		if ( $inline ) {
			$classes .= ' inline';
		}
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<p>
				<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput -- caller is responsible for escaping ?>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-left:1em;"><?php esc_html_e( 'Dismiss', 'wp-menu-cart' ); ?></a>
			</p>
		</div>
		<?php
	}

} // end class

endif; // class_exists

/**
 * Returns the main instance of WP Menu Cart to prevent the need to use globals.
 *
 * @return WpMenuCart
 */
function WPO_Menu_Cart() {
	return WpMenuCart::instance();
}

WPO_Menu_Cart(); // load plugin
