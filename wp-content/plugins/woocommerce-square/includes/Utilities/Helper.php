<?php
namespace WooCommerce\Square\Utilities;
defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Square Helper Class
 *
 * The purpose of this class is to centralize common utility functions.
 *
 * @since 2.2.0
 */
class Helper {
	/**
	 * Format a number with 2 decimal points, using a period for the decimal
	 * separator and no thousands separator.
	 *
	 * Commonly used for payment gateways which require amounts in this format.
	 *
	 * @since 3.0.0
	 * @param float $number
	 * @return string
	 */
	public static function number_format( $number ) {

		return number_format( (float) $number, 2, '.', '' );
	}

	/**
	 * Safely get and trim data from $_POST
	 *
	 * @since 3.0.0
	 * @param string $key array key to get from $_POST array
	 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
	 */
	public static function get_post( $key ) {

		if ( isset( $_POST[ $key ] ) ) {
			return trim( $_POST[ $key ] );
		}

		return '';
	}

	/**
	 * Add and store a notice.
	 *
	 * WC notice functions are not available in the admin
	 *
	 * @since 3.0.2
	 * @param string $message The text to display in the notice.
	 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
	 */
	public static function wc_add_notice( $message, $notice_type = 'success' ) {

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, $notice_type );
		}
	}

	/**
	 * Gets the full URL to the log file for a given $handle
	 *
	 * @since 4.0.0
	 * @param string $handle log handle
	 * @return string URL to the WC log file identified by $handle
	 */
	public static function get_wc_log_file_url( $handle ) {
		return admin_url( sprintf( 'admin.php?page=wc-status&tab=logs&log_file=%s-%s-log', $handle, sanitize_file_name( wp_hash( $handle ) ) ) );
	}

	/**
	 * Logs a doing_it_wrong message.
	 *
	 * Backports wc_doing_it_wrong() to WC 2.6.
	 *
	 * @since 5.0.1
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
	 * Determines if the installed version of WooCommerce meets or exceeds the
	 * passed version.
	 *
	 * @since 4.7.3
	 *
	 * @param string $version version number to compare
	 * @return bool
	 */
	public static function is_wc_version_gte( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
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
	 * Determines if the installed version of WooCommerce is 3.0 or greater.
	 *
	 * @since 4.6.0
	 * @return bool
	 */
	public static function is_wc_version_gte_3_0() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '3.0', '>=' );
	}
}
