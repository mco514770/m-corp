<?php
namespace WooCommerce\Square\Framework\Api;

defined( 'ABSPATH' ) or exit;

interface API_Response {

	/**
	 * Returns the string representation of this request
	 *
	 * @since 3.0.0
	 * @return string the request
	 */
	public function to_string();


	/**
	 * Returns the string representation of this request with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 3.0.0
	 * @return string the request, safe for logging/displaying
	 */
	public function to_string_safe();
}
