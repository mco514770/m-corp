<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Api;
use WooCommerce\Square\Framework\PaymentGateway\Api\Payment_Gateway_API_Response;
use WooCommerce\Square\Framework\PaymentGateway\PaymentTokens\Payment_Gateway_Payment_Token;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API Create Payment Token Response
 */
interface Payment_Gateway_API_Create_Payment_Token_Response extends Payment_Gateway_API_Response {

	/**
	 * Returns the payment token.
	 *
	 * @since 3.0.0
	 *
	 * @return Payment_Gateway_Payment_Token payment token
	 */
	public function get_payment_token();
}
