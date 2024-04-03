<?php

namespace WooCommerce\Square\Framework\Compatibility;
use WooCommerce\Square\Framework\Plugin_Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce data compatibility class.
 *
 * @since 3.0.0
 */
abstract class Data_Compatibility {

	/**
	 * Gets an object property.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @param array $compat_props Compatibility properties.
	 * @return mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = array() ) {

		$value = '';

		if ( is_callable( array( $object, "get_{$prop}" ) ) ) {
			$value = $object->{"get_{$prop}"}( $context );
		}

		return $value;
	}


	/**
	 * Sets an object's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param array $props the new properties as $key => $value
	 * @param array $compat_props Compatibility properties.
	 * @return \WC_Data
	 */
	public static function set_props( $object, $props, $compat_props = array() ) {
		$object->set_props( $props );

		return $object;
	}


	/**
	 * Gets an object's stored meta value.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param bool $single whether to get the meta as a single item. Defaults to `true`
	 * @param string $context if 'view' then the value will be filtered
	 * @return mixed
	 */
	public static function get_meta( $object, $key = '', $single = true, $context = 'edit' ) {
		$value = $object->get_meta( $key, $single, $context );

		return $value;
	}


	/**
	 * Stores an object meta value.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param bool $unique Optional. Whether the meta should be unique.
	 */
	public static function add_meta_data( $object, $key, $value, $unique = false ) {
		$object->add_meta_data( $key, $value, $unique );
		$object->save_meta_data();
	}


	/**
	 * Updates an object's stored meta value.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 * @param string $value the meta value
	 * @param int|string $meta_id Optional. The specific meta ID to update
	 */
	public static function update_meta_data( $object, $key, $value, $meta_id = '' ) {
		$object->update_meta_data( $key, $value, $meta_id );
		$object->save_meta_data();
	}


	/**
	 * Deletes an object's stored meta value.
	 *
	 * @since 3.0.0
	 * @param \WC_Data $object the data object, likely \WC_Order or \WC_Product
	 * @param string $key the meta key
	 */
	public static function delete_meta_data( $object, $key ) {
		$object->delete_meta_data( $key );
		$object->save_meta_data();
	}
}

