<?php

namespace WooCommerce\Square\Framework\Api;

defined( 'ABSPATH' ) or exit;

/**
 * Base JSON API request class.
 *
 * @since 3.0.0
 */
abstract class API_JSON_Request implements API_Request {


	/** @var string The request method, one of HEAD, GET, PUT, PATCH, POST, DELETE */
	protected $method;

	/** @var string The request path */
	protected $path;

	/** @var array The request parameters, if any */
	protected $params = array();

	/** @var array the request data */
	protected $data = array();


	/**
	 * Get the request method.
	 *
	 * @since 3.0.0
	 * @see API_Request::get_method()
	 * @return string
	 */
	public function get_method() {
		return $this->method;
	}


	/**
	 * Get the request path.
	 *
	 * @since 3.0.0
	 * @see API_Request::get_path()
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}


	/**
	 * Get the request parameters.
	 *
	 * @since 3.0.0
	 * @see API_Request::get_params()
	 * @return array
	 */
	public function get_params() {
		return $this->params;
	}


	/**
	 * Get the request data.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}


	/** API Helper Methods ******************************************************/


	/**
	 * Get the string representation of this request.
	 *
	 * @since 3.0.0
	 * @see API_Request::to_string()
	 * @return string
	 */
	public function to_string() {

		$data = $this->get_data();

		return ! empty( $data ) ? wp_json_encode( $data ) : '';
	}


	/**
	 * Get the string representation of this request with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 3.0.0
	 * @see API_Request::to_string_safe()
	 * @return string
	 */
	public function to_string_safe() {

		return $this->to_string();
	}
}
