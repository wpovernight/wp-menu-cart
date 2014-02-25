<?php
if ( ! class_exists( 'WPMenuCart_WPEC' ) ) {
	class WPMenuCart_WPEC {     
	
	    /**
	     * Construct.
	     */
	    public function __construct() {
			add_action('wpsc_alternate_cart_html', array( &$this, 'wpec_cart_ajax' ) );
	    }
	
		public function menu_item() {
		global $wpsc_cart, $options;
			$menu_item = array(
				'cart_url'				=> esc_url( get_option( 'shopping_cart_url' ) ),
				'shop_page_url'			=> esc_url( get_option( 'product_list_url' ) ),
				'cart_contents_count'	=> wpsc_cart_item_count(),
				'cart_total'			=> wpsc_cart_total_widget( false, false ,false ),
			);
		
			return $menu_item;		
		}
		/**
		* action hook for wp-e-commerce to provide our own AJAX cart updates
		*/
		
                public function wpec_cart_ajax() {
                    /*
			$item_data = $this->menu_item();
			$cart_contents = sprintf(_n('%d item', '%d items', $item_data['cart_contents_count'], 'wpmenucart'), $item_data['cart_contents_count']);
			$cart_total = $item_data['cart_total'];
			$cart_url = $item_data['cart_url'];
			?>
			jQuery("span.cartcontents").html("<?php echo $cart_contents;?>");
			jQuery("span.pricedisplay").html("<?php echo $cart_total;?>");
			jQuery("a.wpmenucart-contents").attr("href", "<?php echo $cart_url;?>");
			<?php
                     * 
                     */
		}
                 
	}
}