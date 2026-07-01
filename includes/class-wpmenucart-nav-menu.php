<?php
/**
 * Handles Menu Cart as a real nav menu item in Appearance > Menus.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WpMenuCart_Nav_Menu' ) ) :

	class WpMenuCart_Nav_Menu {

		/**
		 * Pending cart render data keyed by spl_object_hash of the menu args.
		 *
		 * Keyed per render so multiple menus in the same request don't overwrite each other.
		 *
		 * @var array
		 */
		protected $pending_renders = array();

		/**
		 * Menu term IDs that have already had the cart rendered this request.
		 *
		 * Used to enforce the one-menu limit in free and prevent double-renders
		 * when the same menu is assigned to multiple theme locations.
		 *
		 * @var array
		 */
		protected $rendered_menus = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'load-nav-menus.php', array( $this, 'register_nav_menu_metabox' ) );
			add_filter( 'customize_nav_menu_available_item_types', array( $this, 'register_customizer_item_type' ) );
			add_filter( 'customize_nav_menu_available_items', array( $this, 'register_customizer_item' ), 10, 4 );
			add_action( 'wp_update_nav_menu_item', array( $this, 'maybe_fix_cart_item_title' ), 10, 3 );
			add_filter( 'wp_nav_menu_objects', array( $this, 'handle_nav_menu_objects' ), 10, 2 );
		}

		/**
		 * Migrate existing menu_slugs setting to a real nav menu item.
		 *
		 * Runs once on upgrade. If a menu slug is configured in plugin settings,
		 * creates a real nav menu item in that menu if one doesn't already exist.
		 *
		 * @return void
		 */
		public function maybe_migrate_menu_slugs(): void {
			if ( get_option( 'wpo_wpmenucart_nav_menu_migrated' ) ) {
				return;
			}

			// Read from the new option key. Fall back to the legacy key in case
			// maybe_migrate_options() hasn't run yet (e.g. very first load after update).
			$main_settings = get_option( 'wpo_wpmenucart_main_settings', array() );
			$menu_slugs    = ! empty( $main_settings['menu_slugs'] ) ? $main_settings['menu_slugs'] : array();

			if ( empty( $menu_slugs ) ) {
				$legacy     = get_option( 'wpmenucart', array() );
				$menu_slugs = ! empty( $legacy['menu_slugs'] ) ? $legacy['menu_slugs'] : array();
			}

			foreach ( $menu_slugs as $menu_slug ) {
				if ( empty( $menu_slug ) || '0' === $menu_slug ) {
					continue;
				}

				$menu = wp_get_nav_menu_object( $menu_slug );

				if ( ! $menu ) {
					continue;
				}

				// Skip if a cart item already exists in this menu.
				if ( $this->menu_has_cart_item( $menu_slug ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu->term_id,
					0,
					array(
						'menu-item-title'    => __( 'Menu Cart', 'wp-menu-cart' ),
						'menu-item-url'      => '#wpmenucart',
						'menu-item-type'     => 'wpmenucart',
						'menu-item-object'   => 'wpmenucart',
						'menu-item-status'   => 'publish',
						'menu-item-position' => apply_filters( 'wpmenucart_prepend_menu_item', false ) ? 1 : 0,
					)
				);
			}

			update_option( 'wpo_wpmenucart_nav_menu_migrated', true );
		}

		/**
		 * Register the Menu Cart metabox on the Appearance > Menus screen.
		 *
		 * @return void
		 */
		public function register_nav_menu_metabox(): void {
			add_meta_box(
				'wpmenucart-nav-menu-metabox',
				__( 'Menu Cart', 'wp-menu-cart' ),
				array( $this, 'render_metabox' ),
				'nav-menus',
				'side',
				'default'
			);
		}

		/**
		 * Render the Menu Cart metabox content.
		 *
		 * @return void
		 */
		public function render_metabox(): void {
			global $nav_menu_selected_id;

			$item   = $this->get_item_object();
			$walker = new Walker_Nav_Menu_Checklist();
			?>
			<div id="wpmenucart" class="categorydiv">
				<div id="tabs-panel-wpmenucart-all" class="tabs-panel tabs-panel-active">
					<ul id="wpmenucart-checklist" class="categorychecklist form-no-clear">
						<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', array( $item ) ), 0, (object) array( 'walker' => $walker ) ); ?>
					</ul>
				</div>
				<p class="button-controls wp-clearfix" data-items-type="wpmenucart">
					<span class="add-to-menu">
						<input
							type="submit"
							<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?>
							class="button-secondary submit-add-to-menu right"
							value="<?php esc_attr_e( 'Add to Menu', 'wp-menu-cart' ); ?>"
							name="add-wpmenucart-menu-item"
							id="submit-wpmenucart"
						/>
						<span class="spinner"></span>
					</span>
				</p>
			</div>
			<?php
		}

		/**
		 * Register the Menu Cart item type in the Customizer's available items panel.
		 *
		 * @param  array $item_types Registered item types.
		 *
		 * @return array
		 */
		public function register_customizer_item_type( array $item_types ): array {
			$item_types[] = array(
				'title'      => __( 'Menu Cart', 'wp-menu-cart' ),
				'type_label' => __( 'Custom Link' ),
				'type'       => 'wpmenucart',
				'object'     => 'wpmenucart',
			);

			return $item_types;
		}

		/**
		 * Provide the Menu Cart item in the Customizer's available items panel.
		 *
		 * Only fires when the Customizer requests items for our object type.
		 *
		 * @param  array  $items       Available items.
		 * @param  string $object_type The requested object type.
		 * @param  string $object_name The requested object name.
		 * @param  int    $page        The current page number.
		 *
		 * @return array
		 */
		public function register_customizer_item( array $items, string $object_type, string $object_name, int $page ): array {
			if ( 'wpmenucart' !== $object_type || 'wpmenucart' !== $object_name ) {
				return $items;
			}

			$items[] = array(
				'id'             => 'wpmenucart',
				'title'          => __( 'Menu Cart', 'wp-menu-cart' ),
				'original_title' => __( 'Menu Cart', 'wp-menu-cart' ),
				'type'           => 'wpmenucart',
				'type_label'     => __( 'Custom Link' ),
				'object'         => 'wpmenucart',
				'object_id'      => 0,
				'url'            => '#wpmenucart',
			);

			return $items;
		}

		/**
		 * Ensure Menu Cart nav menu items always have the correct post_title and URL saved.
		 *
		 * The Customizer reads the item label from post_title in the DB. If empty it shows
		 * "(no label)". It also doesn't save _menu_item_url for custom item types, so we
		 * fix both here after save.
		 *
		 * @param  int   $menu_id         ID of the menu.
		 * @param  int   $menu_item_db_id ID of the saved menu item post.
		 * @param  array $args            The menu item data that was saved.
		 *
		 * @return void
		 */
		public function maybe_fix_cart_item_title( int $menu_id, int $menu_item_db_id, array $args ): void {
			if ( empty( $args['menu-item-type'] ) || 'wpmenucart' !== $args['menu-item-type'] ) {
				return;
			}

			if ( empty( $args['menu-item-title'] ) ) {
				wp_update_post( array(
					'ID'         => $menu_item_db_id,
					'post_title' => __( 'Menu Cart', 'wp-menu-cart' ),
				) );
			}

			update_post_meta( $menu_item_db_id, '_menu_item_url', '#wpmenucart' );
		}

		/**
		 * Build the pseudo object the checklist walker expects.
		 *
		 * @return object
		 */
		protected function get_item_object(): object {
			global $_nav_menu_placeholder;

			$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval( $_nav_menu_placeholder ) - 1 : -1;

			$item                   = new stdClass();
			$item->ID               = 0;
			$item->db_id            = 0;
			$item->object_id        = $_nav_menu_placeholder;
			$item->object           = 'wpmenucart';
			$item->type             = 'wpmenucart';
			$item->type_label       = __( 'Custom Link' );
			$item->title            = __( 'Menu Cart', 'wp-menu-cart' );
			$item->post_title       = __( 'Menu Cart', 'wp-menu-cart' );
			$item->url              = '#wpmenucart';
			$item->classes          = array( 'wpmenucart-menu-item' );
			$item->target           = '';
			$item->attr_title       = '';
			$item->description      = '';
			$item->xfn              = '';
			$item->status           = 'publish';
			$item->menu_item_parent = 0;

			return $item;
		}

		/**
		 * Check whether a menu already has a manually placed Menu Cart item.
		 *
		 * @param  string $menu_slug Menu slug.
		 *
		 * @return bool
		 */
		public function menu_has_cart_item( string $menu_slug ): bool {
			$menu = wp_get_nav_menu_object( $menu_slug );

			if ( ! $menu ) {
				return false;
			}

			$items = wp_get_nav_menu_items( $menu->term_id );

			if ( empty( $items ) ) {
				return false;
			}

			foreach ( $items as $item ) {
				if ( 'wpmenucart' === $item->type ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Handle the cart menu item at the objects stage.
		 *
		 * When a cart item is found, stash the data needed for the HTML swap and
		 * register render_native_nav_menu_item to fire on wp_nav_menu_items.
		 * The hide-on-cart/checkout logic is handled downstream via the
		 * hidden-wpmenucart CSS class in generate_menu_item_li(), so we don't
		 * strip the item here.
		 *
		 * @param  array    $menu_items Array of nav menu item objects.
		 * @param  stdClass $args       wp_nav_menu() arguments object.
		 *
		 * @return array
		 */
		public function handle_nav_menu_objects( array $menu_items, stdClass $args ): array {
			$cart_item_id = null;

			foreach ( $menu_items as $item ) {
				if ( 'wpmenucart' === $item->type ) {
					$cart_item_id = $item->ID;
					break;
				}
			}

			if ( ! $cart_item_id ) {
				return $menu_items;
			}

			// If no shop is active, remove the cart item entirely so no bare link is left behind.
			if ( ! isset( WPO_Menu_Cart()->shop ) ) {
				return array_values( array_filter( $menu_items, function( $item ) use ( $cart_item_id ) {
					return $item->ID !== $cart_item_id;
				} ) );
			}

			$menu_id = isset( $args->menu->term_id ) ? $args->menu->term_id : 0;

			// Remove the item entirely when the cart should not render on this page.
			if ( false === WPO_Menu_Cart()->main->should_render_menucart() ) {
				return array_values( array_filter( $menu_items, function( $item ) use ( $cart_item_id ) {
					return $item->ID !== $cart_item_id;
				} ) );
			}

			// Free supports one menu only. If a different menu has already been rendered
			// this request, strip the item so no bare placeholder is left behind.
			// The same menu assigned to multiple theme locations is allowed to render in each.
			if ( ! empty( $this->rendered_menus ) && ! in_array( $menu_id, $this->rendered_menus, true ) ) {
				return array_values( array_filter( $menu_items, function( $item ) use ( $cart_item_id ) {
					return $item->ID !== $cart_item_id;
				} ) );
			}

			$menu_slug = isset( $args->menu->slug ) ? $args->menu->slug : '';
			$menu_id   = isset( $args->menu->term_id ) ? $args->menu->term_id : 0;

			// Stash per-render data keyed by the args object hash so multiple menus
			// in the same request each get their own slot without overwriting each other.
			$this->pending_renders[ spl_object_hash( $args ) ] = array(
				'cart_item_id' => $cart_item_id,
				'menu_slug'    => $menu_slug,
			);

			$this->rendered_menus[] = $menu_id;

			add_filter( 'wp_nav_menu_items', array( $this, 'render_native_nav_menu_item' ), 10, 2 );

			return $menu_items;
		}

		/**
		 * Replace the <li> placeholder with the full cart HTML.
		 *
		 * Registered dynamically by handle_nav_menu_objects and removes itself after
		 * firing so it only processes the render it was registered for.
		 *
		 * @param  string   $items_html The HTML string of menu items.
		 * @param  stdClass $args       wp_nav_menu() arguments object.
		 *
		 * @return string
		 */
		public function render_native_nav_menu_item( string $items_html, stdClass $args ): string {
			$key  = spl_object_hash( $args );
			$data = isset( $this->pending_renders[ $key ] ) ? $this->pending_renders[ $key ] : null;

			if ( ! $data ) {
				return $items_html;
			}

			unset( $this->pending_renders[ $key ] );
			remove_filter( 'wp_nav_menu_items', array( $this, 'render_native_nav_menu_item' ), 10 );

			$common_classes = WPO_Menu_Cart()->main->get_common_li_classes( $items_html );
			$cart_html      = WPO_Menu_Cart()->main->generate_menu_item_li( $common_classes, 'classic' );
			$cart_html      = apply_filters( 'wpmenucart_menu_item_wrapper', $cart_html, $data['menu_slug'], $args );

			// Replace the full <li> for this item using its CSS ID class set by WP core.
			$pattern = '/<li[^>]+\bmenu-item-' . $data['cart_item_id'] . '\b[^>]*>.*?<\/li>/is';

			return preg_replace( $pattern, $cart_html, $items_html );
		}

	}

endif;