<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WpMenuCart_Template' ) ) :

	/**
	 * Loads a template file, splits it into replaceable parts, and renders it
	 * with data supplied by a data class (see WpMenuCart_Data).
	 */
	class WpMenuCart_Template {

		public $html;
		public $template;
		public $template_parts;
		public $nav_menu_items;
		public $menu_slug;
		public $dont_render;
		public $menu_args;
		public $template_slug;

		/**
		 * @param string $template_slug
		 * @param string $nav_menu_items
		 * @param array  $args
		 */
		public function __construct( string $template_slug = '', string $nav_menu_items = '', array $args = array() ) {
			$defaults = array(
				'menu_slug' => '',
				'part'      => '',
				'menu_args' => array(),
			);
			$args = wp_parse_args( $args, $defaults );

			$this->menu_args      = $args['menu_args'];
			$this->template_slug  = $template_slug;
			$this->nav_menu_items = $nav_menu_items;
			$this->menu_slug      = $args['menu_slug'];

			if ( isset( $args['wc_fragments'] ) ) {
				$this->dont_render = $args['part'];
			}

			if ( ! empty( $template_slug ) ) {
				$this->load( $template_slug, $args );
			}
		}

		/**
		 * @param string $template_slug
		 * @param array  $args
		 * @return void
		 */
		public function load( string $template_slug, array $args ): void {
			$template_path = $this->get_template_path( $template_slug );
			$template      = $this->get_template( $template_path );

			// get only part of template if requested
			if ( ! empty( $args['part'] ) ) {
				$template = $this->get_template_part( $args['part'], $template );
			}

			$this->template = $template;
			$template_parts = array();

			$template_parts['main'] = $template;

			// separate full & empty cart parts (array: can be multiple!)
			$conditional_parts['empty_cart'] = $this->get_template_part( 'empty_cart', $template, 'with_tag', true );
			$conditional_parts['full_cart']  = $this->get_template_part( 'full_cart', $template, 'with_tag', true );

			// insert placeholders for each template part
			foreach ( $conditional_parts as $tag => $matches ) {
				if ( empty( $matches ) ) {
					continue;
				}

				foreach ( $matches as $match_key => $match ) {
					$match_tag                            = "{{{$tag}_{$match_key}}}";
					$template_parts[ $tag ][ $match_tag ] = $match;
					$template_parts['main']               = str_replace( $match, $match_tag, $template_parts['main'] );
				}
			}

			// separate submenu and replace with new placeholder {{submenu}} if found
			$template_parts['submenu'] = $this->get_template_part( 'submenu', $template_parts['main'] );

			if ( false !== $template_parts['submenu'] ) {
				// echo "<pre>";var_dump($template_parts['submenu']);die();
				$template_parts['main'] = str_replace( $template_parts['submenu'], '{{submenu}}', $template_parts['main'] );

				// separate submenu item li and replace with new placeholder {{items}} if found
				$template_parts['submenu_items'] = $this->get_template_part( 'submenu_items', $template_parts['submenu'] );

				if ( false !== $template_parts['submenu_items'] ) {
					$template_parts['submenu'] = str_replace( $template_parts['submenu_items'], '{{items}}', $template_parts['submenu'] );
				}
			}

			$this->template_parts = $template_parts;
		}

		/**
		 * @param string $file
		 * @return string
		 */
		public function get_template( string $file ): string {
			ob_start();

			if ( file_exists( $file ) ) {
				include $file;
			}

			return ob_get_clean();
		}

		/**
		 * Get the template path for a file. Checks child theme, theme, and each
		 * registered plugin template folder in order, first match wins.
		 *
		 * @param  string $template_slug
		 * @return string
		 */
		public function get_template_path( string $template_slug ): string {
			$template_locations = apply_filters(
				'wpo_wpmenucart_template_locations',
				array(
					'child_theme_template_path' => get_stylesheet_directory() . '/woocommerce/wp-menu-cart/',
					'theme_template_path'       => get_template_directory() . '/woocommerce/wp-menu-cart/',
					'plugin_template_path'      => WPO_Menu_Cart()->plugin_path() . '/templates/',
				)
			);

			$valid_extensions = array( '.html', '.php' );

			$filepath = '';
			foreach ( $template_locations as $template_path ) {
				foreach ( $valid_extensions as $extension ) {
					if ( file_exists( $template_path . $template_slug . $extension ) ) {
						$filepath = $template_path . $template_slug . $extension;
						break 2;
					}
				}
			}

			return apply_filters( 'wpmenucart_custom_template_path', $filepath, $template_slug );
		}

		/**
		 * @param string $tag
		 * @param string $template
		 * @param string $return_type
		 * @param bool   $all
		 * @return array|false
		 */
		public function get_template_part( string $tag, string $template, string $return_type = 'with_tag', bool $all = false ) {
			// compose regex
			$regex = sprintf( '#{{%1$s_start}}(.*?){{%1$s_end}}#s', $tag );

			// perform regex search
			if ( $all ) {
				$preg_match_return = preg_match_all( $regex, $template, $preg_matches );
			} else {
				$preg_match_return = preg_match( $regex, $template, $preg_matches );
			}

			// check if we have matches
			if ( false === $preg_match_return || 0 == $preg_match_return ) {
				return false;
			}

			// get match set with or without tags
			switch ( $return_type ) {
				case 'with_tag':
					$matches = $preg_matches[0];
					break;
				case 'without_tag':
					$matches = $preg_matches[1];
					break;
			}

			return $matches;
		}

		/**
		 * @return string
		 */
		public function get_output(): string {
			// main menu item replacements
			$menu_item = $this->make_replacements( $this->template_parts['main'] );

			/**
			 * Whether the submenu (cart contents list) should render for this template.
			 *
			 * @param bool                $should_render_submenu
			 * @param WpMenuCart_Template $template
			 */
			$should_render_submenu = ! empty( $this->template_parts['submenu'] ) && apply_filters( 'wpo_wpmenucart_should_render_submenu', 'menucart-slideout' === $this->template_slug, $this );

			if ( $should_render_submenu ) {
				// submenu replacements
				$submenu           = $this->make_replacements( $this->template_parts['submenu'] );
				$submenu_item_data = WPO_Menu_Cart()->shop->submenu_items();

				if ( ! empty( $submenu_item_data ) ) {
					// submenu item replacements (the piece the resistence)
					$rendered_submenu_items = array();

					foreach ( $submenu_item_data as $item_data ) {
						$rendered_submenu_items[] = $this->make_replacements( $this->template_parts['submenu_items'], $item_data );
					}

					// legacy filter
					$rendered_submenu_items = apply_filters( 'wpmenucart_submenu_items', implode( "\n", $rendered_submenu_items ) );

					// insert items into submenu
					$submenu = str_replace( '{{items}}', $rendered_submenu_items, $submenu );

					// remove empty cart placeholders
					foreach ( $this->template_parts['empty_cart'] as $tag => $match ) {
						$submenu = str_replace( $tag, '', $submenu );
					}

					// render full cart placeholders
					foreach ( $this->template_parts['full_cart'] as $tag => $match ) {
						// + legacy filter
						$replacement = apply_filters( 'wpmenucart_cart_link_item', $this->make_replacements( $match ) );
						$submenu     = str_replace( $tag, $replacement, $submenu );
					}
				} else {
					// no items
					$submenu = str_replace( '{{items}}', '', $submenu );

					// remove full cart placeholders
					foreach ( $this->template_parts['full_cart'] as $tag => $match ) {
						$submenu = str_replace( $tag, '', $submenu );
					}

					// render empty cart placeholders
					foreach ( $this->template_parts['empty_cart'] as $tag => $match ) {
						$replacement = $this->make_replacements( $match );
						$submenu     = str_replace( $tag, $replacement, $submenu );
					}
				}

				// insert submenu into main menu
				$menu_item = str_replace( '{{submenu}}', $submenu, $menu_item );
			} else {
				// no submenu => remove placeholder
				$menu_item = str_replace( '{{submenu}}', '', $menu_item );
			}

			// replace start and end tags
			$start_and_end_tags = apply_filters( 'wpo_wpmenucart_all_start_end_tags', array( 'submenu', 'submenu_items', 'empty_cart', 'full_cart' ) );
			foreach ( $start_and_end_tags as $tag ) {
				$menu_item = str_replace( "{{{$tag}_start}}", apply_filters( "wpmenucart_{$tag}_start", '', $this ), $menu_item );
				$menu_item = str_replace( "{{{$tag}_end}}", apply_filters( "wpmenucart_{$tag}_end", '', $this ), $menu_item );
			}

			return $menu_item;
		}

		/**
		 * @param string $template
		 * @param array  $data
		 * @return string
		 */
		public function make_replacements( string $template, array $data = array() ): string {
			$data_class = apply_filters( 'wpo_wpmenucart_data_class', 'WpMenuCart_Data' );
			$mcp_data   = new $data_class( $this->nav_menu_items, $this->menu_slug, $this->menu_args, $this->template_slug );

			// get array of placeholders
			preg_match_all( '#{{.*?}}#s', $template, $placeholders );
			$placeholders = array_shift( $placeholders ); // we only need the first match set

			// loop through placeholders and see if we have a matching function (to write data)
			foreach ( $placeholders as $placeholder ) {
				// dont_render is used for the outer tags when doing ajax (woocommerce fragments)
				if ( "{{{$this->dont_render}_start}}" === $placeholder || "{{{$this->dont_render}_end}}" === $placeholder ) {
					$template = str_replace( $placeholder, '', $template );
					continue;
				}

				$replacement_method = trim( $placeholder, '{}' );
				if ( method_exists( $mcp_data, $replacement_method ) ) {
					$replacement = call_user_func( array( $mcp_data, $replacement_method ), $data );
					$template    = str_replace( $placeholder, $replacement ?? '', $template );
				}
			}

			return $template;
		}

		/**
		 * @return void
		 */
		public function write_output(): void {
			echo wp_kses_post( $this->get_output() );
		}
	}


endif; // class_exists
