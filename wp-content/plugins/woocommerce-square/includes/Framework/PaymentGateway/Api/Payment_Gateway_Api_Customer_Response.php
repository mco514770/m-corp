<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Api;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API Customer Response
 */
interface Payment_Gateway_API_Customer_Response extends Payment_Gateway_API_Response {
	/**
	 * Returns the customer ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string customer ID returned by the gateway
	 */
	public function get_customer_id();
}
