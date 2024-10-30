=== WP Menu Cart ===
Contributors: pomegranate, jprummer, alexmigf, yordansoares, kluver, dpeyou
Donate link: https://wpovernight.com/downloads/menu-cart-pro/
Tags: woocommerce, edd, menu, cart, shopping cart
Requires at least: 3.4
Tested up to: 6.6
Requires PHP: 5.3
Stable tag: 2.14.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically displays a shopping cart in your menu bar. Works with WooCommerce and Easy Digital Downloads (EDD)

== Description ==

** Works with WooCommerce and Easy Digital Downloads (EDD) **

This plugin installs a shopping cart button in the navigation bar. The plugin takes less than a minute to setup, 
and includes the following options:

* Display cart icon, or only items/prices.
* Display items only, price only, or both.
* Display always, or only when there are items in the cart.
* Float left, float right, or use your menu's default settings.
* Customize your own CSS

Pro Version Includes:

* A choice of over 10 cart icons
* A fully featured cart details flyout
* Ability to add cart + flyout for unlimited menus
* Ability to add a custom css class
* Automatic updates on any great new features
* Shortcode to display cart *anywhere* on your site
* Quick and thorough support

**Download the Pro version here - https://wpovernight.com/downloads/menu-cart-pro/**

Finally, the cart automatically conforms to your site's styles, leaving you with no extra work.

Compatibility:

* WooCommerce
* Easy Digital Downloads
* Easy Digital Downloads Pro

Translations:

* Brazilian Portuguese
* Danish
* Dutch
* Croatian
* Czech
* English
* French
* German
* Greek
* Hebrew
* Hungarian
* Italian
* Norwegian
* Persian
* Polish
* Portuguese
* Russian
* Spanish[1]
* Swedish
* Turkish
* Vietnamese

[1] WebHostingHub
== Installation ==

Delete any old installations of the plugin. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

Once the plugin is activated navigate to Settings > Menu Cart Setup. Select your options, save and you're ready to go. It's that easy!

== Screenshots ==

1. Shows menu before and after Menu Cart.
2. 3 different display style options to choose from.
3. Shows settings page.

== Frequently Asked Questions ==

== Changelog ==

= 2.14.8 (2024-10-30) =
* New: comply with WP Plugin Check standards
* Translations: Updated translation template (POT)

= 2.14.7 (2024-10-14) =
* Fix: Corrected notices not displaying properly.
* Fix: Load plugin translations later in the `init` hook.
* Tested: Compatible with WooCommerce 9.4.

= 2.14.6 (2024-09-13) =
* New: filter hook `wpmenucart_cart_url`
* Fix: issue with using `wc_get_cart_url()` in Woo version 9.3
* Translations: Updated translation template (POT)
* Tested up to WooCommerce 9.3

= 2.14.5 (2024-08-30) =
* Fix: undefined key errors from settings array
* Translations: Updated translation template (POT)
* Tested up to WooCommerce 9.2

= 2.14.4 (2024-06-26) =
* Tested up to WooCommerce 9.0 & WP 6.6

= 2.14.3 (2024-03-08) =
* Tweak: add explicit labels for the disabling options for the e-commerce plugin and menus
* Fix: deprecated warning with PHP 8.2 and WPCS
* Fix: jumping of a menu on the page load
* Translations: Updated translation template (POT)
* Tested up to WooCommerce 8.7 & WP 6.5

= 2.14.2 (2023-11-08) =
* Tested up to WooCommerce 8.3 & WP 6.4

= 2.14.1 (2023-08-09) =
* New: added compatibility with WC Cart Block
* Fix: `wc-cart-fragments` loading that was removed on WC 7.8
* Tweak: advertise missing Pro version features
* Tested up to WooCommerce 8.0 & WP 6.3

= 2.14.0 (2023-03-28) =
* New: EDD Pro compatibility
* Tested up to WooCommerce 7.5 & WP 6.2

= 2.13.1 (2023-02-02) =
* New: WooCommerce HPOS compatibility (beta)
* Tested up to WooCommerce 7.3

= 2.13.0 (2022-12-06) =
* Tweak: bumps WooCommerce minimum version to 3.0
* Tested up to WooCommerce 7.1 & WP 6.1

= 2.12.1 (2022-10-04) =
* Renames plugin to comply with trademark rules 
* New: adds disabled setting for block themes, including documentation link
* Fix: moves hide woocommerce notice code to the function
* Fix: removes references to Jigoshop, WP Ecommerce and Eshop
* Fix: loads ajax-assist script when 'Always display cart' is disabled
* Fix: escapes HTML properly before echoing
* Fix: deprecate usage of globals
* Fix: missing menu notice style
* Tested up to WooCommerce 6.9

= 2.12.0 =
* Security: escape URL in admin notice
* Tweak: Settings styles & colors
* Tested up to WooCommerce 6.6 & WP 6.0

= 2.11.0 =
* New: Support for Full Site Editing navigation blocks (WP5.9+)
* Tested up to WooCommerce 6.4

= 2.10.4 =
* Tested up to WooCommerce 6.1 & WP5.9

= 2.10.3 =
* Fix: Updated WooCommerce compatibility header

= 2.10.2 =
* Translations: Add German (Formal)
* Tested up to WooCommerce 6.0

= 2.10.1 =
* Fix: WP eCommerce compatibility
* New: Better custom ajax options, using a custom event trigger (`wpmenucart_update_cart_ajax`)
* Tested up to WooCommerce 5.8

= 2.10.0 =
* New: use minified JS & CSS files to reduce load time on live sites (enabling `SCRIPT_DEBUG` will load full versions)
* Tested up to WooCommerce 5.7 & WP5.8

= 2.9.8 =
* Fix: Don't load free version if Pro version is loaded/installed
* Fix: jQuery deprecation notices
* Tweak: parse font stylesheet to use absolute links
* Translations: updated template & added translation hints
* Tested up to WooCommerce 5.4 & WP5.7

= 2.9.7 =
* Tested up to WooCommerce 5.1 & WP5.6

= 2.9.6 =
* Tweak: Improved font loading performance for modern browsers
* Tested up to WooCommerce 4.6

= 2.9.5 =
* Added filters for menu item data
* Fix: backwards compatibility for WooCommerce 3.2
* Tested up to WooCommerce 4.5

= 2.9.4.1 =
* Fix plugin header issue for new installs

= 2.9.4 =
* Tested up to WooCommerce 4.4 & WP5.5

= 2.9.3 =
* Tested up to WooCommerce 4.3

= 2.9.2 =
* Fix: EDD compatibility

= 2.9.1 =
* Fix: Menu issues on cart & checkout if Menu Cart was the only item in the menu

= 2.9.0 =
* New: setting to include fees & shipping in cart total [WooCommerce]
* New: hide on checkout & cart page by default (can be re-enabled via the settings) [WooCommerce]
* Fix: Incorrect total for when using "Cart total (including discounts)" in combination with taxable fees
* Fix: Button to save settings invisible on some installations

= 2.8.2 =
* Tested up to WooCommerce 4.2

= 2.8.1 =
* Fix: Assets versioning

= 2.8.0 =
* Improved: Drastically reduced font filesize for faster page loading
* New: filter to enable legacy custom ajax setting

= 2.7.9 =
* Tested up to WooCommerce 4.1
* Deprecated: Custom/Built-in AJAX option

= 2.7.8.1 =
* Fix: Plugin header

= 2.7.8 =
* Improved: Site/user locale detection
* Improved: Textdomain fallback
* Translations: Included POT & Updated Dutch
* Tested up to WooCommerce 4.0 & WP5.4

= 2.7.7 =
* Fix: include default classes when menu cart is the only item in the menu
* Fix: cart existence check global usage only for old versions
* Tested up to WooCommerce 3.9

= 2.7.6 =
* Improved accessibility for screen readers (cart icon)
* Tested up to WC3.8
* Tested up to WP5.3

= 2.7.5 =
* Fix: check if woocommerce version constant is defined
* Fix: Prevent fatal errors when switching eCommerce plugins
* Fix: Persian translations
* Fix: Notices when not using icon
* Marked tested up to WC3.6
* Marked tested up to WP5.2

= 2.7.4 =
* Tested up to WP5.1

= 2.7.3 =
* Fix: French plural forms rule (zero = single)

= 2.7.2 =
* Tested with WooCommerce 3.5

= 2.7.1 =

* fix label on price to display setting
* Fix live updating cart for first product with 'Always display cart' setting enabled

= 2.7.0 =

* Feature: Full integration with wordpress.org language packs (finally!)
* Feature (WooCommerce): Option to display either total (including fees) or subtotal (total of products)
* Feature (EDD): Native integration with EDD AJAX
* Fix: Several improvements & fixes to WooCommerce AJAX integration for sites with server side caching
* Fix: Cart icon on settings page
* Translations: added Turkish

= 2.6.1 =

* Feature: Option to hide theme cart from Storefront or Divi
* Feature: Improved WooCommerce AJAX compatibility
* Fix: Updated FontAwesome to 4.7.0
* Tweak: load FontAwesome in separate CSS file to allow dequeueing
* Translations: Updated pt_BR

= 2.6.0 =

* WooCommerce 3.0 compatibility
* Translations: updated Swedish

= 2.5.8 =

* Translations: Added Croatian, Hebrew, Hungarian (updated) & Vietnamese
* Fix: Built-in AJAX for multiple menus
* Fix: Textdomain definition and allow custom translations
* Tweak: prevent loading cart when WooCommerce not loaded

= 2.5.7 =

* Fix: Improved JS in Easy Digital Downloads
* New: Hungarian translation
* Tweak: Use css dash instead of hard-coded dash

= 2.5.6 =

* New: Slovak Translation
* New: Option to use built in js
* Tweak: Moved JS to footer
* Tweak: improved css positioning
* Tweak: added js selectors
* Tweak: Brazilian Portuguese Translation

= 2.5.5 =

* New: Norwegian Translation

= 2.5.4 =

* New: Czech Translations
* New: Greek Translations
* Tweak: Seperated Item Classes
* Tweak: Removed Unnecessary submenu classes
* Tweak: Use get_total() instead of get_cart_total()
* Fix: Prices show tax if cart prices are set to display including tax
* Fix: Updated Font Awesome

= 2.5.3 =

* Fix: Ubermenu
* Added: Greek Translation

= 2.5.2 =

*Tweak: Merged menu cart versions

= 2.5.1 =

* WPML String Translation fix

= 2.5 =

* Major Code refactor: CLEANER, FASTER, MORE FLEXIBLE!
* Added: Shop detection for Multisite
* Added: WPML String Translation setting
* Added: Persian translations
* Updated: Font Awesome
* Updated: Spanish, Portugese, Brazilian, French & Polish Translations
* Fix: PHP strict warnings
* Fix: CSS for Twenty Twelve & Twenty Fourteen

= 2.2.2 =

* Jigoshop Bug Fix

= 2.2.1 =

* WPML bug fixes

= 2.2.0 =

* Several bugfixes & improvements
* Better AJAX integration with EDD & eShop
* Various filters added for better theme integration & easier customization
* DOMHtml warnings surpressed

= 2.1.5 =

Fix: Edd and WP e-Commerce ajax.

= 2.1.4 =

Fixed WP e-Commerce ajax conflict and uploaded proper French translation.

= 2.1.3 =

EDD total price bug fixed

= 2.1.2 =

Added WP-Ecommerce and EDD

= 2.1.0 =

Initial Release

== Upgrade Notice ==

= 2.5.5 =

* New: Norwegian Translation

= 2.5.4 =

* New: Czech Translations
* New: Greek Translations
* Tweak: Seperated Item Classes
* Tweak: Removed Unnecessary submenu classes
* Tweak: Use get_total() instead of get_cart_total()
* Fix: Prices show tax if cart prices are set to display including tax
* Fix: Updated Font Awesome

= 2.5.3 =

* Fix: Ubermenu
* Added: Greek Translation

= 2.5.2 =

*Tweak: Merged menu cart versions

= 2.5.1 =

* WPML String Translation fix

= 2.5 =

* Major Code refactor: CLEANER, FASTER, MORE FLEXIBLE!
* Added: Shop detection for Multisite
* Added: WPML String Translation setting
* Added: Persian translations
* Updated: Font Awesome
* Updated: Translations
* Fix: PHP strict warnings
* Fix: CSS for Twenty Twelve & Twenty Fourteen

= 2.2.2 =

* Jigoshop Bug Fix

= 2.2.1 =

* WPML bug fixes

= 2.2.0 =

* Several bugfixes & improvements
* Better AJAX integration with EDD & eShop
* Various filters added for better theme integration & easier customization
* DOMHtml warnings surpressed

= 2.1.5 =

Fix: Edd and WP e-Commerce ajax.

= 2.1.4 =

Fixed WP e-Commerce ajax conflict and uploaded proper French translation.

= 2.1.3 =

EDD total price bug fixed

= 2.1.2 =

Added WP-Ecommerce and EDD

= 2.1.0 =

Initial Release