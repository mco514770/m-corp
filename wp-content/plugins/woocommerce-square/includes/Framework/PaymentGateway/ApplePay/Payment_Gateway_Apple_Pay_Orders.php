<?php
/**
 * Apple Pay Orders class
 *
 * This file defines a class that provides an API to modify orders
 * created through Apple Pay.
 *
 * @package WooCommerce Square
 * @subpackage Apple Pay
 * @since 3.0.0
 */

namespace WooCommerce\Square\Framework\PaymentGateway\ApplePay;

use WooCommerce\Square\Framework\Compatibility\Order_Compatibility;
use WooCommerce\Square\Framework\Plugin_Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * The Apple Pay order handler.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Apple_Pay_Orders {

	/**
	 * Creates an order from a cart.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @return \WC_Order|void
	 * @throws \Exception
	 * @throws \Exception
	 */
	public static function create_order( \WC_Cart $cart ) {

		if ( Plugin_Compatibility::is_wc_version_lt( '3.2' ) && ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$cart->calculate_totals();

		try {

			wc_transaction_query( 'start' );

			$order_data = array(
				'status'      => apply_filters( 'woocommerce_default_order_status', 'pending' ),
				'customer_id' => get_current_user_id(),
				'cart_hash'   => md5( wp_json_encode( wc_clean( $cart->get_cart_for_session() ) ) . $cart->total ),
				'created_via' => 'apple_pay',
			);

			$order = self::get_order_object( $order_data );

			foreach ( $cart->get_cart() as $cart_item_key => $item ) {

				$args = array(
					'variation' => $item['variation'],
					'totals'    => array(
						'subtotal'     => $item['line_subtotal'],
						'subtotal_tax' => $item['line_subtotal_tax'],
						'total'        => $item['line_total'],
						'tax'          => $item['line_tax'],
						'tax_data'     => $item['line_tax_data']
					),
				);

				if ( ! $order->add_product( $item['data'], $item['quantity'], $args ) ) {
					throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 525 ) );
				}
			}

			foreach ( $cart->get_coupons() as $code => $coupon ) {

				if ( ! Order_Compatibility::add_coupon( $order, $code, $cart->get_coupon_discount_amount( $code ), $cart->get_coupon_discount_tax_amount( $code ) ) ) {
					throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 529 ) );
				}
			}

			$chosen_methods = WC()->session->get( 'chosen_shipping_methods', array() );

			foreach ( WC()->shipping->get_packages() as $key => $package ) {

				if ( isset( $package['rates'][ $chosen_methods[ $key ] ] ) ) {

					$method = $package['rates'][ $chosen_methods[ $key ] ];

					if ( ! Order_Compatibility::add_shipping( $order, $method ) ) {
						throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 527 ) );
					}
				}
			}

			// add fees
			foreach ( $cart->get_fees() as $key => $fee ) {

				if ( ! Order_Compatibility::add_fee( $order, $fee ) ) {
					throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 526 ) );
				}
			}

			$cart_taxes     = Plugin_Compatibility::is_wc_version_gte( '3.2' ) ? $cart->get_cart_contents_taxes() : $cart->taxes;
			$shipping_taxes = Plugin_Compatibility::is_wc_version_gte( '3.2' ) ? $cart->get_shipping_taxes() : $cart->shipping_taxes;

			foreach ( array_keys( $cart_taxes + $shipping_taxes ) as $rate_id ) {

				if ( $rate_id && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $rate_id ) {

					if ( ! Order_Compatibility::add_tax( $order, $rate_id, $cart->get_tax_amount( $rate_id ), $cart->get_shipping_tax_amount( $rate_id ) ) ) {
						throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 526 ) );
					}
				}
			}

			wc_transaction_query( 'commit' );

			$order->update_taxes();

			$order->calculate_totals( false ); // false to skip recalculating taxes

			do_action( 'woocommerce_checkout_update_order_meta', Order_Compatibility::get_prop( $order, 'id' ), array() );

			return $order;

		} catch ( \Exception $e ) {

			wc_transaction_query( 'rollback' );

			throw $e;
		}
	}


	/**
	 * Gets an order object for payment.
	 *
	 * @since 3.0.0
	 *
	 * @param array $order_data the order data
	 * @return \WC_Order
	 * @throws \Exception
	 */
	public static function get_order_object( $order_data ) {

		$order_id = (int) WC()->session->get( 'order_awaiting_payment', 0 );

		if ( $order_id && $order_data['cart_hash'] === get_post_meta( $order_id, '_cart_hash', true ) && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {

			$order_data['order_id'] = $order_id;

			$order = wc_update_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 522 ) );
			} else {
				$order->remove_order_items();
			}

		} else {

			$order = wc_create_order( $order_data );

			if ( is_wp_error( $order ) ) {
				throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 520 ) );
			} elseif ( false === $order ) {
				throw new \Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce-square' ), 521 ) );
			}

			// set the new order ID so it can be resumed in case of failure
			WC()->session->set( 'order_awaiting_payment', Order_Compatibility::get_prop( $order, 'id' ) );
		}

		return $order;
	}
}
