<?php

namespace WooCommerce\Square\Framework\PaymentGateway\Admin;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway_Plugin;
use WooCommerce\Square\Framework\PaymentGateway\Payment_Gateway;
use WooCommerce\Square\Framework\Square_Helper;
use WooCommerce\Square\Plugin;
use WooCommerce\Square\Framework\Compatibility\Order_Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * Handle the admin order screens.
 *
 * @since 3.0.0
 */
class Payment_Gateway_Admin_Order {


	/** @var Payment_Gateway_Plugin the plugin instance **/
	protected $plugin;


	/**
	 * Constructs the class.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Plugin The plugin instance
	 */
	public function __construct( Payment_Gateway_Plugin $plugin ) {

		$this->plugin = $plugin;

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// capture feature
		if ( $this->get_plugin()->supports_capture_charge() ) {

			add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'add_capture_button' ) );

			add_action( 'wp_ajax_wc_square_capture_charge', array( $this, 'ajax_process_capture' ) );

			// bulk capture order action
			add_action( 'admin_footer-edit.php', array( $this, 'maybe_add_capture_charge_bulk_order_action' ) );
			add_action( 'load-edit.php',         array( $this, 'process_capture_charge_bulk_order_action' ) );
		}
	}


	/**
	 * Enqueues the scripts and styles.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param string $hook_suffix page hook suffix
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Order screen assets
		if ( 'shop_order' === get_post_type() ) {

			// Edit Order screen assets
			if ( 'post.php' === $hook_suffix ) {

				$order = wc_get_order( Square_Helper::get_request( 'post' ) );

				if ( ! $order ) {
					return;
				}

				// bail if the order payment method doesn't belong to this plugin
				if ( ! $this->get_order_gateway( $order ) ) {
					return;
				}

				$this->enqueue_edit_order_assets( $order );
			}
		}
	}


	/**
	 * Enqueues the assets for the Edit Order screen.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 */
	protected function enqueue_edit_order_assets( \WC_Order $order ) {

		wp_enqueue_script( 'payment-gateway-admin-order', $this->get_plugin()->get_plugin_url() . '/assets/js/admin/wc-square-payment-gateway-admin-order.min.js', array( 'jquery' ), Plugin::VERSION, true );

		wp_localize_script( 'payment-gateway-admin-order', 'sv_wc_payment_gateway_admin_order', array(
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'gateway_id'     => Order_Compatibility::get_prop( $order, 'payment_method' ),
			'order_id'       => Order_Compatibility::get_prop( $order, 'id' ),
			'capture_ays'    => esc_html__( 'Are you sure you wish to process this capture? The action cannot be undone.', 'woocommerce-square' ),
			'capture_action' => 'wc_square_capture_charge',
			'capture_nonce'  => wp_create_nonce( 'wc_square_capture_charge' ),
			'capture_error'  => esc_html__( 'Something went wrong, and the capture could no be completed. Please try again.', 'woocommerce-square' ),
		) );

		wp_enqueue_style( 'payment-gateway-admin-order', $this->get_plugin()->get_plugin_url() . '/assets/css/admin/wc-square-payment-gateway-admin-order.min.css', Plugin::VERSION );
	}


	/** Capture Charge Feature ******************************************************/


	/**
	 * Adds 'Capture charge' to the Orders screen bulk action select.
	 *
	 * @since 3.0.0
	 */
	public function maybe_add_capture_charge_bulk_order_action() {
		global $post_type, $post_status;

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		if ( $post_type === 'shop_order' && $post_status !== 'trash' ) {

			$can_capture_charge = false;

			// ensure at least one gateway supports capturing charge
			foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

				// ensure that it supports captures
				if ( $gateway->supports_credit_card_capture() ) {

					$can_capture_charge = true;
					break;
				}
			}

			if ( $can_capture_charge ) {

				?>
					<script type="text/javascript">
						jQuery( document ).ready( function ( $ ) {
							if ( 0 == $( 'select[name^=action] option[value=wc_capture_charge]' ).size() ) {
								$( 'select[name^=action]' ).append(
									$( '<option>' ).val( '<?php echo esc_js( 'wc_capture_charge' ); ?>' ).text( '<?php esc_html_e( 'Capture Charge', 'woocommerce-square' ); ?>' )
								);
							}
						});
					</script>
				<?php
			}
		}
	}


	/**
	 * Processes the 'Capture Charge' custom bulk action.
	 *
	 * @since 3.0.0
	 */
	public function process_capture_charge_bulk_order_action() {
		global $typenow;

		if ( 'shop_order' === $typenow ) {

			// get the action
			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			// bail if not processing a capture
			if ( 'wc_capture_charge' !== $action ) {
				return;
			}

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				return;
			}

			// security check
			check_admin_referer( 'bulk-posts' );

			// make sure order IDs are submitted
			if ( isset( $_REQUEST['post'] ) ) {
				$order_ids = array_map( 'absint', $_REQUEST['post'] );
			}

			// return if there are no orders to export
			if ( empty( $order_ids ) ) {
				return;
			}

			// give ourselves an unlimited timeout if possible
			@set_time_limit( 0 );

			foreach ( $order_ids as $order_id ) {

				$order = wc_get_order( $order_id );

				if ( $order && ( $gateway = $this->get_order_gateway( $order ) ) ) {
					$gateway->get_capture_handler()->maybe_perform_capture( $order );
				}
			}
		}
	}

	/**
	 * Adds the capture charge button to the order UI.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 */
	public function add_capture_button( $order ) {

		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$post_type = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_type( $order->get_id() );
		} else {
			$post_type = get_post_type( Order_Compatibility::get_prop( $order, 'id' ) );
		}

		// only display the button for core orders
		if ( ! $order instanceof \WC_Order || 'shop_order' !== $post_type ) {
			return;
		}

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return;
		}

		if ( ! $gateway->get_capture_handler()->is_order_ready_for_capture( $order ) ) {
			return;
		}

		$tooltip = '';
		$classes = array(
			'button',
			'wc-square-payment-gateway-capture',
			'wc-' . $gateway->get_id_dasherized() . '-capture',
		);

		// indicate if the partial-capture UI can be shown
		if ( $gateway->supports_credit_card_partial_capture() && $gateway->is_partial_capture_enabled() ) {
			$classes[] = 'partial-capture';
		} elseif ( $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {
			$classes[] = 'button-primary';
		}

		// ensure that the authorization is still valid for capture
		if ( ! $gateway->get_capture_handler()->order_can_be_captured( $order ) ) {

			$classes[] = 'tips disabled';

			// add some tooltip wording explaining why this cannot be captured
			if ( $gateway->get_capture_handler()->is_order_fully_captured( $order ) ) {
				$tooltip = esc_html__( 'This charge has been fully captured.', 'woocommerce-square' );
			} elseif ( $gateway->get_order_meta( $order, 'trans_date' ) && $gateway->get_capture_handler()->has_order_authorization_expired( $order ) ) {
				$tooltip = esc_html__( 'This charge can no longer be captured.', 'woocommerce-square' );
			} else {
				$tooltip = esc_html__( 'This charge cannot be captured.', 'woocommerce-square' );
			}
		}

		?>

		<button type="button" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" <?php echo ( $tooltip ) ? 'data-tip="' . esc_html( $tooltip ) . '"' : ''; ?>><?php esc_html_e( 'Capture Charge', 'woocommerce-square' ); ?></button>

		<?php

		// add the partial capture UI HTML
		if ( $gateway->supports_credit_card_partial_capture() && $gateway->is_partial_capture_enabled() ) {
			$this->output_partial_capture_html( $order, $gateway );
		}
	}


	/**
	 * Outputs the partial capture UI HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param Payment_Gateway $gateway gateway instance
	 */
	protected function output_partial_capture_html( \WC_Order $order, Payment_Gateway $gateway ) {

		$authorization_total = $gateway->get_capture_handler()->get_order_authorization_amount( $order );
		$total_captured      = $gateway->get_order_meta( $order, 'capture_total' );
		$remaining_total     = Square_Helper::number_format( (float) $order->get_total() - (float) $total_captured );

		include( $this->get_plugin()->get_payment_gateway_framework_path() . '/Admin/views/html-order-partial-capture.php' );
	}


	/**
	 * Processes a capture via AJAX.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function ajax_process_capture() {

		check_ajax_referer( 'wc_square_capture_charge', 'nonce' );

		$gateway_id = Square_Helper::get_request( 'gateway_id' );

		if ( ! $this->get_plugin()->has_gateway( $gateway_id ) ) {
			die();
		}

		$gateway = $this->get_plugin()->get_gateway( $gateway_id );

		try {

			$order_id = Square_Helper::get_request( 'order_id' );
			$order    = wc_get_order( $order_id );

			if ( ! $order ) {
				throw new \Exception( 'Invalid order ID' );
			}

			if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
				throw new \Exception( 'Invalid permissions' );
			}

			if ( Order_Compatibility::get_prop( $order, 'payment_method' ) !== $gateway->get_id() ) {
				throw new \Exception( 'Invalid payment method' );
			}

			$amount_captured = (float) $gateway->get_order_meta( $order, 'capture_total' );

			if ( Square_Helper::get_request( 'amount' ) ) {
				$amount = (float) Square_Helper::get_request( 'amount' );
			} else {
				$amount = $order->get_total();
			}

			$result = $gateway->get_capture_handler()->perform_capture( $order, $amount );

			if ( empty( $result['success'] ) ) {
				throw new \Exception( $result['message'] );
			}

			wp_send_json_success( array(
				'message' => html_entity_decode( wp_strip_all_tags( $result['message'] ) ), // ensure any HTML tags are removed and the currency symbol entity is decoded
			) );

		} catch ( \Exception $e ) {

			wp_send_json_error( array(
				'message' => $e->getMessage(),
			) );
		}
	}


	/**
	 * Gets the gateway object from an order.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return Payment_Gateway
	 */
	protected function get_order_gateway( \WC_Order $order ) {

		$capture_gateway = null;

		$payment_method = Order_Compatibility::get_prop( $order, 'payment_method' );

		if ( $this->get_plugin()->has_gateway( $payment_method ) ) {

			$gateway = $this->get_plugin()->get_gateway( $payment_method );

			// ensure that it supports captures
			if ( $gateway->supports_credit_card_capture() ) {
				$capture_gateway = $gateway;
			}
		}

		return $capture_gateway;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 3.0.0
	 *
	 * @return Payment_Gateway_Plugin the plugin instance
	 */
	protected function get_plugin() {

		return $this->plugin;
	}

	/**
	 * Captures an order on status change to a "paid" status.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @param int $order_id order ID
	 * @param string $old_status status being changed
	 * @param string $new_status new order status
	 */
	public function maybe_capture_paid_order( $order_id, $old_status, $new_status ) {

		wc_deprecated_function( __METHOD__, '3.0.0' );
	}


	/**
	 * Determines if an order is ready for capture.
	 *
	 * @since 3.0.0
	 *
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	protected function is_order_ready_for_capture( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '3.0.0' );

		$gateway = $this->get_order_gateway( $order );

		if ( ! $gateway ) {
			return false;
		}

		return $gateway->get_capture_handler()->is_order_ready_for_capture( $order );
	}
}
