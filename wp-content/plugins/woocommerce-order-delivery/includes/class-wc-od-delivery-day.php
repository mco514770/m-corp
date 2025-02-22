<?php
/**
 * A class for representing a delivery day.
 *
 * @package WC_OD/Classes
 * @since   1.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_OD_Delivery_Day class.
 */
class WC_OD_Delivery_Day extends WC_OD_Shipping_Methods_Data {

	/**
	 * The weekday number.
	 *
	 * 0 for Sunday through 6 Saturday.
	 *
	 * @var int
	 */
	protected $weekday;

	/**
	 * Delivery day object data.
	 *
	 * Name value pairs (name + default value).
	 *
	 * @var array
	 */
	protected $data = array(
		'enabled'     => 'yes',
		'time_frames' => array(),
	);

	/**
	 * Constructor.
	 *
	 * @since 1.6.0
	 *
	 * @param array $data    The object data.
	 * @param int   $weekday Optional. The weekday number.
	 */
	public function __construct( array $data = array(), $weekday = null ) {
		parent::__construct( $data );

		if ( ! is_null( $weekday ) ) {
			$this->set_weekday( $weekday );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets the weekday number.
	 *
	 * @since 1.6.0
	 *
	 * @return int
	 */
	public function get_weekday() {
		return $this->weekday;
	}

	/**
	 * Gets the 'enabled' property.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public function get_enabled() {
		return wc_od_bool_to_string( $this->get_prop( 'enabled' ) );
	}

	/**
	 * Gets the time frames.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public function get_time_frames() {
		// Lazy load the time frames collection.
		if ( ! $this->data['time_frames'] instanceof WC_OD_Collection_Time_Frames ) {
			$this->data['time_frames'] = new WC_OD_Collection_Time_Frames( $this->data['time_frames'] );
		}

		return $this->get_prop( 'time_frames' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sets the 'enabled' property.
	 *
	 * @since 1.6.0
	 *
	 * @param string $enabled The status.
	 */
	public function set_enabled( $enabled ) {
		$this->set_prop( 'enabled', wc_od_bool_to_string( $enabled ) );
	}

	/**
	 * Sets the time frames.
	 *
	 * @since 1.6.0
	 *
	 * @param array $time_frames Optional. The time frames to set.
	 */
	public function set_time_frames( $time_frames = array() ) {
		$this->set_prop( 'time_frames', $time_frames );
	}

	/**
	 * Sets the weekday number.
	 *
	 * @since 1.6.0
	 *
	 * @param int $weekday The weekday number.
	 */
	public function set_weekday( $weekday ) {
		$weekday = absint( $weekday );

		// The number must be between 0 and 6.
		if ( $weekday > -1 && $weekday < 7 ) {
			$this->weekday = $weekday;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets if the delivery day is enabled or not.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return wc_od_string_to_bool( $this->get_enabled() );
	}

	/**
	 * Gets if the delivery day has time frames defined or not.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function has_time_frames() {
		return ( is_array( $this->data['time_frames'] ) ? ! empty( $this->data['time_frames'] ) : ! $this->data['time_frames']->is_empty() );
	}

	/**
	 * Gets if a shipping method is valid or not.
	 *
	 * @since 1.6.0
	 *
	 * @param string $shipping_method The shipping method to validate.
	 * @return bool
	 */
	public function validate_shipping_method( $shipping_method ) {
		if ( ! $this->has_time_frames() ) {
			return parent::validate_shipping_method( $shipping_method );
		}

		$valid       = false;
		$time_frames = $this->get_time_frames();

		foreach ( $time_frames as $time_frame ) {
			if ( $time_frame->validate_shipping_method( $shipping_method ) ) {
				$valid = true;
				break;
			}
		}

		return $valid;
	}
}
