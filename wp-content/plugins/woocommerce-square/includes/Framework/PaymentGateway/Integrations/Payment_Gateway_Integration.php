<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Integrations;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway;

defined( 'ABSPATH' ) or exit;

/**
 * Abstract Integration
 *
 * @since 3.0.0
 */
abstract class Payment_Gateway_Integration {


	/** @var Payment_Gateway direct gateway instance */
	protected $gateway;


	/**
	 * Bootstraps the class.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway $gateway direct gateway instance
	 */
	public function __construct( Payment_Gateway $gateway ) {

		$this->gateway = $gateway;
	}


	/**
	 * Return the gateway for the integration
	 *
	 * @since 3.0.0
	 * @return Payment_Gateway
	 */
	public function get_gateway() {

		return $this->gateway;
	}
}
