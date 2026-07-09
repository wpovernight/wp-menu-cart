<?php
/**
 * Detects conflicts between WP Menu Cart cart display modes
 * and WooCommerce's native block-based mini-cart drawer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WpMenuCart_Conflict_Detector' ) ) :

	class WpMenuCart_Conflict_Detector {

		/**
		 * Cart display mode values that conflict with the WooCommerce block cart.
		 *
		 * @var string[]
		 */
		private array $conflicting_modes = array( 'sidebar' );

		/**
		 * Cart display mode values that are recommended when a conflict is found.
		 *
		 * @var string[]
		 */
		private array $recommended_modes = array();

		/**
		 * Cached result of the conflict check to avoid repeated lookups.
		 *
		 * @var bool|null
		 */
		private ?bool $conflict_result = null;

		/**
		 * Check whether a conflict exists between our cart modes and the
		 * WooCommerce block-based mini-cart drawer.
		 *
		 * Result is cached after the first call.
		 *
		 * @return bool True if a conflict is detected.
		 */
		public function has_conflict(): bool {
			if ( null !== $this->conflict_result ) {
				return $this->conflict_result;
			}

			$this->conflict_result = WPO_Menu_Cart()->is_block_theme()
				&& $this->is_woocommerce_active()
				&& $this->is_wc_mini_cart_active();

			/**
             * Filters the conflict detection result.
             *
             * Allows third-party code to override conflict detection if needed,
             * for example to suppress the warning for a known-compatible setup.
             *
             * @param bool $conflict_result Whether a conflict was detected.
             */
			$this->conflict_result = (bool) apply_filters( 'wpo_wpmenucart_has_theme_conflict', $this->conflict_result );

			return $this->conflict_result;
		}

		/**
		 * Get the list of cart display mode values that are affected by the conflict.
		 *
		 * @return string[] Array of mode values, e.g. ['sidebar'].
		 */
		public function get_conflicting_modes(): array {
			/**
			 * Filters the list of conflicting cart display modes.
			 *
			 * @param string[] $conflicting_modes Array of mode values that conflict.
			 */
			return (array) apply_filters( 'wpo_wpmenucart_conflicting_modes', $this->conflicting_modes );
		}

		/**
		 * Get the list of cart display mode values that are recommended when a conflict is found.
		 *
		 * @return string[] Array of mode values
		 */
		public function get_recommended_modes(): array {
			/**
			 * Filters the list of recommended cart display modes.
			 *
			 * @param string[] $recommended_modes Array of mode values that conflict.
			 */
			return (array) apply_filters( 'wpo_wpmenucart_conflict_recommended_modes', $this->recommended_modes );
		}

		/**
		 * Check whether a specific cart display mode is affected by the conflict.
		 *
		 * @param string $mode The cart display mode value to check.
		 * @return bool True if this mode conflicts.
		 */
		public function mode_has_conflict( string $mode ): bool {
			if ( ! $this->has_conflict() ) {
				return false;
			}

			return in_array( $mode, $this->get_conflicting_modes(), true );
		}

		/**
		 * Get the conflict warning message for display in the settings banner.
		 *
		 * @return string The translated conflict message.
		 */
		public function get_conflict_message(): string {
			$mode_names = array_column( WPO_Menu_Cart()->settings->callbacks->get_cart_mode_options(), 'name', 'value' );

			$conflicting_labels = $this->get_mode_labels( $this->get_conflicting_modes(), $mode_names );

			$message = sprintf(
				/* translators: 1. opening strong tag, 2. closing strong tag, 3. comma-separated list of affected mode names */
				esc_html__(
					'Your active theme is a block theme and WooCommerce\'s %1$sblock-based mini-cart drawer%2$s is active. Enabling %3$s may result in two cart panels opening simultaneously.',
					'wp-menu-cart'
				),
				'<strong>',
				'</strong>',
				$conflicting_labels
			);

			$recommended_labels = $this->get_mode_labels( $this->get_recommended_modes(), $mode_names );

			if ( '' !== $recommended_labels ) {
				$message .= ' ' . sprintf(
					/* translators: %s: comma-separated list of recommended mode names */
					esc_html__( 'Consider using %s instead.', 'wp-menu-cart' ),
					$recommended_labels
				);
			}

			return $message;
		}

		/**
		 * Build a human-readable, comma-and-"and"-joined list of mode names
		 * for a set of mode values.
		 *
		 * @param  string[] $mode_values Mode values to look up, e.g. ['sidebar'].
		 * @param  array    $mode_names  Map of mode value => translated name.
		 * @return string                Empty string if no valid modes found.
		 */
		private function get_mode_labels( array $mode_values, array $mode_names ): string {
			$labels = array();

			foreach ( $mode_values as $mode_value ) {
				if ( isset( $mode_names[ $mode_value ] ) ) {
					$labels[] = $mode_names[ $mode_value ];
				}
			}

			if ( empty( $labels ) ) {
				return '';
			}

			if ( 1 === count( $labels ) ) {
				return $labels[0];
			}

			$last = array_pop( $labels );

			/* translators: 1. comma-separated list of items, 2. final item joined with "and" */
			return sprintf( esc_html__( '%1$s and %2$s', 'wp-menu-cart' ), implode( ', ', $labels ), $last );
		}

		/**
		 * Check whether WooCommerce is active.
		 *
		 * @return bool
		 */
		private function is_woocommerce_active(): bool {
			return class_exists( 'WooCommerce' );
		}

		/**
		 * Check whether WooCommerce's mini-cart is active.
		 *
		 * @return bool
		 */
		private function is_wc_mini_cart_active(): bool {
			// If WooCommerce block hooks are disabled entirely, mini-cart won't be injected.
			$hooked_blocks_version = get_option( 'woocommerce_hooked_blocks_version' );
			if ( ! $hooked_blocks_version || 'no' === $hooked_blocks_version ) {
				return false;
			}

			$header = get_block_template( get_stylesheet() . '//header', 'wp_template_part' );

			if ( null === $header || empty( $header->content ) ) {
				return false;
			}

			// Mini-cart is present if the block comment exists in the header content.
			if ( false !== strpos( $header->content, '<!-- wp:woocommerce/mini-cart' ) ) {
				return true;
			}

			// If not explicitly present, WooCommerce will auto-inject it via block hooks
			// unless it has been explicitly removed (marked as ignored).
			$has_navigation = false !== strpos( $header->content, '<!-- wp:navigation' );
			$blocks         = parse_blocks( $header->content );
			$is_ignored     = $this->is_mini_cart_ignored_in_blocks( $blocks );

			return $has_navigation && ! $is_ignored;
		}

		/**
		 * Recursively check whether the WooCommerce mini-cart block has been explicitly
		 * removed from a set of blocks via the ignoredHookedBlocks metadata.
		 *
		 * @param  array[] $blocks Parsed block array from parse_blocks().
		 * @return bool
		 */
		private function is_mini_cart_ignored_in_blocks( array $blocks ): bool {
			foreach ( $blocks as $block ) {
				$ignored = $block['attrs']['metadata']['ignoredHookedBlocks'] ?? array();
				if ( in_array( 'woocommerce/mini-cart', $ignored, true ) ) {
					return true;
				}

				if ( ! empty( $block['innerBlocks'] ) && $this->is_mini_cart_ignored_in_blocks( $block['innerBlocks'] ) ) {
					return true;
				}
			}

			return false;
		}

	}

endif;
