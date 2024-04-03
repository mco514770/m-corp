<?php
/**
 * Sync Completed Email class
 *
 * This file defines a class that describes Apple Pay's
 * Payment Response object.
 *
 * @package WooCommerce Square
 * @since 3.0.0
 */

namespace WooCommerce\Square\Framework\PaymentGateway\ApplePay\Api;
use WooCommerce\Square\Framework\Api\API_JSON_Response;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway_Helper;

defined( 'ABSPATH' ) or exit;

/**
 * The Apple Pay payment response object.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Apple_Pay_Payment_Response extends API_JSON_Response {


	/**
	 * Gets the authorized payment data.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_payment_data() {

		return ! empty( $this->token->paymentData ) ? (array) $this->token->paymentData : array();
	}


	/**
	 * Gets the authorization transaction ID.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_transaction_id() {

		return ! empty( $this->token->transactionIdentifier ) ? $this->token->transactionIdentifier : '';
	}


	/**
	 * Gets the authorized card type.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_card_type() {

		$card_type = ! empty( $this->token->paymentMethod->network ) ? $this->token->paymentMethod->network : 'card';

		return Payment_Gateway_Helper::normalize_card_type( $card_type );
	}


	/**
	 * Gets the last four digits of the authorized card.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_last_four() {

		$last_four = '';

		if ( ! empty( $this->token->paymentMethod->displayName ) ) {
			$last_four = substr( $this->token->paymentMethod->displayName, -4 );
		}

		return $last_four;
	}


	/**
	 * Gets the billing address.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_billing_address() {

		$address = ! empty( $this->response_data->billingContact ) ? $this->response_data->billingContact : null;

		$billing_address = $this->prepare_address( $address );

		// set the billing email
		if ( ! empty( $this->response_data->shippingContact->emailAddress ) ) {
			$billing_address['email'] = $this->shippingContact->emailAddress;
		}

		// set the billing phone number
		if ( ! empty( $this->response_data->shippingContact->phoneNumber ) ) {
			$billing_address['phone'] = $this->shippingContact->phoneNumber;
		}

		return $billing_address;
	}


	/**
	 * Gets the shipping address.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_shipping_address() {

		$address = ! empty( $this->response_data->shippingContact ) ? $this->response_data->shippingContact : null;

		$shipping_address = $this->prepare_address( $address );

		return $shipping_address;
	}


	/**
	 * Prepare an address to WC formatting.
	 *
	 * @since 3.0.0
	 *
	 * @param \stdClass|object $contact the address to prepare
	 * @return array
	 */
	protected function prepare_address( $contact ) {

		$address = array(
			'first_name' => isset( $contact->givenName )       ? $contact->givenName :       '',
			'last_name'  => isset( $contact->familyName )      ? $contact->familyName :      '',
			'address_1'  => isset( $contact->addressLines[0] ) ? $contact->addressLines[0] : '',
			'address_2'  => '',
			'city'       => isset( $contact->locality )           ? $contact->locality :           '',
			'state'      => isset( $contact->administrativeArea ) ? $contact->administrativeArea : '',
			'postcode'   => isset( $contact->postalCode )         ? $contact->postalCode :         '',
			'country'    => isset( $contact->countryCode )        ? $contact->countryCode :        '',
		);

		if ( ! empty( $contact->addressLines[1] ) ) {
			$address['address_2'] = $contact->addressLines[1];
		}

		$address['country'] = strtoupper( $address['country'] );

		return $address;
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
	 *
	 * @see WooCommerce\Square\Framework\Api\API_Response::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		if ( ! empty( $this->token->paymentData->data ) ) {
			$string = str_replace( $this->token->paymentData->data, str_repeat( '*', 10 ), $string );
		}

		if ( ! empty( $this->token->paymentData->signature ) ) {
			$string = str_replace( $this->token->paymentData->signature, str_repeat( '*', 10 ), $string );
		}

		return $string;
	}
}
