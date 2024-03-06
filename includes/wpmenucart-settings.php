<?php
class WpMenuCart_Settings {
	
	public function __construct() {
		add_action( 'admin_init', array( &$this, 'init_settings' ) ); // Registers settings
		add_action( 'admin_menu', array( &$this, 'wpmenucart_add_page' ) );
		add_action( 'wpo_wpmenucart_before_settings_content', array( &$this, 'nav_error_notice' ) );
		add_action( 'wpo_wpmenucart_settings_content', array( &$this, 'display_settings' ) );
		add_action( 'wpo_wpmenucart_after_settings_content', array( &$this, 'display_pro_ad' ) );

		add_filter( 'plugin_action_links_'.WPO_Menu_Cart()->plugin_basename, array( &$this, 'wpmenucart_add_settings_link' ) );

		//Menu admin, not using for now (very complex ajax structure...)
		//add_action( 'admin_init', array( &$this, 'wpmenucart_add_meta_box' ) );
	}
	/**
	 * User settings.
	 */
	public function init_settings() {
		$option        = 'wpmenucart';
		$option_values = get_option( $option, array() );
	
		// Section.
		add_settings_section(
			'plugin_settings',
			__( 'Plugin settings', 'wp-menu-cart' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		add_settings_field(
			'shop_plugin',
			__( 'Select which e-commerce plugin you would like Menu Cart to work with', 'wp-menu-cart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'    => $option,
				'id'      => 'shop_plugin',
				'options' => (array) $this->get_shop_plugins(),
			)
		);			

		if ( WPO_Menu_Cart()->is_block_theme() ) {
			add_settings_field(
				'block_theme_enabled',
				__( 'Current theme is block type', 'wp-menu-cart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'        => $option,
					'id'          => 'block_theme_enabled',
					'disabled'    => true,
					'pro'         => false,
					'default'     => 1,
					'description' => sprintf(
						/* translators: 1. theme name, 2. here docs link */
						__( 'Your current theme, %1$s, is a block theme, therefore, you need to configure the cart menu using the navigation block. Please follow the instructions to do it %2$s.', 'wp-menu-cart' ),
						'<strong>'.WPO_Menu_Cart()->get_current_theme_name().'</strong>',
						'<a href="https://docs.wpovernight.com/wp-menu-cart/cart-block/" target="_blank">'.__( 'here', 'wp-menu-cart' ).'</a>'
					),
				)
			);
		}

		if ( $parent_theme = wp_get_theme( get_template() ) ) {
			if ( in_array( $parent_theme->get( 'Name' ), array( 'Storefront', 'Divi' ) ) ) {
				add_settings_field(
					'hide_theme_cart',
					__( 'Hide theme shopping cart icon', 'wp-menu-cart' ),
					array( &$this, 'checkbox_element_callback' ),
					$option,
					'plugin_settings',
					array(
						'menu' => $option,
						'id'   => 'hide_theme_cart',
					)
				);
			}
		}
		
		if ( ! WPO_Menu_Cart()->is_block_theme() ) {
			add_settings_field(
				'menu_slugs',
				__( 'Select the menu(s) in which you want to display the Menu Cart', 'wp-menu-cart' ),
				array( &$this, 'menus_select_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'    => $option,
					'id'      => 'menu_slugs',
					'options' => (array) $this->get_menu_array(),
				)
			);
		}

		add_settings_field(
			'always_display',
			__( "Always display cart, even if it's empty", 'wp-menu-cart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu' => $option,
				'id'   => 'always_display',
			)
		);

		if ( function_exists( 'WC' ) ) {
			add_settings_field(
				'show_on_cart_checkout_page',
				__( 'Show on cart & checkout page', 'wp-menu-cart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'        => $option,
					'id'          => 'show_on_cart_checkout_page',
					'description' => __( 'To avoid distracting your customers with duplicate information we do not display the menu cart item on the cart & checkout pages by default', 'wp-menu-cart' ),
				)
			);
		}

		add_settings_field(
			'icon_display',
			__( 'Display shopping cart icon.', 'wp-menu-cart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu' => $option,
				'id'   => 'icon_display',
			)
		);

		add_settings_field(
			'flyout_display',
			__( 'Display cart contents in menu fly-out.', 'wp-menu-cart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'     => $option,
				'id'       => 'flyout_display',
				'disabled' => true,
				'pro'      => true,
			)
		);
		
		add_settings_field(
			'flyout_itemnumber',
			__( 'Set maximum number of products to display in fly-out', 'wp-menu-cart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'     => $option,
				'id'       => 'flyout_itemnumber',
				'options'  => array(
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
				),
				'disabled' => true,
				'pro'      => true,
			)
		);			

		add_settings_field(
			'cart_icon',
			__( 'Choose a cart icon.', 'wp-menu-cart' ),
			array( &$this, 'icons_radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'    => $option,
				'id'      => 'cart_icon',
				'options' => array(
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
			)
		);

		add_settings_field(
			'icon_color',
			__( 'Override icon color', 'wp-menu-cart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'     => $option,
				'id'       => 'icon_color',
				'disabled' => true,
				'pro'      => true,
			)
		);

		add_settings_field(
			'custom_icon',
			__( 'Custom Icon', 'wp-menu-cart' ),
			array( &$this, 'media_upload_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'                 => $option,
				'id'                   => 'custom_icon',
				'uploader_button_text' => __( 'Set image', 'wp-menu-cart' ),
				'disabled'             => true,
				'pro'                  => true,
			)
		);

		add_settings_field(
			'items_display',
			__( 'What would you like to display in the menu?', 'wp-menu-cart' ),
			array( &$this, 'radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'    => $option,
				'id'      => 'items_display',
				'options' => array(
					'1' => __( 'Items Only.' , 'wp-menu-cart' ),
					'2' => __( 'Price Only.' , 'wp-menu-cart' ),
					'3' => __( 'Both price and items.' , 'wp-menu-cart' ),
				),
			)
		);
		
		add_settings_field(
			'items_alignment',
			__( 'Select the alignment that looks best with your menu.', 'wp-menu-cart' ),
			array( &$this, 'radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'    => $option,
				'id'      => 'items_alignment',
				'options' => array(
					'left'     => __( 'Align Left.' , 'wp-menu-cart' ),
					'right'    => __( 'Align Right.' , 'wp-menu-cart' ),
					'standard' => __( 'Default Menu Alignment.' , 'wp-menu-cart' ),
				),
			)
		);

		if ( class_exists( 'WooCommerce' ) ) {
			add_settings_field(
				'total_price_type',
				__( 'Price to display', 'wp-menu-cart' ),
				array( &$this, 'select_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'    => $option,
					'id'      => 'total_price_type',
					'options' => array(
						'total'          => __( 'Cart total (including discounts)' , 'wp-menu-cart' ),
						'subtotal'       => __( 'Subtotal (total of products)' , 'wp-menu-cart' ),
						'checkout_total' => __( 'Checkout total (including discounts, fees & shipping)' , 'wp-menu-cart' ),
					),
					'default' => 'total',
				)
			);
		}

		add_settings_field(
			'custom_class',
			__( 'Enter a custom CSS class (optional)', 'wp-menu-cart' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'     => $option,
				'id'       => 'custom_class',
				'disabled' => true,
				'pro'      => true,
			)
		);
		add_settings_section(
			'floating_cart_settings',
			__( 'Floating cart', 'wp-menu-cart' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		add_settings_field(
			'floating_cart_enable',
			__( 'Enable', 'wp-menu-cart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'floating_cart_settings',
			array(
				'menu'     => $option,
				'id'       => 'floating_cart_enable',
				'options'  => array(
					'no'            => __( 'No' , 'wp-menu-cart' ),
					'always'        => __( 'Always' , 'wp-menu-cart' ),
					'small-devices' => __( 'Only on small devices' , 'wp-menu-cart' ),
				),
				'disabled' => true,
				'pro'      => true,
			)
		);

		add_settings_field(
			'floating_display_style',
			__( 'Display style', 'wp-menu-cart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'floating_cart_settings',
			array(
				'menu'     => $option,
				'id'       => 'floating_display_style',
				'options'  => array(
					'floating-circle' => __( 'Floating circle' , 'wp-menu-cart' ),
					'side-square'     => __( 'Side square' , 'wp-menu-cart' ),
				),
				'disabled' => true,
				'pro'      => true,
			)
		);

		add_settings_field(
			'floating_cart_position',
			__( 'Position', 'wp-menu-cart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'floating_cart_settings',
			array(
				'menu'     => $option,
				'id'       => 'floating_cart_position',
				'options'  => array(
					'bottom-right' => __( 'Bottom right' , 'wp-menu-cart' ),
					'bottom-left'  => __( 'Bottom left' , 'wp-menu-cart' ),
					'top-right'    => __( 'Top right' , 'wp-menu-cart' ),
					'top-left'     => __( 'Top left' , 'wp-menu-cart' ),
				),
				'disabled' => true,
				'pro'      => true,
			)
		);

		if ( function_exists( 'icl_register_string' ) ) {
			add_settings_field(
				'wpml_string_translation',
				__( "Use WPML String Translation", 'wp-menu-cart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu' => $option,
					'id'   => 'wpml_string_translation',
				)
			);
		}

		if ( apply_filters( 'wpo_wpmenucart_enable_builtin_ajax_setting', ( class_exists( 'WooCommerce' ) && isset( $option_values['builtin_ajax'] ) ) || class_exists( 'Easy_Digital_Downloads' ) ) ) {

			add_settings_field(
				'builtin_ajax',
				__( 'Use custom AJAX', 'wp-menu-cart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'        => $option,
					'id'          => 'builtin_ajax',
					'description' => __( 'Enable this option to use the custom AJAX / live update functions instead of the default ones from your shop plugin. Only use when you have issues with AJAX!', 'wp-menu-cart' ),
				)
			);
		}
		
		// Register settings.
		register_setting( $option, $option, array( &$this, 'wpmenucart_options_validate' ) );

		// Register defaults if settings empty (might not work in case there's only checkboxes and they're all disabled)
		if ( empty( $option_values ) ) {
			$this->default_settings();
		}

		// Convert old wpmenucart menu settings to array
		if ( isset( $option_values['menu_name_1'] ) ) {
			$option_values['menu_slugs'] = array( '1' => $option_values['menu_name_1'] );

			update_option( $option, $option_values );
		}
	}

	/**
	 * Add menu page
	 */
	public function wpmenucart_add_page() {
		if ( class_exists( 'WooCommerce' ) ) {
			$parent_slug = 'woocommerce';
		} else {
			$parent_slug = 'options-general.php';
		}

		$wpmenucart_page = add_submenu_page(
			$parent_slug,
			__( 'Menu Cart', 'wp-menu-cart' ),
			__( 'Menu Cart Setup', 'wp-menu-cart' ),
			'manage_options',
			'wpmenucart_options_page',
			array( $this, 'wpmenucart_options_do_page' )
		);
		add_action( 'admin_print_styles-' . $wpmenucart_page, array( &$this, 'wpmenucart_admin_styles' ) );
	}
	
	/**
	 * Add settings link to plugins page
	 */
	public function wpmenucart_add_settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=wpmenucart_options_page">'. __( 'Settings', 'woocommerce' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Styles for settings page
	 */
	public function wpmenucart_admin_styles() {
		wp_enqueue_style( 'wpmenucart-admin', WPO_Menu_Cart()->plugin_url() . '/assets/css/wpmenucart-icons' . WPO_Menu_Cart()->asset_suffix . '.css', array(), WPMENUCART_VERSION, 'all' );
		wp_enqueue_style( 'wpmenucart-font', WPO_Menu_Cart()->plugin_url() . '/assets/css/wpmenucart-font' . WPO_Menu_Cart()->asset_suffix . '.css', array(), WPMENUCART_VERSION, 'all' );
	}
	 
	/**
	 * Default settings.
	 */
	public function default_settings() {
		$option              = 'wcmenucart';
		$wcmenucart_options  = get_option( $option, array() );
		$menu_slugs          = array(
			'1' => isset( $wcmenucart_options['menu_name_1'] ) ? $wcmenucart_options['menu_name_1'] : '0',
		);

		$active_shop_plugins = WpMenuCart::get_active_shops();
		
		//switch keys & values, then strip plugin path to folder
		foreach ( $active_shop_plugins as $key => $value ) {
			$filtered_active_shop_plugins[] = dirname($value);
		}

		$first_active_shop_plugin = isset( $filtered_active_shop_plugins[0] ) ? $filtered_active_shop_plugins[0] : '';
		$default = array(
			'menu_slugs'        => $menu_slugs,
			'always_display'    => isset( $wcmenucart_options['always_display'] )  ? $wcmenucart_options['always_display']  : '',
			'icon_display'      => isset( $wcmenucart_options['icon_display'] )    ? $wcmenucart_options['icon_display']    : '1',
			'items_display'     => isset( $wcmenucart_options['items_display'] )   ? $wcmenucart_options['items_display']   : '3',
			'items_alignment'   => isset( $wcmenucart_options['items_alignment'] ) ? $wcmenucart_options['items_alignment'] : 'standard',
			'custom_class'      => '',
			'flyout_display'    => '',
			'flyout_itemnumber' => '5',
			'cart_icon'         => '0',
			'shop_plugin'       => $first_active_shop_plugin,
			'builtin_ajax'      => '',
			'hide_theme_cart'   => 1,
		);

		update_option( $option, $default );
	}

	/**
	 * Build the options page.
	 */
	public function wpmenucart_options_do_page() {
		settings_errors();	
		?>
		<div class="wrap">
			<div class="wpo_wpmenucart_settings">
				<h2><?php _e( 'WP Menu Cart', 'wp-menu-cart' ); ?></h2>
				<div class="wpo_wpmenucart_settings_container">
					<?php do_action( 'wpo_wpmenucart_before_settings_content' ); ?>
					<?php do_action( 'wpo_wpmenucart_settings_content' ); ?>
					<?php do_action( 'wpo_wpmenucart_after_settings_content' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function display_settings() {
		?>
		<div class="wpo_wpmenucart_settings_tab">
			<form method="post" action="options.php">
				<?php				
				settings_fields( 'wpmenucart' );
				do_settings_sections( 'wpmenucart' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function nav_error_notice() {
		if ( ! $this->get_menu_array() && ! WPO_Menu_Cart()->is_block_theme() ) {
			?>
			<div class="notice notice-error">
				<p><?php _e( 'You need to create a menu before you can use Menu Cart. Go to <strong>Appearence > Menus</strong> and create menu to add the cart to.', 'wp-menu-cart' ); ?></p>
			</div>
			<?php
		}
	}

	public function display_pro_ad() {
		?>
		<div class="menucart-pro-ad menucart-pro-ad-small"> 
			<?php _e( 'Want To Stand Out?', 'wp-menu-cart' ); ?> <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartgopro"><?php _e( 'Go Pro.', 'wp-menu-cart' ); ?></a>
			<ul style="font-size: 12px;list-style-type:circle;margin-left: 20px">
				<li><?php _e( 'Unlimited Menus', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Choice of 14 icons', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Packed with customization options', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Access to Shortcode', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Top Notch Support', 'wp-menu-cart' ) ?></li>
			</ul>
		</div>
		<div class="menucart-pro-ad menucart-pro-ad-big"> 
			<img src="<?php echo WPO_Menu_Cart()->plugin_url() . '/assets/images/wpo-helper.png'; ?>" class="wpo-helper">
			<h2><?php _e( 'Sell In Style With Menu Cart Pro!', 'wp-menu-cart' ) ?></h2>
			<br>
			<?php _e( 'Go Pro with Menu Cart Pro. Includes all the great standard features found in this free version plus:', 'wp-menu-cart' ) ?>
			<br>
			<ul style="list-style-type:circle;margin-left: 40px">
				<li><?php _e( 'A choice of over 10 cart icons', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'A fully featured cart details flyout', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Ability to add cart + flyout to an <strong>unlimited</strong> amount of menus', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Adjust the content & URLs via the settings', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Enter custom styles and apply custom classes via the settings', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'WPML compatible', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Automatic updates on any great new features', 'wp-menu-cart' ) ?></li>
				<li><?php _e( 'Put the cart anywhere with the [wpmenucart] shortcode', 'wp-menu-cart' ) ?></li>
			</ul>
			<?php
			/* translators: 1,2: <a> tags */
			printf ( __('Need to see more? %1$sClick here%2$s to check it out. Add a product to your cart and watch what happens!', 'wp-menu-cart' ), '<a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadmore">','</a>'); ?><br><br>
			<a class="button button-primary" style="text-align: center;margin: 0px auto" href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadbuy"><?php _e('Buy Now', 'wp-menu-cart' ) ?></a>
		</div>
		<?php
	}

	/**
	 * Get menu array.
	 * 
	 * @return array menu slug => menu name
	 */
	public function get_menu_array() {
		$menus     = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
		$menu_list = array();

		foreach ( $menus as $menu ) {
			$menu_list[$menu->slug] = $menu->name;
		}
		
		if ( ! empty( $menu_list ) ) {
			return $menu_list;
		}
	}
	
	/**
	 * Get array of active shop plugins
	 * 
	 * @return array plugin slug => plugin name
	 */
	public function get_shop_plugins() {
		$active_shop_plugins = WpMenuCart::get_active_shops();
		
		//switch keys & values, then strip plugin path to folder
		foreach ( $active_shop_plugins as $key => $value ) {
			$filtered_active_shop_plugins[ dirname($value) ] = $key;
		}

		$active_shop_plugins = isset( $filtered_active_shop_plugins ) ? $filtered_active_shop_plugins : '';
				
		return $active_shop_plugins;
	}

	/**
	 * Text field callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string	  Text field.
	 */
	public function text_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];
		$size = isset( $args['size'] ) ? $args['size'] : '25';
		$pro  = isset( $args['pro'] ) ? $args['pro'] : false;
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$disabled = ( isset( $args['disabled'] ) ) ? ' disabled' : '';
		$html     = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"%5$s/>', esc_attr( $id ), esc_attr( $menu ), esc_attr( $current ), esc_attr( $size ), esc_attr( $disabled ) );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
	
		if ( isset( $args['disabled'] ) && $pro ) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartcustomclass">Menu Cart Pro</a></i></span>';
			$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
			$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
		}
	
		echo $html;
	}
	
	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function select_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];
		$pro  = isset( $args['pro'] ) ? $args['pro'] : false;
		
		$options = get_option( $menu );
		
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
		
		$html  = sprintf( '<select name="%1$s[%2$s]" id="%1$s[%2$s]"%3$s>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $disabled ) );
		if ( 'shop_plugin' === $args['id'] ) {
			$html .= sprintf( '<option value="">%s</option>', __( 'Select a choice…', 'wp-menu-cart' ) );
		}
		
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $current , $key, false ), esc_attr( $label ) );
		}
		$html .= sprintf( '</select>' );

		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
		
		if ( isset( $args['disabled'] ) && $pro ) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
			$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
			$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
		}

		echo $html;
	}

	/**
	 * Displays a multiple selectbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function menus_select_element_callback( $args ) {
		$menu = $args['menu'];
		$id = $args['id'];

		$options = get_option( $menu );
		$menus = $options['menu_slugs'];

		for ( $x = 1; $x <= 3; $x++ ) {
			$html = '';
			if ( isset( $options[$id][$x] ) ) {
				$current = $options[$id][$x];
			} else {
				$current = isset( $args['default'] ) ? $args['default'] : '';
			}
			
			$disabled = ($x == 1) ? '' : ' disabled';
			
			$html .= sprintf( '<select name="%1$s[%2$s][%3$s]" id="%1$s[%2$s][%3$s]"%4$s>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $x ), esc_attr( $disabled ) );
			$html .= sprintf( '<option value="">%s</option>', __( 'Select a choice…', 'wp-menu-cart' ) );
			
			foreach ( (array) $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), esc_attr( $label ) );
			}
			$html .= '</select>';
	
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
			}
			if ( $x > 1 ) {
				$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartmultiplemenus">Menu Cart Pro</a></i></span>';
				$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
				$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
			}

			$html .= '<br />';
			echo $html;
		}
		
	}

	/**
	 * Checkbox field callback.
	 *
	 * @param  array $args Field arguments.
	 *
	 * @return string	  Checkbox field.
	 */
	public function checkbox_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];
		$pro  = isset( $args['pro'] ) ? $args['pro'] : false;
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
	
		$disabled = isset( $args['disabled'] ) ? ' disabled' : '';
		$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1"%3$s %4$s/>', esc_attr( $id ), esc_attr( $menu ), checked( 1, esc_attr( $current ), false ), esc_attr( $disabled ) );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
	
		if ( isset( $args['disabled'] ) && $pro ) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
			$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
			$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
		}
			
		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function radio_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];
		$pro  = isset( $args['pro'] ) ? $args['pro'] : false;
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = '';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ), checked( esc_attr( $current ), esc_attr( $key ), false ) );
			$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ), esc_attr( $label ) );
		}
		
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}

		if ( isset( $args['disabled'] ) && $pro ) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
			$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
			$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
		}
			
		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 */
	public function icons_radio_element_callback( $args ) {
		$menu = $args['menu'];
		$id   = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$icons = '';
		$radios = '';
		
		foreach ( $args['options'] as $key => $iconnumber ) {
			if ( 0 === $key ) {
				$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s][%3$s]"><i class="wpmenucart-icon-shopping-cart-%4$s"></i></label></td>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ), esc_attr( $iconnumber ) );
				$radios .= sprintf( '<td style="padding-top:0" align="center"><input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s /></td>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ), checked( esc_attr( $current ), esc_attr( $key ), false ) );
			} else {
				$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s][%3$s]"><img src="%4$scart-icon-%5$s.png" /></label></td>', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ), WPO_Menu_Cart()->plugin_url() . '/assets/images/', esc_attr( $iconnumber ) );
				$radio = sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" disabled />', esc_attr( $menu ), esc_attr( $id ), esc_attr( $key ) );
				$radio .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input-icon"></div>';
				$radio = '<div style="display:inline-block; position:relative;">'.$radio.'</div>';
				
				$radios .= '<td style="padding-top:0" align="center">'.$radio.'</td>';
			}
		}

		$profeature = '<span style="display:none;" class="pro-icon"><i>'. __( 'Additional icons are only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucarticons">Menu Cart Pro</a></i></span>';

		$html = '<table><tr>'.$icons.'</tr><tr>'.$radios.'</tr></table>'.$profeature;
		
		echo $html;
	}

	public function media_upload_callback( $args ) {
		$menu     = $args['menu'];
		$id       = $args['id'];
		$pro      = isset( $args['pro'] ) ? $args['pro'] : false;
		$btn_text = $args['uploader_button_text'];

		$disabled = isset( $args['disabled'] ) ? ' disabled' : '';
		$html     = sprintf( '<input type="button" id="%1$s" name="%2$s[%1$s]" class="btn button-primary" value="%3$s" %4$s/>', esc_attr( $id ), esc_attr( $menu ), $btn_text, esc_attr( $disabled ) );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
	
		if ( isset( $args['disabled'] ) && $pro ) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __( 'This feature only available in', 'wp-menu-cart' ) .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
			$html .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input"></div>';
			$html = '<div style="display:inline-block; position:relative;">'.$html.'</div>';
		}
			
		echo $html;
	}

	/**
	 * Section null callback.
	 *
	 * @return void.
	 */
	public function section_options_callback() {
	
	}

	/**
	 * Validate/sanitize options input
	 */
	public function wpmenucart_options_validate( $input ) {
		// Create our array for storing the validated options.
		$output = array();

		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {

			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[ $key ] ) ) {
				// Strip all HTML and PHP tags and properly handle quoted strings.
				if ( is_array( $input[ $key ] ) ) {
					foreach ( $input[ $key ] as $sub_key => $sub_value ) {
						$output[ $key ][ $sub_key ] = strip_tags( stripslashes( $input[$key][$sub_key] ) );
					}

				} else {
					$output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );
				}
			}
		}

		// Return the array processing any additional functions filtered by this action.
		return apply_filters( 'wpmenucart_validate_input', $output, $input );
	}

	public function wpmenucart_add_meta_box() {
		add_meta_box(
			'wpmenucart-meta-box',
			__('Menu Cart'),
			array( &$this, 'wpmenucart_menu_item_meta_box' ),
			'nav-menus',
			'side',
			'default'
		);
	}
	
	public function wpmenucart_menu_item_meta_box() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

		?>
		<p>
			<input value="custom" name="menu-item[<?php echo esc_attr( $_nav_menu_placeholder ); ?>][menu-item-type]" type="text" />
			<input id="custom-menu-item-url" name="menu-item[<?php echo esc_attr( $_nav_menu_placeholder ); ?>][menu-item-url]" type="text" value="" />
			<input id="custom-menu-item-name" name="menu-item[<?php echo esc_attr( $_nav_menu_placeholder ); ?>][menu-item-title]" type="text" title="<?php esc_attr_e('Menu Item', 'wp-menu-cart'); ?>" />
		</p>

		<p class="wpmenucart-meta-box" id="wpmenucart-meta-box">
			<span class="add-to-menu">
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'wp-menu-cart'); ?>" name="menucart-menu-item" id="menucart-menu-item" />
				<span class="spinner"></span>
			</span>
		</p>
		<?php
	}
}