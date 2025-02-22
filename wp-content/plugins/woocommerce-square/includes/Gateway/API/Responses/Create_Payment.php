<?php

namespace WooCommerce\Square\Gateway\API\Responses;

use WooCommerce\Square\Framework\PaymentGateway\Api\Payment_Gateway_API_Authorization_Response;
use WooCommerce\Square\Framework\PaymentGateway\Api\Payment_Gateway_API_Response_Message_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * The Create Payment API response object.
 *
 * @since 2.2.0
 *
 * @method \Square\Models\CreatePaymentResponse get_data()
 */
class Create_Payment extends \WooCommerce\Square\Gateway\API\Response implements Payment_Gateway_API_Authorization_Response {

	/**
	 * Determines if the charge was held.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public function transaction_held() {

		$held = parent::transaction_held();

		// ensure the tender is CAPTURED
		if ( $this->get_payment() ) {
			$held = 'AUTHORIZED' === $this->get_payment()->getCardDetails()->getStatus();
		}

		return $held;
	}


	/** Getter methods ************************************************************************************************/


	/**
	 * Gets the authorization code.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_authorization_code() {

		return $this->get_payment() ? $this->get_payment()->getId() : '';
	}


	/**
	 * Gets the transaction (payment) ID.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_transaction_id() {

		return $this->get_payment() ? $this->get_payment()->getId() : '';
	}



	/**
	 * Gets the location ID.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_location_id() {

		return $this->get_payment() ? $this->get_payment()->getLocationId() : '';
	}


	/**
	 * Gets the Square order ID, if any.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public function get_square_order_id() {

		return $this->get_payment() ? $this->get_payment()->getOrderId() : '';
	}


	/**
	 * Gets the Square payment object.
	 *
	 * @since 2.2.0
	 *
	 * @return \Square\Models\Payment|null
	 */
	public function get_payment() {
		return ! $this->has_errors() && $this->get_data()->getPayment() ? $this->get_data()->getPayment() : null;
	}


	/**
	 * Gets the message to display to the user.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_user_message() {

		$message_id = $this->get_status_code();

		$helper = new \WooCommerce\Square\Gateway\API\Response_Message_Helper();

		return $helper->get_user_message( $message_id );
	}


	/** No-op methods *************************************************************************************************/


	public function get_avs_result() { }

	public function get_csc_result() { }

	public function csc_match() { }


}
