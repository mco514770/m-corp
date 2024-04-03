<?php

namespace WooCommerce\Square\Framework;

defined( 'ABSPATH' ) or exit;

class Plugin_Compatibility {

	/**
	 * Logs a doing_it_wrong message.
	 *
	 * Backports wc_doing_it_wrong() to WC 2.6.
	 *
	 * @since 3.0.0
	 *
	 * @param string $function function used
	 * @param string $message message to log
	 * @param string $version version the message was added in
	 */
	public static function wc_doing_it_wrong( $function, $message, $version ) {

		if ( self::is_wc_version_gte( '3.0' ) ) {

			wc_doing_it_wrong( $function, $message, $version );

		} else {

			$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

			if ( wp_doing_ajax() ) {

				do_action( 'doing_it_wrong_run', $function, $message, $version );
				error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );

			} else {

				_doing_it_wrong( $function, $message, $version );
			}
		}
	}

	/**
	 * Helper method to get the version of the currently installed WooCommerce
	 *
	 * @since 3.0.0
	 * @return string woocommerce version number or null
	 */
	public static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Determines if the installed version of WooCommerce is less than 3.0.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public static function is_wc_version_lt_3_0() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.0', '<' );
	}


	/**
	 * Determines if the installed version of WooCommerce is 3.1 or greater.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	public static function is_wc_version_gte_3_1() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.1', '>=' );
	}

	/**
	 * Determines if the installed version of WooCommerce meets or exceeds the
	 * passed version.
	 *
	 * @since 3.0.0
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_gte( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
	}


	/**
	 * Determines if the installed version of WooCommerce is lower than the
	 * passed version.
	 *
	 * @since 3.0.0
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_lt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '<' );
	}

	/**
	 * Normalizes a WooCommerce page screen ID.
	 *
	 * Needed because WordPress uses a menu title (which is translatable), not slug, to generate screen ID.
	 * See details in: https://core.trac.wordpress.org/ticket/21454
	 *
	 * @since 3.0.0
	 * @param string $slug slug for the screen ID to normalize (minus `woocommerce_page_`)
	 * @return string normalized screen ID
	 */
	public static function normalize_wc_screen_id( $slug = 'wc-settings' ) {

		// The textdomain usage is intentional here, we need to match the menu title.
		$prefix = sanitize_title( __( 'WooCommerce', 'woocommerce-square' ) );

		return $prefix . '_page_' . $slug;
	}


	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
	 *
	 * Based on ActionScheduler's compat wrapper for the same function:
	 * ActionScheduler_Compatibility::convert_hr_to_bytes()
	 *
	 * @link https://secure.php.net/manual/en/function.ini-get.php
	 * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 *
	 * @since 3.0.0
	 *
	 * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
	 * @return int An integer byte value.
	 */
	public static function convert_hr_to_bytes( $value ) {

		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {

			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {

			$bytes *= GB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'm' ) ) {

			$bytes *= MB_IN_BYTES;

		} elseif ( false !== strpos( $value, 'k' ) ) {

			$bytes *= KB_IN_BYTES;
		}

		// deal with large (float) values which run into the maximum integer size
		return min( $bytes, PHP_INT_MAX );
	}
}

