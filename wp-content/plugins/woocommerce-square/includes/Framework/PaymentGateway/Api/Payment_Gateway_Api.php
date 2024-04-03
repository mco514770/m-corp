<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Api;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce Direct Payment Gateway API
 */
interface Payment_Gateway_API {


	/**
	 * Perform a credit card authorization for the given order
	 *
	 * If the gateway does not support credit card authorizations, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Payment_Gateway_API_Response credit card charge response
	 * @throws \Exception network timeouts, etc
	 */
	public function credit_card_authorization( \WC_Order $order );


	/**
	 * Perform a credit card charge for the given order
	 *
	 * If the gateway does not support credit card charges, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Payment_Gateway_API_Response credit card charge response
	 * @throws \Exception network timeouts, etc
	 */
	public function credit_card_charge( \WC_Order $order );


	/**
	 * Perform a credit card capture for a given authorized order
	 *
	 * If the gateway does not support credit card capture, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Payment_Gateway_API_Response credit card capture response
	 * @throws \Exception network timeouts, etc
	 */
	public function credit_card_capture( \WC_Order $order );


	/**
	 * Perform an eCheck debit (ACH transaction) for the given order
	 *
	 * If the gateway does not support check debits, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Payment_Gateway_API_Response check debit response
	 * @throws \Exception network timeouts, etc
	 */
	public function check_debit( \WC_Order $order );


	/**
	 * Perform a refund for the given order
	 *
	 * If the gateway does not support refunds, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Payment_Gateway_API_Response refund response
	 * @throws \Exception network timeouts, etc
	 */
	public function refund( \WC_Order $order );


	/**
	 * Perform a void for the given order
	 *
	 * If the gateway does not support voids, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Payment_Gateway_API_Response void response
	 * @throws \Exception network timeouts, etc
	 */
	public function void( \WC_Order $order );


	/**
	 * Creates a payment token for the given order
	 *
	 * If the gateway does not support tokenization, this method can be a no-op.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order the order
	 * @return Payment_Gateway_API_Create_Payment_Token_Response payment method tokenization response
	 * @throws \Exception network timeouts, etc
	 */
	public function tokenize_payment_method( \WC_Order $order );


	/**
	 * Updates a tokenized payment method.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Payment_Gateway_API_Response
	 * @throws \Exception
	 */
	public function update_tokenized_payment_method( \WC_Order $order );


	/**
	 * Determines if this API supports updating tokenized payment methods.
	 *
	 * @see Payment_Gateway_API::update_tokenized_payment_method()
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function supports_update_tokenized_payment_method();


	/**
	 * Removes the tokenized payment method.  This method should not be invoked
	 * unless supports_remove_tokenized_payment_method() returns true, otherwise
	 * the results are undefined.
	 *
	 * @since 3.0.0
	 * @see Payment_Gateway_API::supports_remove_tokenized_payment_method()
	 *
	 * @param string $token the payment method token
	 * @param string $customer_id unique customer id for gateways that support it
	 * @return Payment_Gateway_API_Response remove tokenized payment method response
	 * @throws \Exception network timeouts, etc
	 */
	public function remove_tokenized_payment_method( $token, $customer_id );


	/**
	 * Returns true if this API supports a "remove tokenized payment method"
	 * request.  If this method returns true, then remove_tokenized_payment_method()
	 * is considered safe to call.
	 *
	 * @since 3.0.0
	 * @see Payment_Gateway_API::remove_tokenized_payment_method()
	 *
	 * @return boolean true if this API supports a "remove tokenized payment method" request, false otherwise
	 */
	public function supports_remove_tokenized_payment_method();


	/**
	 * Returns all tokenized payment methods for the customer.  This method
	 * should not be invoked unless supports_get_tokenized_payment_methods()
	 * return true, otherwise the results are undefined
	 *
	 * @since 3.0.0
	 * @see Payment_Gateway_API::supports_get_tokenized_payment_methods()
	 *
	 * @param string $customer_id unique customer id
	 * @return Payment_Gateway_API_Get_Tokenized_Payment_Methods_Response response containing any payment tokens for the customer
	 * @throws \Exception network timeouts, etc
	 */
	public function get_tokenized_payment_methods( $customer_id );


	/**
	 * Returns true if this API supports a "get tokenized payment methods"
	 * request.  If this method returns true, then get_tokenized_payment_methods()
	 * is considered safe to call.
	 *
	 * @since 3.0.0
	 * @see Payment_Gateway_API::get_tokenized_payment_methods()
	 *
	 * @return boolean true if this API supports a "get tokenized payment methods" request, false otherwise
	 */
	public function supports_get_tokenized_payment_methods();


	/**
	 * Returns the most recent request object
	 *
	 * @since 3.0.0
	 *
	 * @return \WooCommerce\Square\Framework\Api\API_Request the most recent request object
	 */
	public function get_request();


	/**
	 * Returns the most recent response object
	 *
	 * @since 3.0.0
	 *
	 * @return Payment_Gateway_API_Response the most recent response object
	 */
	public function get_response();


	/**
	 * Returns the WC_Order object associated with the request, if any
	 *
	 * @since 3.0.0
	 *
	 * @return \WC_Order
	 */
	public function get_order();
}
