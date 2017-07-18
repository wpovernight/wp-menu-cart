<?php
use Jigoshop\Helper\Product;

if (!class_exists('WPMenuCart_Jigoshop')) {
	class WPMenuCart_Jigoshop
	{
		private static $cart;
		
		/**
		 * Construct.
		 */
		public function __construct()
		{
			self::$cart = \Jigoshop\Integration::getCart();
		}
		
		public function menu_item()
		{
			if (class_exists('\Jigoshop\Core')) {
				$total = 0;
				if (!empty(self::$cart->getItems()))
					foreach (self::$cart->getItems() as $cart_item_key) {
						$product = $cart_item_key->getProduct();
						$total += $product->getPrice() * $cart_item_key->getQuantity();
					}
				$total = Product::formatPrice($total);
				$menu_item = array(
					'cart_url' => get_permalink(\Jigoshop\Integration::getOptions()->getPageId('cart')),
					'shop_page_url' => get_permalink(\Jigoshop\Integration::getOptions()->getPageId('shop')),
					'cart_contents_count' => count(self::$cart->getItems()),
					'cart_total' => $total,
				);
				return $menu_item;
			} else {
				if (class_exists('jigoshop')) {
					$total = 0;
					if (!empty(jigoshop_cart::$cart_contents))
						foreach (jigoshop_cart::$cart_contents as $cart_item_key => $values) {
							$product = $values['data'];
							$total += $product->get_price() * $values['quantity'];
						}
					$total = jigoshop_price($total);
					
					$menu_item = array(
						'cart_url' => jigoshop_cart::get_cart_url(),
						'shop_page_url' => get_permalink(jigoshop_get_page_id('shop')),
						'cart_contents_count' => jigoshop_cart::$cart_contents_count,
						'cart_total' => $total,
					);
					
					return $menu_item;
				}
			}
		}
	}
}