<?php
class WpMenuCart_Settings {
	
	public function __construct() {
		add_action( 'admin_init', array( &$this, 'init_settings' ) ); // Registers settings
		add_action( 'admin_menu', array( &$this, 'wpmenucart_add_page' ) );
		add_filter( 'plugin_action_links_'.WpMenuCart::$plugin_basename, array( &$this, 'wpmenucart_add_settings_link' ) );

		//Menu admin, not using for now (very complex ajax structure...)
		//add_action( 'admin_init', array( &$this, 'wpmenucart_add_meta_box' ) );
	}
	/**
	 * User settings.
	 */
	public function init_settings() {
		$option = 'wpmenucart';
	
		// Create option in wp_options.
		if ( false == get_option( $option ) ) {
			add_option( $option );
		}
	
		// Section.
		add_settings_section(
			'plugin_settings',
			__( 'Plugin settings', 'wpmenucart' ),
			array( &$this, 'section_options_callback' ),
			$option
		);

		add_settings_field(
			'shop_plugin',
			__( 'Select which e-commerce plugin you would like Menu Cart to work with', 'wpmenucart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'		=> $option,
				'id'		=> 'shop_plugin',
				'options'	=> (array) $this->get_shop_plugins(),
			)
		);			
		
		add_settings_field(
			'menu_slugs',
			__( 'Select the menu(s) in which you want to display the Menu Cart', 'wpmenucart' ),
			array( &$this, 'menus_select_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'		=> $option,
				'id'		=> 'menu_slugs',
				'options'	=> (array) $this->get_menu_array(),
			)
		);

		add_settings_field(
			'always_display',
			__( "Always display cart, even if it's empty", 'wpmenucart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'always_display',
			)
		);

		add_settings_field(
			'icon_display',
			__( 'Display shopping cart icon.', 'wpmenucart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'icon_display',
			)
		);

		add_settings_field(
			'flyout_display',
			__( 'Display cart contents in menu fly-out.', 'wpmenucart' ),
			array( &$this, 'checkbox_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'flyout_display',
				'disabled'		=> true,
			)
		);
		
		add_settings_field(
			'flyout_itemnumber',
			__( 'Set maximum number of products to display in fly-out', 'wpmenucart' ),
			array( &$this, 'select_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'flyout_itemnumber',
				'options'		=> array(
						'0'			=> '0',
						'1'			=> '1',
						'2'			=> '2',
						'3'			=> '3',
						'4'			=> '4',
						'5'			=> '5',
						'6'			=> '6',
						'7'			=> '7',
						'8'			=> '8',
						'9'			=> '9',
						'10'		=> '10',
				),
				'disabled'		=> true,
			)
		);			

		add_settings_field(
			'cart_icon',
			__( 'Choose a cart icon.', 'wpmenucart' ),
			array( &$this, 'icons_radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'cart_icon',
				'options' 		=> array(
					'0'			=> '0',
					'1'			=> '1',
					'2'			=> '2',
					'3'			=> '3',
					'4'			=> '4',
					'5'			=> '5',
					'6'			=> '6',
					'7'			=> '7',
					'8'			=> '8',
					'9'			=> '9',
					'10'		=> '10',
					'11'		=> '11',
					'12'		=> '12',
					'13'		=> '13',
				),
			)
		);


		add_settings_field(
			'items_display',
			__( 'What would you like to display in the menu?', 'wpmenucart' ),
			array( &$this, 'radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'items_display',
				'options' 		=> array(
					'1'			=> __( 'Items Only.' , 'wpmenucart' ),
					'2'			=> __( 'Price Only.' , 'wpmenucart' ),
					'3'			=> __( 'Both price and items.' , 'wpmenucart' ),
				),
			)
		);
		
		add_settings_field(
			'items_alignment',
			__( 'Select the alignment that looks best with your menu.', 'wpmenucart' ),
			array( &$this, 'radio_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'items_alignment',
				'options' 		=> array(
					'left'			=> __( 'Align Left.' , 'wpmenucart' ),
					'right'			=> __( 'Align Right.' , 'wpmenucart' ),
					'standard'		=> __( 'Default Menu Alignment.' , 'wpmenucart' ),
				),
			)
		);

		add_settings_field(
			'custom_class',
			__( 'Enter a custom CSS class (optional)', 'wpmenucart' ),
			array( &$this, 'text_element_callback' ),
			$option,
			'plugin_settings',
			array(
				'menu'			=> $option,
				'id'			=> 'custom_class',
				'disabled'		=> true,
			)
		);
		
		if ( function_exists( 'icl_register_string' ) ) {
			add_settings_field(
				'wpml_string_translation',
				__( "Use WPML String Translation", 'wpmenucart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'wpml_string_translation',
				)
			);
		}

		if ( class_exists( 'WooCommerce' ) || defined('JIGOSHOP_VERSION') ) {
			add_settings_field(
				'builtin_ajax',
				__( 'Use Built-in AJAX', 'wpmenucart' ),
				array( &$this, 'checkbox_element_callback' ),
				$option,
				'plugin_settings',
				array(
					'menu'			=> $option,
					'id'			=> 'builtin_ajax',
					'description'	=> __( 'Enable this option to use the built-in AJAX / live update functions instead of the default ones from WooCommerce or Jigoshop', 'wpmenucart' ),
				)
			);
		}
		
		// Register settings.
		register_setting( $option, $option, array( &$this, 'wpmenucart_options_validate' ) );

		// Register defaults if settings empty (might not work in case there's only checkboxes and they're all disabled)
		$option_values = get_option($option);
		if ( empty( $option_values ) )
			$this->default_settings();

		// Convert old wpmenucart menu settings to array
		if ( isset($option_values['menu_name_1']) ) {
			$option_values['menu_slugs'] = array( '1' =>  $option_values['menu_name_1'] );
			update_option( 'wpmenucart', $option_values );
		}
	}

	/**
	 * Add menu page
	 */
	public function wpmenucart_add_page() {
		if (class_exists('WooCommerce')) {
			$parent_slug = 'woocommerce';
		} else {
			$parent_slug = 'options-general.php';
		}

		$wpmenucart_page = add_submenu_page(
			$parent_slug,
			__( 'Menu Cart', 'wpmenucart' ),
			__( 'Menu Cart Setup', 'wpmenucart' ),
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
		wp_register_style( 'wpmenucart-admin', plugins_url( 'css/wpmenucart-icons.css', dirname(__FILE__) ), array(), '', 'all' );
		wp_enqueue_style( 'wpmenucart-admin' );
	}
	 
	/**
	 * Default settings.
	 */
	public function default_settings() {
		$wcmenucart_options = get_option('wcmenucart');
		$menu_slugs = array( '1' =>  isset($wcmenucart_options['menu_name_1']) ? $wcmenucart_options['menu_name_1']:'0' );

		$active_shop_plugins = WpMenuCart::get_active_shops();
		
		//switch keys & values, then strip plugin path to folder
		foreach ($active_shop_plugins as $key => $value) {
			$filtered_active_shop_plugins[] = dirname($value);
		}

		$first_active_shop_plugin = isset($filtered_active_shop_plugins[0])?$filtered_active_shop_plugins[0]:'';
		$default = array(
			'menu_slugs'		=> $menu_slugs,
			'always_display'	=> isset($wcmenucart_options['always_display']) ? $wcmenucart_options['always_display']:'',
			'icon_display'		=> isset($wcmenucart_options['icon_display']) ? $wcmenucart_options['icon_display']:'1',
			'items_display'		=> isset($wcmenucart_options['items_display']) ? $wcmenucart_options['items_display']:'3',
			'items_alignment'	=> isset($wcmenucart_options['items_alignment']) ? $wcmenucart_options['items_alignment']:'standard',
			'custom_class'		=> '',
			'flyout_display'	=> '',
			'flyout_itemnumber'	=> '5',
			'cart_icon'			=> '0',
			'shop_plugin'		=> $first_active_shop_plugin,
			'builtin_ajax'		=> ''
		);

		update_option( 'wpmenucart', $default );
	}

	/**
	 * Build the options page.
	 */
	public function wpmenucart_options_do_page() {		
		?>
	
		<div class="wrap">
			<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php _e('WP Menu Cart','wpmenucart') ?></h2>
				<?php 
				// print_r(get_option('wpmenucart')); //for debugging
				//print_r($this->get_shop_plugins());
				//print_r(apply_filters( 'active_plugins', get_option( 'active_plugins' )));
				if (!$this->get_menu_array()) {
				?>
				<div class="error" style="width:400px; padding:10px;">
					You need to create a menu before you can use Menu Cart. Go to <strong>Appearence > Menus</strong> and create menu to add the cart to.
				</div>
				<?php } ?>
				<form method="post" action="options.php">
				<?php
									
					settings_fields( 'wpmenucart' );
					do_settings_sections( 'wpmenucart' );

					submit_button();
				?>

			</form>
			<script type="text/javascript">
			jQuery('.hidden-input').click(function() {
				jQuery(this).closest('.hidden-input').prev('.pro-feature').show('slow');
				jQuery(this).closest('.hidden-input').hide();
			});
			jQuery('.hidden-input-icon').click(function() {
				jQuery('.pro-icon').show('slow');
			});
			</script>
			<style type="text/css">
			.menucart-pro-ad {
				border: 1px solid #3D5C99;
				background-color: #EBF5FF;	
				border-radius: 5px;
				padding: 15px;
			}
			.menucart-pro-ad-big {
				margin-top: 15px;
				min-height: 90px;
				position: relative;
				padding-left: 100px;
			}
			.menucart-pro-ad-small {
				position: absolute;
				right: 20px;
				top: 20px;
			}
			img.wpo-helper {
				position: absolute;
				top: -20px;
				left: 3px;
			}
			</style>
			<div class="menucart-pro-ad menucart-pro-ad-small"> 
				Want To Stand Out? <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartgopro">Go Pro.</a>
				<ul style="font-size: 12px;list-style-type:circle;margin-left: 20px">
					<li><?php _e('Unlimited Menus','wpmenucart') ?></li>
					<li><?php _e('Choice of 14 icons','wpmenucart') ?></li>
					<li><?php _e('Packed with customization options','wpmenucart') ?></li>
					<li><?php _e('Access to Shortcode','wpmenucart') ?></li>
					<li><?php _e('Top Notch Support','wpmenucart') ?></li>
				</ul>
			</div>
			<div class="menucart-pro-ad menucart-pro-ad-big"> 
				<img src="<?php echo plugins_url( 'images/', dirname(__FILE__) ) . 'wpo-helper.png'; ?>" class="wpo-helper">
				<h2><?php _e('Sell In Style With Menu Cart Pro!','wpmenucart') ?></h2>
				<br>
				<?php _e('Go Pro with Menu Cart Pro. Includes all the great standard features found in this free version plus:','wpmenucart') ?>
				<br>
				<ul style="list-style-type:circle;margin-left: 40px">
					<li><?php _e('A choice of over 10 cart icons','wpmenucart') ?></li>
					<li><?php _e('A fully featured cart details flyout','wpmenucart') ?></li>
					<li><?php _e('Ability to add cart + flyout to an <strong>unlimited</strong> amount of menus','wpmenucart') ?></li>
					<li><?php _e('Adjust the content & URLs via the settings','wpmenucart') ?></li>
					<li><?php _e('Enter custom styles and apply custom classes via the settings','wpmenucart') ?></li>
					<li><?php _e('WPML compatible','wpmenucart') ?></li>
					<li><?php _e('Automatic updates on any great new features','wpmenucart') ?></li>
					<li><?php _e('Put the cart anywhere with the [wpmenucart] shortcode','wpmenucart') ?></li>
				</ul>
				<?php
				$menucartadmore = '<a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadmore">';
				printf (__('Need to see more? %sClick here%s to check it out. Add a product to your cart and watch what happens!','wpmenucart'), $menucartadmore,'</a>'); ?><br><br>
				<a class="button button-primary" style="text-align: center;margin: 0px auto" href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartadbuy"><?php _e('Buy Now','wpmenucart') ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Get menu array.
	 * 
	 * @return array menu slug => menu name
	 */
	public function get_menu_array() {
		$menus = get_terms( 'nav_menu', array( 'hide_empty' => false ) );
		$menu_list = array();

		foreach ( $menus as $menu ) {
			$menu_list[$menu->slug] = $menu->name;
		}
		
		if (!empty($menu_list)) return $menu_list;
	}
	
	/**
	 * Get array of active shop plugins
	 * 
	 * @return array plugin slug => plugin name
	 */
	public function get_shop_plugins() {
		$active_shop_plugins = WpMenuCart::get_active_shops();
		
		//switch keys & values, then strip plugin path to folder
		foreach ($active_shop_plugins as $key => $value) {
			$filtered_active_shop_plugins[dirname($value)] = $key;
		}

		$active_shop_plugins = isset($filtered_active_shop_plugins) ? $filtered_active_shop_plugins:'';
				
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
		$id = $args['id'];
		$size = isset( $args['size'] ) ? $args['size'] : '25';
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
		$html = sprintf( '<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" size="%4$s"%5$s/>', $id, $menu, $current, $size, $disabled );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		if (isset( $args['disabled'] )) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __('This feature only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartcustomclass">Menu Cart Pro</a></i></span>';
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
		$id = $args['id'];
		
		$options = get_option( $menu );
		
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
		
		$html = sprintf( '<select name="%1$s[%2$s]" id="%1$s[%2$s]"%3$s>', $menu, $id, $disabled );
		$html .= sprintf( '<option value="%s"%s>%s</option>', '0', selected( $current, '0', false ), '' );
		
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
		}
		$html .= sprintf( '</select>' );

		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
		
		if (isset( $args['disabled'] )) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __('This feature only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
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
			
			$html .= sprintf( '<select name="%1$s[%2$s][%3$s]" id="%1$s[%2$s][%3$s]"%4$s>', $menu, $id, $x, $disabled);
			$html .= sprintf( '<option value="%s"%s>%s</option>', '0', selected( $current, '0', false ), '' );
			
			foreach ( (array) $args['options'] as $key => $label ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $current, $key, false ), $label );
			}
			$html .= '</select>';
	
			if ( isset( $args['description'] ) ) {
				$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
			}
			if ( $x > 1 ) {
				$html .= ' <span style="display:none;" class="pro-feature"><i>'. __('This feature only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartmultiplemenus">Menu Cart Pro</a></i></span>';
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
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}
	
		$disabled = (isset( $args['disabled'] )) ? ' disabled' : '';
		$html = sprintf( '<input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1"%3$s %4$s/>', $id, $menu, checked( 1, $current, false ), $disabled );
	
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}
	
		if (isset( $args['disabled'] )) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __('This feature only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
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
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$html = '';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $menu, $id, $key, checked( $current, $key, false ) );
			$html .= sprintf( '<label for="%1$s[%2$s][%3$s]"> %4$s</label><br>', $menu, $id, $key, $label);
		}
		
		// Displays option description.
		if ( isset( $args['description'] ) ) {
			$html .= sprintf( '<p class="description">%s</p>', $args['description'] );
		}

		if (isset( $args['disabled'] )) {
			$html .= ' <span style="display:none;" class="pro-feature"><i>'. __('This feature only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucartflyout">Menu Cart Pro</a></i></span>';
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
		$id = $args['id'];
	
		$options = get_option( $menu );
	
		if ( isset( $options[$id] ) ) {
			$current = $options[$id];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '';
		}

		$icons = '';
		$radios = '';
		
		foreach ( $args['options'] as $key => $iconnumber ) {
			if ($key == 0) {
				$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s][%3$s]"><i class="wpmenucart-icon-shopping-cart-%4$s"></i></label></td>', $menu, $id, $key, $iconnumber);
				$radios .= sprintf( '<td style="padding-top:0" align="center"><input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s /></td>', $menu, $id, $key, checked( $current, $key, false ) );
			} else {
				$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s][%3$s]"><img src="%4$scart-icon-%5$s.png" /></label></td>', $menu, $id, $key, plugins_url( 'images/', dirname(__FILE__) ), $iconnumber);
				$radio = sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" disabled />', $menu, $id, $key);
				$radio .= '<div style="position:absolute; left:0; right:0; top:0; bottom:0; background-color:white; -moz-opacity: 0; opacity:0;filter: alpha(opacity=0);" class="hidden-input-icon"></div>';
				$radio = '<div style="display:inline-block; position:relative;">'.$radio.'</div>';
				
				$radios .= '<td style="padding-top:0" align="center">'.$radio.'</td>';
			}
		}

		$profeature = '<span style="display:none;" class="pro-icon"><i>'. __('Additional icons are only available in', 'wpmenucart') .' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucarticons">Menu Cart Pro</a></i></span>';

		$html = '<table><tr>'.$icons.'</tr><tr>'.$radios.'</tr></table>'.$profeature;
		
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
			if ( isset( $input[$key] ) ) {
				// Strip all HTML and PHP tags and properly handle quoted strings.
				if ( is_array( $input[$key] ) ) {
					foreach ( $input[$key] as $sub_key => $sub_value ) {
						$output[$key][$sub_key] = strip_tags( stripslashes( $input[$key][$sub_key] ) );
					}

				} else {
					$output[$key] = strip_tags( stripslashes( $input[$key] ) );
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
			<input value="custom" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" type="text" />
			<input id="custom-menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" type="text" value="" />
			<input id="custom-menu-item-name" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" type="text" title="<?php esc_attr_e('Menu Item'); ?>" />
		</p>

		<p class="wpmenucart-meta-box" id="wpmenucart-meta-box">
			<span class="add-to-menu">
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu'); ?>" name="menucart-menu-item" id="menucart-menu-item" />
				<span class="spinner"></span>
			</span>
		</p>
		<?php
	}


}