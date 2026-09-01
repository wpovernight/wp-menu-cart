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
		 * Range slider input callback.
		 *
		 * @param  array $args Field arguments.
		 * @return void
		 */
		public function range_slider( array $args ): void {
			$label       = $args['label'] ?? null;
			$description = $args['description'] ?? '';

			$render = function() use ( $args, $description ) {
				extract( $this->normalize_settings_args( $args ) );

				$min     = isset( $min ) ? $min : 0;
				$max     = isset( $max ) ? $max : 100;
				$step    = isset( $step ) ? $step : 1;
				$unit    = isset( $unit ) ? $unit : 'px';
				$current = max( $min, min( $max, intval( $current ?? 0 ) ) ); // Ensure current value is within range

				$this->render_range_slider_inputs( array(
					'id'           => $id,
					'setting_name' => $setting_name,
					'current'      => $current,
					'min'          => $min,
					'max'          => $max,
					'step'         => $step,
					'unit'         => $unit,
				) );

				if ( ! empty( $description ) ) {
					printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
				}
			};

			if ( $label ) {
				$this->render_subpanel_field_row( $label, $render );
			} else {
				$render();
			}
		}

		/**
		 * Render the range slider input elements.
		 *
		 * @param array $args {
		 *     @type string $id           Unique element ID.
		 *     @type string $setting_name Form field name attribute.
		 *     @type int    $current      Current value.
		 *     @type int    $min          Minimum value.
		 *     @type int    $max          Maximum value.
		 *     @type int    $step         Step increment.
		 *     @type string $unit         Unit label.
		 * }
		 * @return void
		 */
		private function render_range_slider_inputs( array $args ): void {
			extract( $args );

			// Container for slider and value display
			echo '<div class="wpmenucart-range-slider-container">';
			
			// The range slider
			printf(
				'<input type="range" id="%1$s_range" class="wpmenucart-range-slider" value="%2$s" min="%3$s" max="%4$s" step="%5$s" data-target="%1$s_input" />',
				esc_attr( $id ),
				esc_attr( $current ),
				esc_attr( $min ),
				esc_attr( $max ),
				esc_attr( $step )
			);
			
			// Hidden input that stores the actual value for form submission
			printf(
				'<input type="hidden" id="%1$s_hidden" name="%2$s" value="%3$s" />',
				esc_attr( $id ),
				esc_attr( $setting_name ),
				esc_attr( $current )
			);
			
			// Simple input with unit label
			echo '<div class="wpmenucart-range-value-group">';
			printf(
				'<input type="number" id="%1$s_input" class="wpmenucart-range-input" value="%2$s" min="%3$s" max="%4$s" step="%5$s" data-range="%1$s_range" data-hidden="%1$s_hidden" />',
				esc_attr( $id ),
				esc_attr( $current ),
				esc_attr( $min ),
				esc_attr( $max ),
				esc_attr( $step )
			);
			printf(
				'<span class="wpmenucart-range-unit">%s</span>',
				esc_html( $unit )
			);
			echo '</div>';
			
			echo '</div>';
		}

		/**
		 * Resolve a stored mode value to one that's actually renderable right now.
		 *
		 * @param  string $mode The raw stored mode value.
		 * @return string
		 */
		protected function resolve_available_mode( string $mode ): string {
			foreach ( $this->get_cart_mode_options() as $mode_option ) {
				if ( $mode_option['value'] === $mode ) {
					return empty( $mode_option['disabled'] ) ? $mode : 'none';
				}
			}

			return 'none';
		}

		/**
		 * Resolve a stored Icon Style template value to one that's actually renderable right now.
		 *
		 * @param  string $template The raw stored template value.
		 * @return string
		 */
		protected function resolve_available_icon_style( string $template ): string {
			foreach ( $this->get_icon_style_options() as $option ) {
				if ( $option['value'] === $template ) {
					return empty( $option['disabled'] ) ? $template : '';
				}
			}

			return '';
		}

		/**
		 * Cart display modes section callback.
		 *
		 * @param  array $option_values Saved option values.
		 * @return void
		 */
		public function cart_display_modes_section( array $option_values ): void {
			$desktop_mode = $this->resolve_available_mode( $option_values['desktop_cart_mode'] ?? 'none' );
			$mobile_mode  = $this->resolve_available_mode( $option_values['mobile_cart_mode']  ?? 'none' );

			echo '<div class="wpmenucart-section wpmenucart-section--cart-display-modes">';

			$this->maybe_render_conflict_banner();

			do_action( 'wpo_wpmenucart_before_mode_groups' );

			$mode_groups = apply_filters( 'wpo_wpmenucart_mode_groups', array(
				'desktop' => array(
					'current_value' => $desktop_mode,
					'option_key'    => 'desktop_cart_mode',
					'heading_args'  => array(
						'icon'         => 'desktop-cart-mode.svg',
						'label'        => __( 'Desktop Cart Mode', 'wp-menu-cart' ),
						'screen_label' => '&gt; 768px',
					)
				),
				'mobile'  => array(
					'current_value' => $mobile_mode,
					'option_key'    => 'mobile_cart_mode',
					'heading_args'  => array(
						'icon'         => 'mobile-cart-mode.svg',
						'label'        => __( 'Mobile Cart Mode', 'wp-menu-cart' ),
						'screen_label' => '&lt;= 768px',
					)
				),
			) );

			foreach ( $mode_groups as $context => $mode_group ) {
				$this->render_mode_group(
					$context,
					$mode_group['current_value'],
					$mode_group['option_key'],
					$mode_group['heading_args'] ?? array()
				);
			}

			do_action( 'wpo_wpmenucart_after_mode_groups' );

			echo '</div>';
		}

		/**
		 * Render a full mode group for a given context.
		 *
		 * @param  string $context      'desktop' or 'mobile'.
		 * @param  string $current_mode Currently saved mode value.
		 * @param  string $option_key   The key within the option array.
		 * @param  array  $heading      Heading config with keys: icon, label, screen_label.
		 * @return void
		 */
		private function render_mode_group( string $context, string $current_mode, string $option_key, array $heading ): void {
			printf( '<div class="wpmenucart-mode-group" data-context="%s">', esc_attr( $context ) );

			echo '<h3 class="wpmenucart-mode-group__heading">';

			if ( isset( $heading['icon'] ) && $heading['icon'] ) {
				echo '<span class="wpmenucart-mode-group__icon" aria-hidden="true">';
				$this->render_svg( $heading['icon'] );
				echo '</span>';
			}

			echo esc_html( $heading['label'] );
			printf(
				' <span class="wpmenucart-mode-group__screen-label">(%s %s)</span>',
				esc_html__( 'Screens', 'wp-menu-cart' ),
				$heading['screen_label']
			);
			echo '</h3>';

			do_action( 'wpo_wpmenucart_before_mode_group_cards', $context );

			echo '<div class="wpmenucart-mode-cards">';
			foreach ( $this->get_cart_mode_options() as $mode ) {
				$args = apply_filters(
					'wpo_wpmenucart_render_mode_card_args',
					array(
						'mode'             => $mode['value'],
						'name'             => $mode['name'],
						'description'      => $mode['description'],
						'current_value'    => $current_mode,
						'option_key'       => $option_key,
						'pro'              => $mode['pro'] ?? false,
						'disabled'         => $mode['disabled'] ?? false,
						'disabled_tooltip' => $mode['disabled_tooltip'] ?? '',
					),
					$context,
					$mode
				);

				$this->render_mode_card( $args );
			}
			echo '</div>';

			do_action( 'wpo_wpmenucart_after_mode_group_cards', $context );

			$this->render_sidebar_subpanel( $context );

			do_action( 'wpo_wpmenucart_after_mode_group_subpanels', $context );

			echo '</div>';
		}

		/**
		 * Icon Style section callback.
		 *
		 * @param  array $option_values Saved option values.
		 * @return void
		 */
		public function icon_style_section( array $option_values ): void {
			$current_template = $this->resolve_available_icon_style( $option_values['icon_style_template'] ?? '' );
			$custom_enabled   = ! empty( $option_values['icon_style_custom_enabled'] );

			echo '<div class="wpmenucart-section wpmenucart-section--icon-style">';

			do_action( 'wpo_wpmenucart_before_icon_style_cards' );

			printf( '<div class="wpmenucart-mode-group" data-context="icon_style">' );

			printf( '<h3 class="wpmenucart-mode-group__heading">%s</h3>', esc_html__( 'Templates', 'wp-menu-cart' ) );

			printf(
				'<div class="wpmenucart-mode-cards%s">',
				$custom_enabled ? ' wpmenucart-mode-cards--disabled' : ''
			);
			foreach ( $this->get_icon_style_options() as $template ) {
				$args = apply_filters(
					'wpo_wpmenucart_render_icon_style_card_args',
					array(
						'mode'             => $template['value'],
						'name'             => $template['name'],
						'description'      => $template['description'],
						'current_value'    => $current_template,
						'option_key'       => 'icon_style_template',
						'pro'              => $template['pro'] ?? false,
						'disabled'         => $template['disabled'] ?? false,
						'disabled_tooltip' => $template['disabled_tooltip'] ?? '',
					),
					$template
				);

				$this->render_mode_card( $args );
			}
			echo '</div>';

			do_action( 'wpo_wpmenucart_after_icon_style_cards' );

			do_action( 'wpo_wpmenucart_icon_style_subpanels' );

			echo '</div>'; // .wpmenucart-mode-group

			$notice_attributes = $this->normalize_custom_attributes( array(
				'data-show_for_option_name' => WpMenuCart_Settings::OPTION_NAME . '[icon_style_custom_enabled]',
			) );

			printf(
				'<p class="wpmenucart-icon-style-disabled-notice"%s %s>%s</p>',
				$custom_enabled ? '' : ' style="display:none;"',
				$notice_attributes,
				esc_html__( 'Templates are disabled while Custom Section is enabled.', 'wp-menu-cart' )
			);

			$this->render_custom_section( $custom_enabled );

			echo '</div>'; // .wpmenucart-section--icon-style
		}

		/**
		 * Render the Custom section: fields called directly rather than
		 * through do_settings_fields(), so the row layout can match the design.
		 *
		 * @param  bool $enabled Whether the Custom section is currently toggled on.
		 * @return void
		 */
		protected function render_custom_section( bool $enabled ): void {
			printf( '<div class="wpmenucart-custom-section-toggle-row">' );
			echo '<span class="wpmenucart-custom-section-toggle-row__icon" aria-hidden="true">';
			$this->render_svg( 'custom-icon-style.svg' );
			echo '</span>';
			printf( '<span class="wpmenucart-custom-section-toggle-row__label">%s</span>', esc_html__( 'Custom', 'wp-menu-cart' ) );

			$this->render_registered_fields( 'icon_style_custom_toggle' );

			echo '</div>';

			printf(
				'<div class="wpmenucart-subpanel wpmenucart-custom-section-fields%s">',
				$enabled ? '' : ' wpmenucart-custom-section-fields--collapsed'
			);

			$this->render_registered_fields( 'icon_style_custom' );

			echo '</div>';
		}

		/**
		 * Render every field registered to a page/section through
		 * add_settings_field(), using our own row markup instead of
		 * do_settings_fields()'s fixed <tr><th><td> table markup. Fields
		 * stay registered through the normal WP Settings API.
		 *
		 * @param  string $section Section id fields were registered under.
		 * @return void
		 */
		protected function render_registered_fields( string $section ): void {
			global $wp_settings_fields;

			foreach ( $wp_settings_fields[ WpMenuCart_Settings::PAGE_ICON_STYLE ][ $section ] ?? array() as $field ) {
				$args = $field['args'];

				if ( '' === $field['title'] ) {
					// No label row wanted, e.g. the Custom section's own toggle.
					call_user_func( $field['callback'], $args );
					continue;
				}

				$description       = $args['description'] ?? '';
				$tooltip           = $args['tooltip'] ?? '';
				$inline_toggle     = ! empty( $args['inline_toggle'] );
				$custom_attributes = $args['custom_attributes'] ?? array();

				// The row itself displays the description on the left. Strip it
				// from the args passed to the field callback, otherwise fields
				// like select_with_locked_options() print it a second time
				// inside the control column on the right.
				unset( $args['description'], $args['tooltip'], $args['custom_attributes'] );

				$this->render_custom_field_row(
					$field['title'],
					$description,
					function() use ( $field, $args ) {
						call_user_func( $field['callback'], $args );
					},
					$inline_toggle,
					$inline_toggle,
					$tooltip,
					$custom_attributes
				);
			}
		}

		/**
		 * Render a Custom-section field row: label + description stacked on
		 * the left, the control on the right. Matches the design's layout,
		 * distinct from render_subpanel_field_row()'s single-line label shape
		 * used by Cart Display Modes' sub-panels.
		 *
		 * @param  string   $label             The translated field label.
		 * @param  string   $description       The translated description, shown under the label.
		 * @param  callable $callback          Callable that renders the field control HTML.
		 * @param  bool     $show_info_icon    Whether to render an info icon next to the label, tooltipped with $tooltip.
		 * @param  bool     $inline_toggle     Whether the control renders inline with the label (e.g. a toggle switch) instead of in its own column.
		 * @param  string   $tooltip           The translated tooltip text for the info icon. Ignored if $show_info_icon is false.
		 * @param  array    $custom_attributes Extra data-* attributes for the row, e.g. conditional-visibility hooks. See normalize_custom_attributes().
		 * @return void
		 */
		protected function render_custom_field_row( string $label, string $description, callable $callback, bool $show_info_icon = false, bool $inline_toggle = false, string $tooltip = '', array $custom_attributes = array() ): void {
			printf(
				'<div class="wpmenucart-custom-field-row" %s>',
				$this->normalize_custom_attributes( $custom_attributes )
			);

			echo '<div class="wpmenucart-custom-field-row__info">';
			if ( $inline_toggle ) {
				$callback();
			}
			echo '<span class="wpmenucart-custom-field-row__label-row">';
			printf( '<strong class="wpmenucart-custom-field-row__label">%s</strong>', esc_html( $label ) );
			if ( $show_info_icon && $tooltip ) {
				printf( '<span class="wpmenucart-custom-field-row__info-icon" title="%s" aria-hidden="true">', esc_attr( $tooltip ) );
				$this->render_svg( 'info.svg' );
				echo '</span>';
			}
			echo '</span>';
			if ( $description ) {
				printf( '<span class="wpmenucart-custom-field-row__description">%s</span>', esc_html( $description ) );
			}
			echo '</div>';

			if ( ! $inline_toggle ) {
				echo '<div class="wpmenucart-custom-field-row__control">';
				$callback();
				echo '</div>';
			}

			echo '</div>';
		}

		/**
		 * Render a single cart mode selection card.
		 *
		 * @param  array $args {
		 *     Card arguments.
		 *
		 *     @type string $mode             The mode value for this card (e.g. 'sidebar').
		 *     @type string $name             Translated display name.
		 *     @type string $description      Translated description.
		 *     @type string $current_value    Currently saved mode value for this context.
		 *     @type string $option_key       The key within the option array (e.g. 'desktop_cart_mode').
		 *     @type bool   $disabled         Whether the card should be disabled entirely.
		 *     @type bool   $pro              Whether disabled is due to being a Pro-only mode.
		 *     @type string $disabled_tooltip Optional tooltip for a non-Pro disabled reason.
		 * }
		 * @return void
		 */
		protected function render_mode_card( array $args ): void {
			extract( $args );

			$is_selected       = ( $current_value === $mode );
			$has_mode_conflict = WPO_Menu_Cart()->conflict_detector->mode_has_conflict( $mode );

			$card_classes = array( 'wpmenucart-mode-card' );

			if ( $is_selected ) {
				$card_classes[] = 'wpmenucart-mode-card--selected';
			}
			if ( $disabled ) {
				$card_classes[] = 'wpmenucart-mode-card--disabled';
			}
			if ( $has_mode_conflict ) {
				$card_classes[] = 'wpmenucart-mode-card--has-conflict';
			}

			$show_plain_tooltip = $disabled && empty( $pro ) && ! empty( $disabled_tooltip );

			printf(
				'<label class="%s" data-mode="%s"%s>',
				esc_attr( implode( ' ', $card_classes ) ),
				esc_attr( $mode ),
				$show_plain_tooltip ? ' title="' . esc_attr( $disabled_tooltip ) . '"' : ''
			);

			printf(
				'<input type="radio" name="%s" value="%s"%s%s />',
				esc_attr( sprintf( '%s[%s]', WpMenuCart_Settings::OPTION_NAME, $option_key ) ),
				esc_attr( $mode ),
				checked( $current_value, $mode, false ),
				$disabled ? ' disabled' : ''
			);

			echo '<div class="wpmenucart-mode-card__checkmark">';
			$this->render_svg( 'checkmark.svg' );
			echo '</div>';

			if ( $has_mode_conflict ) {
				echo '<div class="wpmenucart-mode-card__warning-badge" title="' . esc_attr__( 'Theme conflict detected.', 'wp-menu-cart' ) . '">';
				$this->render_svg( 'conflict.svg' );
				echo '</div>';
			} elseif ( ! empty( $pro ) ) {
				echo '<div class="wpmenucart-mode-card__pro-badge" title="' . esc_attr__( 'Requires Menu Cart Pro.', 'wp-menu-cart' ) . '">';
				$this->render_svg( 'pro-badge.svg' );
				echo '</div>';
			}

			echo '<div class="wpmenucart-mode-card__icon" aria-hidden="true">';
			$this->render_svg( 'template-icons/' . $mode . '.svg' );
			echo '</div>';

			echo '<div class="wpmenucart-mode-card__content">';
			printf( '<strong class="wpmenucart-mode-card__name">%s</strong>', esc_html( $name ) );
			printf( '<span class="wpmenucart-mode-card__description">%s</span>', esc_html( $description ) );
			echo '</div>';

			if ( $disabled && ! empty( $pro ) ) {
				echo wp_kses( $this->pro_overlay( 'cartmode' . $mode ), $this->get_allowed_html() );
			}

			echo '</label>';
		}

		/**
		 * Render the theme conflict warning banner only if a conflict exists.
		 *
		 * @return void
		 */
		protected function maybe_render_conflict_banner(): void {
			$conflict_detector = WPO_Menu_Cart()->conflict_detector;

			if ( ! $conflict_detector->has_conflict() ) {
				return;
			}

			echo '<div class="wpmenucart-conflict-banner">';

			echo '<div class="wpmenucart-conflict-banner__icon" aria-hidden="true">';
			$this->render_svg( 'conflict-alt.svg' );
			echo '</div>';

			echo '<div class="wpmenucart-conflict-banner__body">';

			printf(
				'<strong class="wpmenucart-conflict-banner__title">%s</strong>',
				esc_html__( 'Theme Conflict Detected', 'wp-menu-cart' )
			);

			printf(
				'<p class="wpmenucart-conflict-banner__message">%s</p>',
				wp_kses( $conflict_detector->get_conflict_message(), array( 'strong' => array() ) )
			);

			echo '</div>';
			echo '</div>';
		}

		/**
		 * Render a Cart Display Modes-style sub-panel.
		 *
		 * @param  string   $context       'desktop', 'mobile', or another data-context value.
		 * @param  string   $mode          The mode value this sub-panel belongs to, e.g. 'sidebar'.
		 * @param  string   $title         The sub-panel's header title. Empty string skips the header entirely.
		 * @param  callable $render_fields Callable that renders the field rows inside .wpmenucart-subpanel__fields.
		 * @param  string   $option_key    The option array key holding the controlling value. Defaults to '{$context}_cart_mode'.
		 * @param  string   $fields_class  Optional extra class on .wpmenucart-subpanel__fields, e.g. '--grid'.
		 * @return void
		 */
		protected function render_mode_subpanel( string $context, string $mode, string $title, callable $render_fields, string $option_key = '', string $fields_class = '' ): void {
			$option_key = $option_key ?: $context . '_cart_mode';

			$subpanel_attributes = $this->normalize_custom_attributes( array(
				'data-show_for_option_name'   => WpMenuCart_Settings::OPTION_NAME . '[' . $option_key . ']',
				'data-show_for_option_values' => wp_json_encode( array( $mode ) ),
			) );

			printf(
				'<div class="wpmenucart-subpanel" data-context="%s" data-mode="%s" %s>',
				esc_attr( $context ),
				esc_attr( $mode ),
				$subpanel_attributes
			);

			if ( $title ) {
				$badge_label = in_array( $context, array( 'desktop', 'mobile' ), true )
					? ( 'desktop' === $context ? __( 'Desktop only', 'wp-menu-cart' ) : __( 'Mobile only', 'wp-menu-cart' ) )
					: '';

				$this->render_subpanel_header( $title, $context, $badge_label );
			}

			printf( '<div class="wpmenucart-subpanel__fields%s">', $fields_class ? ' ' . esc_attr( $fields_class ) : '' );
			$render_fields();
			echo '</div>';

			echo '</div>';
		}

		/**
		 * Render the Sidebar Slide-out Settings sub-panel.
		 *
		 * @param  string $context 'desktop' or 'mobile'.
		 * @return void
		 */
		public function render_sidebar_subpanel( string $context ): void {
			$this->render_mode_subpanel(
				$context,
				'sidebar',
				__( 'Sidebar Slide-out Settings', 'wp-menu-cart' ),
				function() use ( $context ) {
					$this->range_slider( array(
						'option_name' => WpMenuCart_Settings::OPTION_NAME,
						'id'          => $context . '_sidebar_width',
						'label'       => __( 'Sidebar width', 'wp-menu-cart' ),
						'min'         => 320,
						'max'         => 500,
						'step'        => 10,
						'unit'        => 'px',
					) );

					$this->range_slider( array(
						'option_name' => WpMenuCart_Settings::OPTION_NAME,
						'id'          => $context . '_overlay_opacity',
						'label'       => __( 'Overlay opacity', 'wp-menu-cart' ),
						'min'         => 10,
						'max'         => 100,
						'step'        => 5,
						'unit'        => '%',
					) );
				}
			);
		}

		/**
		 * Render the header row for a sub-panel.
		 *
		 * @param  string $title       The translated sub-panel title.
		 * @param  string $context     Used only for the badge's modifier class, e.g. 'desktop'/'mobile'. Irrelevant when $badge_label is ''.
		 * @param  string $badge_label Translated badge text, e.g. 'Desktop only'. Empty string omits the badge entirely.
		 * @return void
		 */
		protected function render_subpanel_header( string $title, string $context, string $badge_label = '' ): void {
			echo '<div class="wpmenucart-subpanel__header">';
			printf( '<h4 class="wpmenucart-subpanel__title">%s</h4>', esc_html( $title ) );

			if ( $badge_label ) {
				printf(
					'<span class="wpmenucart-subpanel__context-badge wpmenucart-subpanel__context-badge--%s">%s</span>',
					esc_attr( $context ),
					esc_html( $badge_label )
				);
			}

			echo '</div>';
		}

		/**
		 * Render a label/field row inside a sub-panel.
		 *
		 * @param  string      $label       The translated field label.
		 * @param  callable    $callback    Callable that renders the field input HTML.
		 * @param  string|null $description The description of the field that goes outside the __field div.
		 * @return void
		 */
		protected function render_subpanel_field_row( string $label, callable $callback, $description = null ): void {
			echo '<div class="wpmenucart-subpanel__row">';
			printf( '<span class="wpmenucart-subpanel__label">%s</span>', esc_html( $label ) );
			echo '<div class="wpmenucart-subpanel__field">';
			$callback();
			echo '</div>';
			if ( $description ) {
				printf( '<p class="description">%s</p>', esc_html( $description ) );
			}
			echo '</div>';
		}

		/**
		 * Get the ordered list of cart display mode options.
		 *
		 * @return array[] Array of mode definition arrays with keys: value, name, description.
		 */
		public function get_cart_mode_options(): array {
			return apply_filters( 'wpo_wpmenucart_cart_mode_options', array(
				array(
					'value'       => 'none',
					'name'        => __( 'None', 'wp-menu-cart' ),
					'description' => __( 'Don\'t use a template', 'wp-menu-cart' ),
				),
				array(
					'value'       => 'sidebar',
					'name'        => __( 'Sidebar Slide-out', 'wp-menu-cart' ),
					'description' => __( 'Full cart slides in from the side', 'wp-menu-cart' ),
				),
				array(
					'value'       => 'floating',
					'name'        => __( 'Floating Cart', 'wp-menu-cart' ),
					'description' => __( 'Fixed floating button', 'wp-menu-cart' ),
					'disabled'    => true,
					'pro'         => true,
				),
				array(
					'value'       => 'dropdown',
					'name'        => __( 'Dropdown Preview', 'wp-menu-cart' ),
					'description' => __( 'Hover/click shows cart contents', 'wp-menu-cart' ),
					'disabled'    => true,
					'pro'         => true,
				),
			) );
		}

		/**
		 * Get the ordered list of Icon Style template options.
		 *
		 * @return array[] Array of template definition arrays with keys: value, name, description.
		 */
		public function get_icon_style_options(): array {
			return apply_filters( 'wpo_wpmenucart_icon_style_options', array(
				array(
					'value'       => 'cart_button',
					'name'        => __( 'Cart button', 'wp-menu-cart' ),
					'description' => __( 'Shape and total options', 'wp-menu-cart' ),
					'disabled'    => true,
					'pro'         => true,
				),
				array(
					'value'       => 'icon_badge',
					'name'        => __( 'Icon badge', 'wp-menu-cart' ),
					'description' => __( 'Count overlays the icon', 'wp-menu-cart' ),
					'disabled'    => true,
					'pro'         => true,
				),
			) );
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
		 * Select element with per-option Pro locking.
		 *
		 * Renders a native select where some options are visible but locked:
		 * they get the disabled attribute and a translated "(Pro)" suffix,
		 * the same discoverable-but-locked treatment the template cards use.
		 *
		 * @param  array $args {
		 *     @type string $option_name
		 *     @type string $id
		 *     @type array  $options        value => label
		 *     @type array  $locked_options Optional. Option values that are Pro-locked.
		 *     @type string $default
		 *     @type string $description
		 * }
		 * @return void
		 */
		public function select_with_locked_options( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			$locked_options = $locked_options ?? array();

			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $setting_name ) );

			foreach ( $options as $key => $label ) {
				$is_locked = in_array( (string) $key, $locked_options, true );

				if ( $is_locked ) {
					/* translators: %s: option label */
					$label = sprintf( __( '%s (Pro)', 'wp-menu-cart' ), $label );
				}

				printf(
					'<option value="%s"%s%s>%s</option>',
					esc_attr( $key ),
					selected( $current, $key, false ),
					$is_locked ? ' disabled' : '',
					esc_html( $label )
				);
			}

			echo '</select>';

			if ( isset( $custom ) ) {
				$panel_attributes = $this->normalize_custom_attributes( array(
					'data-show_for_option_name'   => $setting_name,
					'data-show_for_option_values' => wp_json_encode( array( 'custom' ) ),
					'data-keep_current_value'     => 'true',
				) );

				printf( '<div class="%1$s_custom wpmenucart-select-custom-panel" %2$s>', esc_attr( $id ), $panel_attributes );

				$custom_callback = apply_filters( 'wpo_wpmenucart_settings_callback', array( $this, $custom['type'] ), $custom['type'] );

				if ( is_callable( $custom_callback ) ) {
					call_user_func( $custom_callback, $custom['args'] );
				}

				echo '</div>';
			}

			if ( isset( $description ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}
		}

		/**
		 * Locked color-swatch preview.
		 *
		 * @param  array $args
		 * @return void
		 */
		public function color_swatch_callback( array $args ): void {
			$pro      = $args['pro'] ?? false;
			$disabled = ! empty( $args['disabled'] );

			extract( $this->normalize_settings_args( $args ) );

			$swatch_color = $current ? $current : '#3858e9';

			$before = '';

			if ( $disabled && $pro ) {
				$before = '<div class="pro-setting-wrapper">';
			}

			$before .= sprintf( '<label class="wpmenucart-color-swatch-field" for="%s">', esc_attr( $id ) );
			$before .= sprintf(
				'<input type="color" id="%1$s" name="%2$s" value="%3$s" class="wpmenucart-color-swatch-field__input"%4$s />',
				esc_attr( $id ),
				esc_attr( $setting_name ),
				esc_attr( $swatch_color ),
				( $disabled && $pro ) ? ' disabled' : ''
			);
			$before .= '<span class="wpmenucart-color-swatch-field__chevron" aria-hidden="true"></span>';
			$before .= '</label>'; // .wpmenucart-color-swatch-field

			if ( isset( $description ) ) {
				$before .= sprintf( '<p class="description">%s</p>', wp_kses_post( $description ) );
			}

			if ( $disabled && $pro ) {
				$before .= $this->pro_overlay( 'menucartflyout' );
				$before .= '</div>'; // .pro-setting-wrapper
			}

			echo wp_kses( $before, $this->get_allowed_html() );
		}

		/**
		 * Locked upload dropzone preview for the custom icon field.
		 *
		 * @param  array $args
		 * @return void
		 */
		public function icon_upload_dropzone_callback( array $args ): void {
			$pro      = $args['pro'] ?? false;
			$disabled = ! empty( $args['disabled'] );

			extract( $this->normalize_settings_args( $args ) );

			$before = '';

			if ( $disabled && $pro ) {
				$before = '<div class="pro-setting-wrapper">';
			}

			printf( '<input id="%1$s" name="%2$s" type="hidden" value="%3$s" />', esc_attr( $id ), esc_attr( $setting_name ), esc_attr( $current ) );

			$dropzone_classes = 'wpmenucart-upload-dropzone';
			if ( ! ( $disabled && $pro ) ) {
				$dropzone_classes .= ' wpmenucart-upload-dropzone--active wpo_upload_image_button';
			}

			$before .= sprintf(
				'<span class="%1$s" role="button" tabindex="0"%2$s data-input_id="%3$s" data-uploader_title="%4$s" data-uploader_button_text="%5$s" data-remove_button_text="%6$s" data-mime-types="%7$s" data-extensions="%8$s">',
				esc_attr( $dropzone_classes ),
				( $disabled && $pro ) ? ' aria-disabled="true"' : '',
				esc_attr( $id ),
				esc_attr__( 'Select or upload a custom menu cart icon.', 'wp-menu-cart' ),
				esc_attr__( 'Set image', 'wp-menu-cart' ),
				esc_attr__( 'Remove image', 'wp-menu-cart' ),
				esc_attr( 'image/svg+xml,image/png,image/jpeg,image/gif' ),
				esc_attr( 'svg,png,jpg,jpeg,gif' )
			);

			if ( ! empty( $current ) ) {
				$attachment = wp_get_attachment_image_src( $current, 'thumbnail', false );

				if ( $attachment ) {
					$before .= '<span class="wpmenucart-upload-dropzone__preview-wrap">';

					$before .= sprintf(
						'<img src="%1$s" class="wpmenucart-upload-dropzone__preview" id="img-%2$s" />',
						esc_url( $attachment[0] ),
						esc_attr( $id )
					);

					$before .= sprintf(
						'<span class="wpmenucart-upload-dropzone__remove wpo_remove_image_button" role="button" tabindex="0" data-input_id="%1$s" aria-label="%2$s">&times;</span>',
						esc_attr( $id ),
						esc_attr__( 'Remove image', 'wp-menu-cart' )
					);

					$before .= '</span>'; // .wpmenucart-upload-dropzone__preview-wrap
				}
			}

			$before .= '<span class="wpmenucart-upload-dropzone__icon" aria-hidden="true">';

			echo wp_kses( $before, $this->get_allowed_html() );

			// SVG isn't in get_allowed_html()'s tag list, render directly.
			$this->render_svg( 'upload.svg' );

			$after  = '</span>'; // .wpmenucart-upload-dropzone__icon
			$after .= '<div class="wpmenucart-upload-dropzone__content">';
			$after .= '<span class="wpmenucart-upload-dropzone__content-text">' . esc_html__( 'Click to upload', 'wp-menu-cart' ) . ' ' . esc_html__( 'or drag and drop', 'wp-menu-cart' ) . '</span>';
			$after .= '<span class="wpmenucart-upload-dropzone__content-hint">' . esc_html__( 'SVG, PNG, JPG or GIF (max. 800x400px)', 'wp-menu-cart' ) . '</span>';
			$after .= '</div>'; // .wpmenucart-upload-dropzone__content
			$after .= '</span>'; // .wpmenucart-upload-dropzone

			if ( isset( $args['description'] ) ) {
				$after .= sprintf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
			}

			if ( $disabled && $pro ) {
				$after .= $this->pro_overlay( 'menucartflyout' );
				$after .= '</div>'; // .pro-setting-wrapper
			}

			echo wp_kses( $after, $this->get_allowed_html() );
		}

		/**
		 * Generic toggle switch control, used for the Custom section's own
		 * on/off toggle and for boolean fields inside it (icon_display).
		 *
		 * @param  array $args
		 * @return void
		 */
		public function toggle_switch_callback( array $args ): void {
			extract( $this->normalize_settings_args( $args ) );

			printf(
				'<label class="wpmenucart-toggle-switch"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s /><span class="wpmenucart-toggle-switch__track" aria-hidden="true"></span></label>',
				esc_attr( $id ),
				esc_attr( $setting_name ),
				checked( 1, $current, false )
			);
		}

		/**
		 * The Custom section's own toggle switch control.
		 *
		 * @param  array $args
		 * @return void
		 */
		public function custom_section_toggle_callback( array $args ): void {
			$this->toggle_switch_callback( $args );
		}

		/**
		 * Validate and sanitize settings input, including cart-mode and
		 * sidebar-specific fields.
		 *
		 * @param  array $input Raw input from the settings form.
		 * @return array
		 */
		public function validate( array $input ): array {
			$output = parent::validate( $input );

			if ( empty( $output ) || ! is_array( $output ) ) {
				return $output;
			}

			$option_page = isset( $_POST['option_page'] ) ? sanitize_text_field( wp_unslash( $_POST['option_page'] ) ) : '';

			if ( WpMenuCart_Settings::OPTION_NAME === $option_page ) {
				$page = isset( $_POST['wpo_wpmenucart_settings_page'] ) ? sanitize_text_field( wp_unslash( $_POST['wpo_wpmenucart_settings_page'] ) ) : '';

				$existing = get_option( WpMenuCart_Settings::OPTION_NAME, array() );

				global $wp_settings_fields;

				foreach ( $wp_settings_fields[ $page ] ?? array() as $section_fields ) {
					foreach ( array_keys( $section_fields ) as $field_id ) {
						unset( $existing[ $field_id ] );
					}
				}

				$output = array_merge( $existing, $output );

				if ( WpMenuCart_Settings::PAGE_ICON_STYLE === $page ) {
					$output['icon_style_custom_enabled'] = isset( $input['icon_style_custom_enabled'] ) ? '1' : '0';
				}
			}

			$is_main_settings = isset( $output['shop_plugin'] )
				|| isset( $output['items_display'] )
				|| isset( $output['desktop_cart_mode'] )
				|| isset( $output['mobile_cart_mode'] )
				|| isset( $output['icon_style_template'] )
				|| isset( $output['icon_style_custom_enabled'] );

			if ( ! $is_main_settings ) {
				return $output;
			}

			$allowed_modes = array_column( $this->get_cart_mode_options(), 'value' );

			if ( isset( $output['icon_style_template'] ) ) {
				$output['icon_style_template'] = $this->resolve_available_icon_style( $output['icon_style_template'] );
			}

			// Only the default cart icon is available in free. Pro extends this list.
			$allowed_cart_icons = apply_filters( 'wpo_wpmenucart_allowed_cart_icons', array( '0' ) );

			if ( isset( $output['cart_icon'] ) && ! in_array( (string) $output['cart_icon'], $allowed_cart_icons, true ) ) {
				$output['cart_icon'] = '0';
			}

			// The Custom placeholder option for items_display requires Pro.
			if ( isset( $output['items_display'] ) && 'custom' === $output['items_display']
				&& ! apply_filters( 'wpo_wpmenucart_items_display_custom_unlocked', false ) ) {
				$output['items_display'] = '3';
			}

			if ( isset( $output['desktop_cart_mode'] ) ) {
				$output['desktop_cart_mode'] = in_array( $output['desktop_cart_mode'], $allowed_modes, true )
					? $output['desktop_cart_mode']
					: 'none';
			}

			if ( isset( $output['mobile_cart_mode'] ) ) {
				$output['mobile_cart_mode'] = in_array( $output['mobile_cart_mode'], $allowed_modes, true )
					? $output['mobile_cart_mode']
					: 'none';
			}

			foreach ( array( 'desktop', 'mobile' ) as $context ) {
				if ( isset( $output[ $context . '_sidebar_width' ] ) ) {
					$output[ $context . '_sidebar_width' ] = max( 320, min( 500, absint( $output[ $context . '_sidebar_width' ] ) ) );
				}

				if ( isset( $output[ $context . '_overlay_opacity' ] ) ) {
					$output[ $context . '_overlay_opacity' ] = max( 10, min( 100, absint( $output[ $context . '_overlay_opacity' ] ) ) );
				}
			}

			return $output;
		}

		/**
		 * Output an SVG file from the plugin's images directory.
		 *
		 * @param  string $filename SVG filename without path, e.g. 'checkmark.svg'.
		 * @return void
		 */
		protected function render_svg( string $filename ): void {
			echo $this->get_svg( $filename ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG loaded from plugin directory.
		}

		/**
		 * Get an SVG file's contents from the plugin's images directory.
		 *
		 * Caches loaded SVGs in memory to avoid repeated filesystem reads.
		 *
		 * @param  string $filename SVG filename without path, e.g. 'checkmark.svg'.
		 * @return string
		 */
		public function get_svg( string $filename ): string {
			static $cache = array();

			if ( ! isset( $cache[ $filename ] ) ) {
				$path               = WPO_Menu_Cart()->plugin_path() . '/assets/images/' . $filename;
				$cache[ $filename ] = file_exists( $path ) ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
			}

			return $cache[ $filename ];
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
				'label'  => array( 'for' => array(), 'class' => array() ),
				'table'  => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'tr'     => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'td'     => array( 'id' => array(), 'class' => array(), 'style' => array(), 'colspan' => array(), 'rowspan' => array(), 'align' => array() ),
				'a'      => array( 'href' => array(), 'title' => array(), 'id' => array(), 'class' => array(), 'style' => array(), 'target' => array(), 'rel' => array() ),
				'select' => array( 'id' => array(), 'name' => array(), 'class' => array(), 'disabled' => array() ),
				'option' => array( 'value' => array(), 'selected' => array(), 'disabled' => array() ),
				'div'    => array( 'id' => array(), 'class' => array(), 'style' => array(), 'data-show_for_option_name' => array(), 'data-show_for_option_values' => array(), 'data-keep_current_value' => array() ),
				'span'   => array( 'id' => array(), 'class' => array(), 'style' => array(), 'role' => array(), 'tabindex' => array(), 'aria-disabled' => array(), 'aria-hidden' => array(), 'aria-label' => array(), 'data-input_id' => array(), 'data-uploader_title' => array(), 'data-uploader_button_text' => array(), 'data-remove_button_text' => array(), 'data-mime-types' => array(), 'data-extensions' => array() ),
				'p'      => array( 'id' => array(), 'class' => array(), 'style' => array() ),
				'i'      => array( 'class' => array() ),
				'img'    => array( 'id' => array(), 'class' => array(), 'src' => array() ),
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
