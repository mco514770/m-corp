<?php
/**
 * Apple Pay Request API class
 *
 * This file defines a class used to create the Request object
 * for features related to Apple Pay.
 *
 * @package WooCommerce Square
 * @subpackage Apple Pay
 * @since 3.0.0
 */

namespace WooCommerce\Square\Framework\PaymentGateway\ApplePay\Api;

use WooCommerce\Square\Framework\Api\API_JSON_Request;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway;

defined( 'ABSPATH' ) or exit;

/**
 * The Apple Pay API request object.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Apple_Pay_API_Request extends API_JSON_Request {


	/** @var Payment_Gateway $gateway the gateway instance */
	protected $gateway;


	/**
	 * Constructs the request.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway $gateway the gateway instance
	 */
	public function __construct( Payment_Gateway $gateway ) {

		$this->gateway = $gateway;
	}


	/**
	 * Sets the data for merchant validation.
	 *
	 * @since 3.0.0
	 *
	 * @param string $merchant_id the merchant ID to validate
	 * @param string $domain_name the verified domain name
	 * @param string $display_name the merchant display name
	 */
	public function set_merchant_data( $merchant_id, $domain_name, $display_name ) {

		$data = array(
			'merchantIdentifier' => $merchant_id,
			'domainName'         => str_replace( array( 'http://', 'https://' ), '', $domain_name ),
			'displayName'        => $display_name,
		);

		/**
		 * Filters the data for merchant validation.
		 *
		 * @since 3.0.0
		 *
		 * @param array $data {
		 *     The merchant data.
		 *
		 *     @var string $merchantIdentifier the merchant ID
		 *     @var string $domainName         the verified domain name
		 *     @var string $displayName        the merchant display name
		 * }
		 * @param Payment_Gateway_Apple_Pay_API_Request the request object
		 */
		$this->data = apply_filters( 'sv_wc_apple_pay_api_merchant_data', $data, $this );
	}


	/**
	 * Get the string representation of this response with any and all sensitive
	 * elements masked or removed.
	 *
	 * @since 3.0.0
	 * @see API_Response::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		// mask the merchant ID
		$string = str_replace( $this->data['merchantIdentifier'], str_repeat( '*', strlen( $this->data['merchantIdentifier'] ) ), $this->to_string() );

		return $string;
	}
}
