<?php
namespace WooCommerce\Square\Framework\Addresses;
use WooCommerce\Square\Framework\Compatibility\Order_Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * The customer address data class.
 *
 * Adds customer-specific data to a base address, as used for a billing or shipping address that can include first
 * and last name.
 *
 * @see Address
 *
 * @since 3.0.0
 */
class Customer_Address extends Address {


	/** @var string customer first name */
	protected $first_name = '';

	/** @var string customer last name */
	protected $last_name = '';


	/** Getter Methods ************************************************************************************************/


	/**
	 * Gets the customer first name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_first_name() {

		return $this->first_name;
	}


	/**
	 * Gets the customer first name.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	public function get_last_name() {

		return $this->last_name;
	}


	/**
	 * Gets the data used to generate a hash for the address.
	 *
	 * @see Address::get_hash_data()
	 *
	 * @since 3.0.0
	 *
	 * @return string[]
	 */
	protected function get_hash_data() {

		// add the first & last name to data used to generate the hash
		$data = array_merge(
			array(
				$this->get_first_name(),
				$this->get_last_name(),
			),
			parent::get_hash_data()
		);

		return $data;
	}


	/** Setter Methods ************************************************************************************************/


	/**
	 * Sets the customer first name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value first name value
	 */
	public function set_first_name( $value ) {

		$this->first_name = $value;
	}


	/**
	 * Sets the customer last name.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value first name value
	 */
	public function set_last_name( $value ) {

		$this->last_name = $value;
	}


	/**
	 * Sets the full address based on a WooCommerce order.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order WooCommerce order object
	 * @param string $type address type, like billing or shipping
	 */
	public function set_from_order( \WC_Order $order, $type = 'billing' ) {

		$this->set_first_name( Order_Compatibility::get_prop( $order, "{$type}_first_name" ) );
		$this->set_last_name( Order_Compatibility::get_prop( $order, "{$type}_last_name" ) );
		$this->set_line_1( Order_Compatibility::get_prop( $order, "{$type}_address_1" ) );
		$this->set_line_2( Order_Compatibility::get_prop( $order, "{$type}_address_2" ) );
		$this->set_locality( Order_Compatibility::get_prop( $order, "{$type}_city" ) );
		$this->set_region( Order_Compatibility::get_prop( $order, "{$type}_state" ) );
		$this->set_country( Order_Compatibility::get_prop( $order, "{$type}_country" ) );
		$this->set_postcode( Order_Compatibility::get_prop( $order, "{$type}_postcode" ) );
	}
}
