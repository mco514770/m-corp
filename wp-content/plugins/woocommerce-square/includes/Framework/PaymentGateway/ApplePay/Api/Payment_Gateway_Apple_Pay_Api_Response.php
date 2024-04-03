<?php
/**
 * Apple Pay Response API class
 *
 * This file defines a class used to create the Response object
 * for features related to Apple Pay.
 *
 * @package WooCommerce Square
 * @subpackage Apple Pay
 * @since 3.0.0
 */

namespace WooCommerce\Square\Framework\PaymentGateway\ApplePay\Api;
use WooCommerce\Square\Framework\Api\API_JSON_Response;

defined( 'ABSPATH' ) or exit;

/**
 * The Apple Pay API response object.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Apple_Pay_API_Response extends API_JSON_Response {


	/**
	 * Gets the status code.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_code() {

		return $this->statusCode;
	}


	/**
	 * Gets the status message.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_status_message() {

		return $this->statusMessage;
	}


	/**
	 * Gets the validated merchant session.
	 *
	 * @since 3.0.0
	 *
	 * @return string|array
	 */
	public function get_merchant_session() {

		return $this->raw_response_json;
	}


	/**
	 * Get the string representation of this response with any and all sensitive
	 * elements masked or removed.
	 *
	 * No strong indication from the Apple documentation that these _need_ to be
	 * masked, but they don't provide any useful info and only make the debug
	 * logs unnecessarily huge.
	 *
	 * @since 3.0.0
	 * @see SquareFramework\Api\API_Response::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		// mask the merchant session ID
		$string = str_replace( $this->merchantSessionIdentifier, str_repeat( '*', 10 ), $string );

		// mask the merchant ID
		$string = str_replace( $this->merchantIdentifier, str_repeat( '*', 10 ), $string );

		// mask the signature
		$string = str_replace( $this->signature, str_repeat( '*', 10 ), $string );

		return $string;
	}
}
