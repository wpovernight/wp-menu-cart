<?php
if ( ! class_exists( 'WPMenuCart_eShop' ) ) {
	class WPMenuCart_eShop {     
	
	    /**
	     * Construct.
	     */
	    public function __construct() {
			global $wpdb,$blog_id,$eshopoptions, $post;
		}
	
		public function menu_item() {
			global $wpdb,$blog_id,$eshopoptions, $post;
			$eshopsize=0;
			$eshopqty=0;
			$thetotal=0;
			$eshoptotal=0;
			if(isset($_SESSION['eshopcart'.$blog_id])) {
				$eshopcartarray = $_SESSION['eshopcart'.$blog_id];
				$currsymbol = $eshopoptions['currency_symbol'];
			
				
			}
			if(isset($_SESSION['eshopcart'.$blog_id])) {
				$eshopsize=sizeof($_SESSION['eshopcart'.$blog_id]);
					foreach($_SESSION['eshopcart'.$blog_id] as $eshopdo=>$eshopwop){
						$eshopqty+=$eshopwop['qty'];
					
				}
			}
			if(isset($_SESSION['final_price'.$blog_id]) && isset($_SESSION['eshopcart'.$blog_id])) {
				//should be working but there seems to be an eShop bug in storing the final_price value (doesn't multiply with quantity)
				//$thetotal=$_SESSION['final_price'.$blog_id]; 
				$eshopcart = $_SESSION['eshopcart'.$blog_id];
				$thetotal = 0;
				foreach ($eshopcart as $eshopcart_item) {
					$thetotal += $eshopcart_item['qty'] * $eshopcart_item['price'];
				}
				
				$eshoptotal=sprintf( __('%1$s%2$s','eshop'), $currsymbol, number_format_i18n($thetotal,__('2','eshop')));
			}
			$menu_item = array(
				'cart_url'				=> get_permalink($eshopoptions['cart']),
				'shop_page_url'			=> get_home_url(),
				'cart_contents_count'	=> $eshopqty,
				'cart_total'			=> $eshoptotal,
			);

			return $menu_item;		
		}
	}
}