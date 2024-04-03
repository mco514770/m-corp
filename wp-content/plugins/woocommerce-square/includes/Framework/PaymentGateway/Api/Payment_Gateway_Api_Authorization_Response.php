<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Api;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API Authorization Response
 *
 * Represents a Payment Gateway Credit Card Authorization response.  This should
 * also be used as the parent class for credit card charge (authorization +
 * capture) responses.
 */
interface Payment_Gateway_API_Authorization_Response extends Payment_Gateway_API_Response {

	/**
	 * The authorization code is returned from the credit card processor to
	 * indicate that the charge will be paid by the card issuer.
	 *
	 * @since 3.0.0
	 *
	 * @return string credit card authorization code
	 */
	public function get_authorization_code();


	/**
	 * Returns the result of the AVS check.
	 *
	 * @since 3.0.0
	 *
	 * @return string result of the AVS check, if any
	 */
	public function get_avs_result();


	/**
	 * Returns the result of the CSC check.
	 *
	 * @since 3.0.0
	 *
	 * @return string result of CSC check
	 */
	public function get_csc_result();


	/**
	 * Returns true if the CSC check was successful.
	 *
	 * @since 3.0.0
	 *
	 * @return boolean true if the CSC check was successful
	 */
	public function csc_match();
}
