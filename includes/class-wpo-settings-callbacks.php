<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_Settings_Callbacks_2' ) ) :

	class WPO_Settings_Callbacks_2 {
		/**
		 * Section null callback.
		 *
		 * @return void
		 */
		public function section(): void {
		}

		/**
		 * Checkbox callback.
		 *
		 * @param  array $args Args:
		 *   option_name - name of the main option
		 *   id          - key of the setting
		 *   value       - value if not 1 (optional)
		 *   default     - default setting (optional)
		 *   description - description (optional)
		 *
		 * @return void
		 */
		public function checkbox( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			$disabled = isset( $disabled ) ? ' disabled' : '';

			// output checkbox  
			printf( '<input type="checkbox" id="%1$s" name="%2$s" value="%3$s"%4$s %5$s />', esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $value ), checked( $value, $current, false ), esc_attr( $disabled ) );
	
			// output description.
			if ( isset( $description ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}
		}

		/**
		 * Text input callback.
		 *
		 * @param  array $args Args:
		 *   option_name - name of the main option
		 *   id          - key of the setting
		 *   size        - size of the text input (em)
		 *   default     - default setting (optional)
		 *   description - description (optional)
		 *   type        - type (optional)
		 *
		 * @return void
		 */
		public function text_input( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			if ( empty( $type ) ) {
				$type = 'text';
			}

			printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" size="%5$s" placeholder="%6$s"/>', esc_attr( $type ), esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $current ), esc_attr( $size ), esc_attr( $placeholder ) );
	
			// output description.
			if ( isset( $description ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}
		}

		/**
		 * Textarea callback.
		 *
		 * @param  array $args Args:
		 *   option_name - name of the main option
		 *   id          - key of the setting
		 *   width       - width of the text input (em)
		 *   height      - height of the text input (lines)
		 *   default     - default setting (optional)
		 *   description - description (optional)
		 *
		 * @return void
		 */
		public function textarea( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );
	
			printf( '<textarea id="%1$s" name="%2$s" cols="%4$s" rows="%5$s" placeholder="%6$s"/>%3$s</textarea>', esc_attr( $id ), esc_attr( $setting_name ), esc_textarea( $current ), esc_attr( $width ), esc_attr( $height ), esc_attr( $placeholder ) );
	
			// output description.
			if ( isset( $description ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}
		}

		/**
		 * Select element callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return void
		 */
		public function select( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );
	
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $setting_name ) );

			foreach ( $options as $key => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_attr( $label ) );
			}

			echo '</select>';

			if ( isset( $custom ) ) {
				printf( '<div class="%1$s_custom custom custom-select-option">', esc_attr( $id ) );

				if ( method_exists( $this, $custom['type'] ) ) {
					call_user_func( array( $this, $custom['type'] ), $custom['args'] );
				}
				echo '</div>';
			}
	
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
			}

		}

		/**
		 * Radio element callback.
		 *
		 * @param  array $args Field arguments.
		 *
		 * @return void
		 */
		public function radio_button( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );
	
			foreach ( $options as $key => $label ) {
				printf( '<input type="radio" class="radio" id="%1$s[%3$s]" name="%2$s" value="%3$s"%4$s />', esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $key ), checked( $current, $key, false ) );
				printf( '<label for="%1$s[%3$s]"> %4$s</label><br>', esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $key ), esc_attr( $label ) );
			}
		
	
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
			}

		}

		/**
		 * Multiple text element callback.
		 *
		 * @param  array $args Field arguments.
		 * 
		 * @return void
		 */
		public function multiple_text_input( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			if ( ! empty( $header ) ) {
				echo esc_html( "<p><strong>{$header}</strong>:</p>" );
			}

			foreach ( $fields as $name => $field ) {
				$label       = $field['label'];
				$size        = $field['size'];
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

				if ( isset( $field['label_width'] ) ) {
					$style = sprintf( 'style="display:inline-block; width:%1$s;"', $field['label_width'] );
				} else {
					$style = '';
				}

				$suffix = isset( $field['suffix'] ) ? $field['suffix'] : '';

				// output field label
				printf( '<label for="%1$s_%2$s" %3$s>%4$s</label>', esc_attr( $id ), esc_attr( $name ), esc_attr( $style ), esc_attr( $label ) );

				// output field
				$field_current = isset( $current[ $name ] ) ? $current[ $name ] : '';
				printf( '<input type="text" id="%1$s_%3$s" name="%2$s[%3$s]" value="%4$s" size="%5$s" placeholder="%6$s"/>%7$s<br/>', esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $name ), esc_attr( $field_current ), esc_attr( $size ), esc_attr( $placeholder ), esc_attr( $suffix ) );

			}
	
			// Displays option description.
			if ( isset( $args['description'] ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
			}
		}

		/**
		 * Wrapper function to create tabs for settings in different languages
		 *
		 * @param  array $args Field arguments
		 *
		 * @return void
		 */
		public function i18n_wrap ( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			$languages = $this->get_languages();
			if ( ! empty( $languages ) ) {
				printf( '<div id="%s-%s-translations" class="translations">', esc_attr( $option_name ), esc_attr( $id ) )
					?>
					<ul>
						<?php 
						foreach ( $languages as $lang_code => $language_name ) {
							$translation_id = "{$option_name}_{$id}_{$lang_code}";
							printf( '<li><a href="#%s">%s</a></li>', esc_attr( $translation_id ), esc_attr( $language_name ) );
						}
						?>
					</ul>
					<?php 
					foreach ( $languages as $lang_code => $language_name ) {
						$translation_id = esc_html( "{$option_name}_{$id}_{$lang_code}" );
						printf( '<div id="%s">', esc_attr( $translation_id ) );
						$args['lang'] = $lang_code;
						// don't use internationalized placeholders since they're not translated,
						// to avoid confusion (user thinking they're all the same)
						if ( 'multiple_text_input' == $callback ) {
							foreach ( $fields as $key => $field_args ) {
								if ( ! empty( $field_args['placeholder'] ) && isset( $field_args['i18n_placeholder'] ) ) {
									$args['fields'][$key]['placeholder'] = '';
								}
							}
						} else {
							if ( ! empty( $args['placeholder'] ) && isset( $args['i18n_placeholder'] ) ) {
								$args['placeholder'] = '';
							}
						}
						// specific description for internationalized fields (to compensate for missing placeholder)
						if ( ! empty( $args['i18n_description'] ) ) {
							$args['description'] = $args['i18n_description'];
						}
						call_user_func( array( $this, $callback ), $args );
						echo '</div>';
					}
					?>
				</div>
				<?php
			} else {
				$args['lang'] = 'default';
				call_user_func( array( $this, $callback ), $args );
			}
		}

		/**
		 * Get languages
		 *
		 * @return array|false
		 */
		public function get_languages() {
			$wpml = class_exists( 'SitePress' );
			// $wpml = true; // for development

			if ( $wpml ) {
				// use this instead of function call for development outside of WPML
				// $icl_get_languages = 'a:3:{s:2:"en";a:8:{s:2:"id";s:1:"1";s:6:"active";s:1:"1";s:11:"native_name";s:7:"English";s:7:"missing";s:1:"0";s:15:"translated_name";s:7:"English";s:13:"language_code";s:2:"en";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/en.png";s:3:"url";s:23:"http://yourdomain/about";}s:2:"fr";a:8:{s:2:"id";s:1:"4";s:6:"active";s:1:"0";s:11:"native_name";s:9:"Français";s:7:"missing";s:1:"0";s:15:"translated_name";s:6:"French";s:13:"language_code";s:2:"fr";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/fr.png";s:3:"url";s:29:"http://yourdomain/fr/a-propos";}s:2:"it";a:8:{s:2:"id";s:2:"27";s:6:"active";s:1:"0";s:11:"native_name";s:8:"Italiano";s:7:"missing";s:1:"0";s:15:"translated_name";s:7:"Italian";s:13:"language_code";s:2:"it";s:16:"country_flag_url";s:43:"http://yourdomain/wpmlpath/res/flags/it.png";s:3:"url";s:26:"http://yourdomain/it/circa";}}';
				// $icl_get_languages = unserialize($icl_get_languages);
			
				$icl_get_languages = icl_get_languages( 'skip_missing=0' );
				$languages         = array();
				foreach ( $icl_get_languages as $lang => $data ) {
					$languages[ $data['language_code'] ] = $data['native_name'];
				}
			} else {
				return false;
			}

			return $languages;
		}

		/**
		 * Normalize the arguments
		 *
		 * @param  array $args Field arguments
		 *
		 * @return array
		 */
		public function normalize_settings_args ( array $args ): array {
			$args['value'] = isset( $args['value'] ) ? $args['value'] : 1;

			$args['placeholder'] = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

			// get main settings array
			$option = get_option( $args['option_name'] );
	
			$args['setting_name'] = "{$args['option_name']}[{$args['id']}]";

			if ( isset( $args['lang'] ) ) {
				// i18n settings name
				$args['setting_name'] = "{$args['setting_name']}[{$args['lang']}]";
				// copy current option value if set
			
				if ( 'default' == $args['lang'] && ! empty( $option[ $args['id'] ] ) && ! isset( $option[ $args['id'] ]['default'] ) ) {
					// we're switching back from WPML to normal
					// try english first
					if ( isset( $option[ $args['id'] ]['en'] ) ) {
						$args['current'] = $option[ $args['id'] ]['en'];
					} elseif ( is_array( $option[ $args['id'] ] ) ) {
						// fallback to the first language if english not found
						$first = array_shift( $option[ $args['id'] ] );
						if ( ! empty( $first ) ) {
							$args['current'] = $first;
						}
					} elseif ( is_string( $option[ $args['id'] ] ) ) {
						$args['current'] = $option[ $args['id'] ];
					} else {
						// nothing, really?
						$args['current'] = '';
					}
				} else {
					if ( isset( $option[ $args['id'] ][ $args['lang'] ] ) ) {
						$args['current'] = $option[ $args['id'] ][ $args['lang'] ];
					} elseif ( isset( $option[ $args['id'] ]['default'] ) ) {
						$args['current'] = $option[ $args['id'] ]['default'];
					} elseif ( isset( $option[ $args['id'] ] ) && is_string( $option[ $args['id'] ] ) ) {
						$args['current'] = $option[ $args['id'] ];
					} else {
						// nothing, really?
						$args['current'] = '';
					}
				}
			} else {
				// copy current option value if set
				if ( isset( $option[ $args['id'] ] ) ) {
					$args['current'] = $option[ $args['id'] ];
				}
			}

			// falback to default or empty if no value in option
			if ( ! isset( $args['current'] ) ) {
				$args['current'] = isset( $args['default'] ) ? $args['default'] : '';
			}

			return $args;
		}

		/**
		 * Validate options.
		 *
		 * @param  array $input options to valid.
		 *
		 * @return array
		 */
		public function validate( array $input ): array {
			// echo '<pre>';var_dump($input);die('</pre>');
			// Create our array for storing the validated options.
			$output = array();

			if ( empty( $input ) || ! is_array( $input ) ) {
				return $input;
			}
	
			// Loop through each of the incoming options.
			foreach ( $input as $key => $value ) {
	
				// Check to see if the current option has a value. If so, process it.
				if ( isset( $input[ $key ] ) ) {
					if ( is_array( $input[ $key ] ) ) {
						foreach ( $input[ $key ] as $sub_key => $sub_value ) {
							$output[ $key ][ $sub_key ] = $input[ $key ][ $sub_key ];
						}
					} else {
						$output[ $key ] = $input[ $key ];
					}
				}
			}
	
			// Return the array processing any additional functions filtered by this action.
			return apply_filters( 'wpmenucart_validate_input', $output, $input );
		}
	}


endif; // class_exists
