<?php
/**
 * Mix and Match Product Quantity
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/mnm/mnm-product-quantity.php.
 *
 * HOWEVER, on occasion WooCommerce Mix and Match will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  Kathy Darling
 * @package WooCommerce Mix and Match/Templates
 * @since   1.0.0
 * @version 1.9.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

global $product;

$mnm_id = $mnm_item->get_id();

if ( $product->is_in_stock() && $mnm_item->is_in_stock() ) {
			
	/**
	 * The quantity input name.
	 */
	$quantity_name = wc_mnm_get_child_input_name( $product->get_id() );

	/**
	 * The quantity input value.
	 */
	$quantity = isset( $_REQUEST[ $quantity_name ] ) && ! empty ( $_REQUEST[ $quantity_name ][ $mnm_id ] ) ? intval( $_REQUEST[ $quantity_name ][ $mnm_id ] ) : apply_filters( 'woocommerce_mnm_quantity_input', '', $mnm_item, $product );

	ob_start();

	/**
	 * filter woocommerce_mnm_child_quantity_input_args.
	 *
	 * @param array $args
	 * @param obj WC_Product
	 * @param obj WC_Product_Mix_and_Match
	 */
	woocommerce_quantity_input( 
		apply_filters( 'woocommerce_mnm_child_quantity_input_args',
			array(
				'input_name'  => $quantity_name . '[' . $mnm_id . ']',
				'input_value' => $quantity,
				'min_value'   => $product->get_child_quantity( 'min', $mnm_id ),
				'max_value'   => $product->get_child_quantity( 'max', $mnm_id ),
				'placeholder' => 0
			),
			$mnm_item,
			$product ),
		$mnm_item );
	echo str_replace( 'class="quantity"', 'class="quantity mnm-quantity"', ob_get_clean() );

} else {

	/**
	 * Child item availability message.
	 *
	 * @param str $availability
	 * @param obj WC_Product
	 */
	echo apply_filters( 'woocommerce_mnm_availability_html', $product->get_child_availability_html( $mnm_id ), $mnm_item );
}