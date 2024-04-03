<?php

/*
Plugin Name: Conditional Shipping for WooCommerce
Description: Disable shipping methods based on shipping classes, weight, categories and much more.
Version:     2.1.0
Author:      Lauri Karisola / WooElements.com
Author URI:  https://wooelements.com
Text Domain: woo-conditional-shipping
Domain Path: /languages
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0.0
WC tested up to: 4.4.1
*/

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version
 */
if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_VERSION' ) ) {
	define( 'WOO_CONDITIONAL_SHIPPING_VERSION', '2.1.0' );
}

/**
 * Assets version
 */
if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION' ) ) {
	define( 'WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION', '2.1.0.free' );
}

/**
 * Load plugin textdomain
 *
 * @return void
 */
add_action( 'plugins_loaded', 'woo_conditional_shipping_load_textdomain' );
function woo_conditional_shipping_load_textdomain() {
  load_plugin_textdomain( 'woo-conditional-shipping', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

class Woo_Conditional_Shipping {
	/**
	 * Constructor
	 */
	function __construct() {
		// WooCommerce not activated, abort
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		// Pro version activated, abort
		if ( class_exists( 'Woo_Conditional_Shipping_Pro' ) ) {
			return;
		}

		if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_BASENAME' ) ) {
			define( 'WOO_CONDITIONAL_SHIPPING_BASENAME', plugin_basename( __FILE__ ) );
		}

		$this->includes();

		// Go Pro settings link
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Include required files
	 */
	public function includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-conditional-shipping-updater.php' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-conditional-shipping-filters.php' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-woo-conditional-shipping-post-type.php', 'Woo_Conditional_Shipping_Post_Type' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-woo-conditional-shipping-ruleset.php', 'Woo_Conditional_Shipping_Ruleset' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-conditional-shipping-utils.php' );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/frontend/class-woo-conditional-shipping-frontend.php', 'Woo_Conditional_Shipping_Frontend' );
	}

	/**
	 * Include admin files
	 */
	private function admin_includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/admin/class-woo-conditional-shipping-admin.php', 'Woo_Conditional_Shipping_Admin' );
	}

	/**
	 * Load class
	 */
	private function load_class( $filepath, $class_name = FALSE ) {
		require_once( $filepath );

		if ( $class_name ) {
			return new $class_name;
		}

		return TRUE;
	}

	/**
	 * Add settings link to the plugins page.
	 */
	public function add_settings_link( $links ) {
		$link = '<span style="font-weight:bold;"><a href="https://wooelements.com/products/conditional-shipping" style="color:#46b450;" target="_blank">' . __( 'Go Pro' ) . '</a></span>';

		return array_merge( array( $link ), $links );
	}
}

function init_woo_conditional_shipping() {
	new Woo_Conditional_Shipping();
}

add_action( 'plugins_loaded', 'init_woo_conditional_shipping', 110 );
