<?php
/**
 * WC_MNM_PIP_Compatibility class
 *
 * @author   SomewhereWarm <info@somewherewarm.gr>
 * @category Compatibility
 * @package  WooCommerce Mix and Match Products/Compatibility
 * @since    1.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print Invoices & Packing Lists Integration.
 *
 * @version  1.7.0
 */
class WC_MNM_PIP_Compatibility {

	/**
	 * The document being processed.
	 * @var WC_PIP_Document
	 */
	public static $document;

	/**
	 * Flag to control internal flow in 'items_count'.
	 * @var bool
	 */
	private static $recounting_items = false;

	/**
	 * Add hooks.
	 */
	public static function init() {

		// Fires off before rendering the PIP document body.
		add_action( 'wc_pip_before_body', array( __CLASS__, 'before_body' ), 10, 4 );

		// Fires off after rendering the PIP document body.
		add_action( 'wc_pip_after_body', array( __CLASS__, 'after_body' ), 10, 4 );

		// Filter items count.
		add_filter( 'wc_pip_order_items_count', array( __CLASS__, 'items_count' ), 1000 );

		// Temporarily add order item data to array.
		add_filter( 'wc_pip_document_table_row_item_data', array( __CLASS__, 'row_item_data' ), 10, 5 );

		// Re-sort PIP table rows so that child items are always below their container.
		add_filter( 'wc_pip_document_table_rows', array( __CLASS__, 'table_rows' ), 52, 5 );

		// Add 'bundled-product' class to pip row classes.
		add_filter( 'wc_pip_document_table_product_class', array( __CLASS__, 'bundled_item_class' ), 10, 4 );

		// Add assembly info to child item meta.
		add_action( 'wc_pip_order_item_meta_end', array( __CLASS__, 'add_assembled_order_item_meta' ), 10, 2 );

		// Ensure bundle container line items are always dislpayed.
		add_filter( 'wc_pip_packing-list_hide_virtual_item', array( __CLASS__, 'hide_item' ), 10, 4 );

		// Prevent child order items from being sorted/categorized.
		add_filter( 'wc_pip_packing-list_list_group_item_as_uncategorized', array( __CLASS__, 'group_bundled_items_as_uncategorized' ), 10, 3 );

		// Add bundled item class CSS rule.
		add_action( 'wc_pip_styles', array( __CLASS__, 'add_styles' ) );

	}

	/**
	 * Rendering a PIP document?
	 *
	 * @since  1.4.0
	 *
	 * @param  string  $type
	 * @return boolean
	 */
	public static function rendering_document( $type = '' ) {
		return ! is_null( self::$document ) && ( '' === $type || $type === self::$document->type );
	}

	/**
	 * Fires off before rendering the PIP document body.
	 *
	 * @since  1.4.0
	 *
	 * @param  string           $type
	 * @param  string           $action
	 * @param  WC_PIP_Document  $document
	 * @param  WC_Order         $order
	 * @return void
	 */
	public static function before_body( $type, $action, $document, $order ) {
		self::$document = $document;
		if ( in_array( $document->type, array( 'packing-list' ) ) ) {
			self::add_filters();
		}
	}

	/**
	 * Fires off after rendering the PIP document body.
	 *
	 * @since  1.4.0
	 *
	 * @param  string           $type
	 * @param  string           $action
	 * @param  WC_PIP_Document  $document
	 * @param  WC_Order         $order
	 * @return void
	 */
	public static function after_body( $type, $action, $document, $order ) {
		if ( in_array( $document->type, array( 'packing-list' ) ) ) {
			self::remove_filters();
		}
		self::$document = null;
	}

	/**
	 * Modify the returned order items and products to return the correct items/weights/values for shipping.
	 *
	 * @since  1.4.0
	 */
	public static function add_filters() {
		add_filter( 'woocommerce_order_get_items', array( WC_Mix_and_Match()->order, 'get_order_items' ), 10, 2 );
		add_filter( 'woocommerce_order_item_product', array( WC_Mix_and_Match()->order, 'get_product_from_item' ), 10, 2 );
	}

	/**
	 * Remove filters above.
	 *
	 * @since  1.4.0
	 */
	public static function remove_filters() {
		remove_filter( 'woocommerce_order_get_items', array( WC_Mix_and_Match()->order, 'get_order_items' ), 10 );
		remove_filter( 'woocommerce_order_item_product', array( WC_Mix_and_Match()->order, 'get_product_from_item' ), 10 );
	}

	/**
	 * Recounts items excluding bundle containers.
	 *
	 * @param  int  $count
	 * @return int
	 */
	public static function items_count( $count ) {

		if ( false === self::$recounting_items && self::$document ) {
			self::$recounting_items = true;
			$count = self::$document->get_items_count();
			self::$recounting_items = false;
		}

		return $count;
	}

	/**
	 * Temporarily add order item data to array.
	 *
	 * @param  array       $item_data
	 * @param  array       $item
	 * @param  WC_Product  $product
	 * @param  string      $order_id
	 * @param  string      $type
	 * @return array
	 */
	public static function row_item_data( $item_data, $item, $product, $order_id, $type ) {
		$item_data[ 'wc_mnm_item_data' ] = $item;
		return $item_data;
	}

	/**
	 * Re-sort PIP table rows so that child items are always below their container.
	 *
	 * @param  array   $table_rows
	 * @param  array   $items
	 * @param  string  $order_id
	 * @param  string  $type
	 * @return array
	 */
	public static function table_rows( $table_rows, $items, $order_id, $type, $pip_document = null ) {

		$order = is_null( $pip_document ) ? wc_get_order( $order_id ) : $pip_document->order;

		$filtered_table_rows = array();

		if ( ! empty( $table_rows ) ) {

			foreach ( $table_rows as $table_row_key => $table_row_data ) {

				$filtered_table_rows[ $table_row_key ] = $table_row_data;

				if ( empty( $table_row_data[ 'items' ] ) ) {
					continue;
				}

				$sorted_rows = array();

				foreach ( $table_row_data[ 'items' ] as $row_item ) {

					if ( isset( $row_item[ 'wc_mnm_item_data' ] ) && wc_mnm_is_container_order_item( $row_item[ 'wc_mnm_item_data' ] ) ) {

						$virtual_parent = false;

						// Virtual parent items should be hidden in packing lists when the corresponding PIP option is active.
						if ( self::$document && 'packing-list' === self::$document->type ) {
							if ( 'yes' === $row_item[ 'wc_mnm_item_data' ][ 'mnm_container_item_virtual' ] ) {
								$virtual_parent = true;
							}
						}

						if ( $virtual_parent ) {
							$row_item[ 'quantity' ] = str_replace( 'class="quantity', 'class="quantity virtual-container', $row_item[ 'quantity' ] );
							$row_item[ 'weight' ] = str_replace( 'class="weight', 'class="weight virtual-container', $row_item[ 'weight' ] );
						}

						$sorted_rows[] = $row_item;

						$children = wc_mnm_get_child_order_items( $row_item[ 'wc_mnm_item_data' ], $order );

						// Look for its children in all table rows and bring them over in the original order.
						if ( ! empty( $children ) ) {
							foreach ( $children as $child_order_item ) {

								if ( empty( $child_order_item[ 'mnm_container' ] ) ) {
									continue;
								}

								// Look for the child in all table rows and bring it over.
								foreach ( $table_rows as $table_row_key_inner => $table_row_data_inner ) {
									foreach ( $table_row_data_inner[ 'items' ] as $index => $row_item_inner ) {

										$is_child = false;

										if ( isset( $row_item_inner[ 'wc_mnm_item_data' ] ) && isset( $row_item_inner[ 'wc_mnm_item_data' ][ 'mnm_container' ] ) ) {
											$is_child = $row_item_inner[ 'wc_mnm_item_data' ][ 'mnm_container' ] === $child_order_item[ 'mnm_container' ];
										}

										if ( $is_child ) {
											$sorted_rows[] = $row_item_inner;

											// Once sorted, don't sort again.
											unset( $table_rows[$table_row_key_inner]['items'][$index] );
										}
									}
								}
							}
						}

					} else {

						// Do not copy child items (will be looked up by their parents).
						if ( ! isset( $row_item[ 'wc_mnm_item_data' ] ) || ! wc_mnm_is_child_order_item( $row_item[ 'wc_mnm_item_data' ] ) ) {
							$sorted_rows[] = $row_item;
						}
					}
				}

				// Unset our (now redundant) data.
				foreach ( $sorted_rows as $sorted_row_item => $sorted_row_item_data ) {
					if ( isset( $sorted_row_item_data[ 'wc_mnm_item_data' ] ) ) {
						unset( $sorted_rows[ $sorted_row_item ][ 'wc_mnm_item_data' ]  );
					}
				}

				$filtered_table_rows[ $table_row_key ][ 'items' ] = $sorted_rows;
			}

			// Ensure empty categories are not displayed at all.
			foreach ( $filtered_table_rows as $table_row_key => $table_row_data ) {
				if ( empty( $table_row_data[ 'items' ] ) ) {
					if ( isset( $table_row_data[ 'headings' ] ) && ! isset( $table_row_data[ 'headings' ][ 'order-number' ] ) ) {
						unset( $filtered_table_rows[ $table_row_key ] );
					}
				}
			}
		}

		return $filtered_table_rows;
	}

	/**
	 * Add 'bundled-product' class to pip row classes.
	 *
	 * @param  array       $classes
	 * @param  WC_Product  $product
	 * @param  array       $item
	 * @param  string      $type
	 * @return array
	 */
	public static function bundled_item_class( $classes, $product, $item, $type ) {

		if ( wc_mnm_get_order_item_container( $item ) ) {
			$classes[] = 'bundled-product';
		}

		return $classes;
	}

	/**
	 * Add "assembled" item meta to pick-lists.
	 *
	 * @since  1.4.0
	 *
	 * @param  int            $item_id
	 * @param  WC_Order_Item  $item
	 */
	public static function add_assembled_order_item_meta( $item_id, $item ) {

		if ( self::$document && 'pick-list' === self::$document->type ) {

			if ( $parent_item = wc_mnm_get_order_item_container( $item ) ) {

				// Is it an assembled item?
				if ( 'no' === $item->get_meta( '_mnm_item_needs_shipping', true ) ) {

					$flat = false;

					if ( has_filter( 'wc_pip_document_table_row_item_meta_flat' ) ) {
						$product = $item->get_product();
						$flat    = apply_filters( 'wc_pip_document_table_row_item_meta_flat', $flat, $product, $item_id, $item, self::$document->type, self::$document->order );
					}

					if ( $flat ) {
						$assembled_item_meta_html = wp_kses_post( __( 'Packaged in:', 'woocommerce-mix-and-match-products' ) . ' ' .  wpautop( $parent_item->get_name() ) );
					} else {
						$assembled_item_meta_html = '<dl class="variation assembled"><dt>' . __( 'Packaged in:', 'woocommerce-mix-and-match-products' ) . '</dt><dd>' . $parent_item->get_name() . '</dd></dl>';
					}

					echo apply_filters( 'wc_pip_pick-list_order_item_meta_assembled_in_bundle', $assembled_item_meta_html, $item_id, $item, $parent_item );
				}
			}
		}
	}

	/**
	 * Ensure bundle container line items are always displayed, otherwise we will not be able to collect their children in 'table_rows'.
	 *
	 * @param  boolean     $hide
	 * @param  WC_Product  $product
	 * @param  array       $order_item
	 * @param  WC_Order    $order
	 * @return boolean
	 */
	public static function hide_item( $hide, $product, $order_item, $order ) {

		if ( wc_mnm_is_container_order_item( $order_item ) ) {

			$product = wc_get_product( $order_item->get_product_id() );

			if ( ! $product->needs_shipping() ) {

				if ( self::$recounting_items ) {
					$hide = true;
				} else {
					$hide = false;
				}

				$order_item->add_meta_data( '_mnm_container_item_virtual', 'yes', true );
			}

		} elseif ( wc_mnm_is_child_order_item( $order_item, $order ) ) {
			if ( self::$document && 'packing-list' === self::$document->type && 'no' === $order_item->get_meta( '_mnm_item_needs_shipping', true ) ) {
				$hide = true;
			}
		}

		return $hide;
	}

	/**
	 * Prevent child order items from being sorted/categorized.
	 *
	 * @param  boolean   $uncategorize
	 * @param  array     $order_item
	 * @param  WC_Order  $order
	 * @return boolean
	 */
	public static function group_bundled_items_as_uncategorized( $uncategorize, $order_item, $order ) {

		if ( wc_mnm_is_child_order_item( $order_item, $order ) ) {
			$uncategorize = true;
		}

		return $uncategorize;
	}

	/**
	 * Add bundled item class CSS rule.
	 * @return  void
	 */
	public static function add_styles() {
		?>
		.quantity .virtual-container, .weight .virtual-container {
			display: none;
		}
		.quantity .assembled, .weight .assembled {
			display: none;
		}
		.product .bundled-product {
			padding-left: 2.5em;
		}
		.bundled-product-subtotal {
			font-size: 0.875em;
			padding-right: 2em;
			display: block;
		}
		.product-bundle.product-meta dl {
			margin-top: 0.5em;
		}
		.bundled-product dl.variation.assembled {
			margin-top: 0.5em;
		}
		<?php
	}
}

WC_MNM_PIP_Compatibility::init();
