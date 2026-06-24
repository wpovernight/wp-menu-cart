<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPO_Settings_Callbacks_2' ) ) {
	include_once 'class-wpo-settings-callbacks.php';
}

if ( ! class_exists( 'WpMenuCart_Settings_Callbacks' ) ) :

	class WpMenuCart_Settings_Callbacks extends WPO_Settings_Callbacks_2 {

		/**
		 * Checkbox with optional Pro overlay.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function checkbox( array $args ): void {
			$this->render_with_pro_overlay( 'checkbox', $args, 'menucartflyout' );
		}

		/**
		 * Select with optional Pro overlay.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function select( array $args ): void {
			$this->render_with_pro_overlay( 'select', $args, 'menucartflyout' );
		}

		/**
		 * Text input with optional Pro overlay.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function text_input( array $args ): void {
			$this->render_with_pro_overlay( 'text_input', $args, 'menucartcustomclass' );
		}

		/**
		 * Radio with optional Pro overlay.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function radio_button( array $args ): void {
			$this->render_with_pro_overlay( 'radio_button', $args, 'menucartflyout' );
		}

		/**
		 * Select element callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return void
		 */
		public function shop_select( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );
	
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $setting_name ) );

			foreach ( $options as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_attr( $key ) );
			}

			echo '</select>';

			if ( isset( $custom ) ) {
				printf( '<div class="%1$s_custom custom">', esc_attr( $id ) );

				switch ( $custom['type'] ) {
					case 'text_element_callback':
						$this->text_input( $custom['args'] );
						break;      
					case 'multiple_text_element_callback':
						$this->multiple_text_input( $custom['args'] );
						break;      
					default:
						break;
				}
				echo '</div>';
			}
	
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', $args['description'] );
			}
		}

		/**
		 * Media upload button with optional Pro overlay.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function media_upload_callback( array $args ): void {
			$pro = $args['pro'] ?? false;

			extract( $this->normalize_settings_args( $args ) );

			$disabled = isset( $disabled ) ? ' disabled' : '';

			$html = sprintf(
				'<input type="button" id="%1$s" name="%2$s" class="btn button-primary" value="%3$s"%4$s />',
				esc_attr( $id ),
				esc_attr( $setting_name ),
				esc_attr( $uploader_button_text ?? '' ),
				esc_attr( $disabled )
			);

			if ( isset( $description ) ) {
				$html .= sprintf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}

			if ( $disabled && $pro ) {
				$html .= $this->pro_overlay( 'menucartflyout' );
				$html  = '<div class="pro-setting-wrapper">' . $html . '</div>';
			}

			echo wp_kses( $html, $this->get_allowed_html() );
		}

		/**
		 * Icon radio with locked icons 1-13 when Pro not active.
		 *
		 * @param  array $args
		 *
		 * @return void
		 */
		public function icons_radio_element_callback( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			$icons  = '';
			$radios = '';

			foreach ( $options as $key => $iconnumber ) {
				if ( '0' === (string) $key ) {
					$icons  .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s]"><i class="wpmenucart-icon-shopping-cart-%3$s"></i></label></td>', esc_attr( $id ), esc_attr( $key ), esc_attr( $iconnumber ) );
					$radios .= sprintf( '<td style="padding-top:0" align="center"><input type="radio" class="radio" id="%1$s[%2$s]" name="%3$s" value="%2$s"%4$s /></td>', esc_attr( $id ), esc_attr( $key ), esc_attr( $setting_name ), checked( $current, $key, false ) );
				} else {
					$icons .= sprintf( '<td style="padding-bottom:0;font-size:16pt;" align="center"><label for="%1$s[%2$s]"><img src="%3$scart-icon-%4$s.png" /></label></td>', esc_attr( $id ), esc_attr( $key ), esc_url( WPO_Menu_Cart()->plugin_url() . '/assets/images/' ), esc_attr( $iconnumber ) );
					$radio  = sprintf( '<input type="radio" class="radio" id="%1$s[%2$s]" name="%3$s" value="%2$s" disabled />', esc_attr( $id ), esc_attr( $key ), esc_attr( $setting_name ) );
					$radio .= '<div class="hidden-input-icon"></div>';
					$radio  = '<div class="pro-setting-wrapper">' . $radio . '</div>';
					$radios .= '<td style="padding-top:0" align="center">' . $radio . '</td>';
				}
			}

			$profeature = '<span style="display:none;" class="pro-icon"><i>' . __( 'Additional icons are only available in', 'wp-menu-cart' ) . ' <a href="https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=menucarticons">Menu Cart Pro</a></i></span>';
			$html       = '<table><tr>' . $icons . '</tr><tr>' . $radios . '</tr></table>' . $profeature;

			echo wp_kses( $html, $this->get_allowed_html() );
		}

		/**
		 * Helper method: Renders a callback from the base class and wraps it in a Pro overlay.
		 *
		 * @param string $method   The method to call.
		 * @param array  $args     The field arguments.
		 * @param string $campaign The UTM campaign slug for the link.
		 *
		 * @return void
		 */
		protected function render_with_pro_overlay( string $method, array $args, string $campaign ): void {
			$pro      = $args['pro'] ?? false;
			$disabled = ! empty( $args['disabled'] );

			// Capture the exact HTML output from the base WPO class
			ob_start();
			parent::$method( $args );
			$html = ob_get_clean();

			if ( $disabled && $pro ) {
				// The base class doesn't naturally support 'disabled' for fields like select & text.
				// We can dynamically inject it here if it's missing, without rewriting the HTML logic.
				if ( false === strpos( $html, 'disabled' ) ) {
					$html = preg_replace( '/<(input|select|textarea)([^>]+)>/i', '<$1 disabled="disabled"$2>', $html );
				}

				$html .= $this->pro_overlay( $campaign );
				$html  = '<div class="pro-setting-wrapper">' . $html . '</div>';
			}

			echo wp_kses( $html, $this->get_allowed_html() );
		}

		/**
		 * Generate the Pro overlay HTML.
		 *
		 * @param  string $campaign UTM campaign slug.
		 *
		 * @return string
		 */
		protected function pro_overlay( string $campaign ): string {
			return sprintf(
				' <span class="pro-feature"><i>%s <a href="%s">Menu Cart Pro</a></i></span><div class="hidden-input"></div>',
				esc_html__( 'This feature only available in', 'wp-menu-cart' ),
				esc_url( 'https://wpovernight.com/downloads/menu-cart-pro?utm_source=wordpress&utm_medium=menucartfree&utm_campaign=' . $campaign )
			);
		}

		/**
		 * Allowed HTML for wp_kses output.
		 *
		 * @return array
		 */
		protected function get_allowed_html(): array {
			return array(
				'input'  => array( 'type' => array(), 'id' => array(), 'name' => array(), 'value' => array(), 'size' => array(), 'disabled' => array(), 'checked' => array(), 'class' => array(), 'placeholder' => array() ),
				'label'  => array( 'for' => array() ),
				'table'  => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'tr'     => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'td'     => array( 'id' => array(), 'class' => array(), 'style' => array(), 'colspan' => array(), 'rowspan' => array(), 'align' => array() ),
				'a'      => array( 'href' => array(), 'title' => array(), 'id' => array(), 'class' => array(), 'style' => array(), 'target' => array(), 'rel' => array() ),
				'select' => array( 'id' => array(), 'name' => array(), 'class' => array(), 'disabled' => array() ),
				'option' => array( 'value' => array(), 'selected' => array() ),
				'div'    => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'span'   => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'p'      => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'i'      => array( 'class' => array() ),
				'img'    => array( 'src' => array() ),
				'b'      => array(), 'br' => array(), 'em' => array(), 'strong' => array(),
			);
		}

		/**
		 * Get menu array.
		 * 
		 * @return array menu slug => menu name
		 */
		public function get_menu_array() {
			$menu_list = array();
			$menus     = get_terms( array(
				'taxonomy'   => 'nav_menu',
				'hide_empty' => false,
			) );
	
			foreach ( $menus as $menu ) {
				$menu_list[ $menu->slug ] = $menu->name;
			}
		
			if ( ! empty( $menu_list ) ) {
				return $menu_list;
			}
		}

	}

endif;
