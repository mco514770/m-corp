<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Api;
use WooCommerce\Square\Framework\PaymentGateway\PaymentTokens\Payment_Gateway_Payment_Token;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API Create Payment Token Response
 */
interface Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response extends Payment_Gateway_API_Response {

	/**
	 * Returns any payment tokens.
	 *
	 * @since 3.0.0
	 *
	 * @return Payment_Gateway_Payment_Token[] array of Payment_Gateway_Payment_Token payment tokens, keyed by the token ID
	 */
	public function get_payment_tokens();
}
