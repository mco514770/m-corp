<?php

namespace WooCommerce\Square\Gateway;

defined( 'ABSPATH' ) || exit;

use Square\Models\RetrieveGiftCardFromGANResponse;
use WooCommerce\Square\Framework\Square_Helper;
use WooCommerce\Square\Plugin;
use WooCommerce\Square\Utilities\Money_Utility;

class Gift_Card {
	/**
	 * @var \WooCommerce\Square\Gateway $gateway
	 */
	public $gateway = null;

	/**
	 * Checks if Gift Card is enabled.
	 *
	 * @since 3.7.0
	 * @return bool
	 */
	public function is_gift_card_enabled() {
		return 'yes' === $this->gateway->get_option( 'enable_gift_cards', 'no' );
	}

	/**
	 * Setup the Gift Card class
	 *
	 * @param \WooCommerce\Square\Gateway $gateway The payment gateway object.
	 * @since 3.7.0
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;

		add_action( 'wp', array( $this, 'init_gift_cards' ) );
		add_action( 'wp_ajax_check_gift_card_balance', array( $this, 'apply_gift_card' ) );
		add_action( 'wp_ajax_nopriv_check_gift_card_balance', array( $this, 'apply_gift_card' ) );
		add_action( 'wp_ajax_gift_card_remove', array( $this, 'remove_gift_card' ) );
		add_action( 'wp_ajax_nopriv_gift_card_remove', array( $this, 'remove_gift_card' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_gift_card_fragments' ) );
		add_filter( 'woocommerce_checkout_order_processed', array( $this, 'delete_sessions' ) );
	}

	/**
	 * Loads resources required for the Gift Card feature.
	 *
	 * @since 3.7.0
	 */
	public function init_gift_cards() {
		$cart_has_subscription = false;

		if ( class_exists( '\WC_Subscriptions_Cart' ) ) {
			$cart_has_subscription = \WC_Subscriptions_Cart::cart_contains_subscription();
		}

		if ( 'yes' === $this->gateway->get_option( 'enabled', 'no' ) && $this->is_gift_card_enabled() && ! $cart_has_subscription ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}

	/**
	 * Enqueue scripts ans=d styles required for Gift Cards.
	 *
	 * @since 3.7.0
	 */
	public function enqueue_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		/**
		 * Hook to filter JS args for Gift cards.
		 *
		 * @since 3.7.0
		 * @param array Array of args.
		 */
		$args = apply_filters(
			'wc_square_gift_card_js_args',
			array(
				'applicationId'       => $this->gateway->get_application_id(),
				'locationId'          => wc_square()->get_settings_handler()->get_location_id(),
				'gatewayId'           => $this->gateway->get_id(),
				'gatewayIdDasherized' => $this->gateway->get_id_dasherized(),
				'generalError'        => __( 'An error occurred, please try again or try an alternate form of payment.', 'woocommerce-square' ),
				'ajaxUrl'             => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
				'applyGiftCardNonce'  => wp_create_nonce( 'wc-square-apply-gift-card' ),
				'logging_enabled'     => $this->gateway->debug_log(),
				'orderId'             => absint( get_query_var( 'order-pay' ) ),
				'removeGiftCardText'  => esc_html__( 'Remove Gift Card?', 'woocommerce-square' ),
				'applyDiffGiftCard'   => esc_html__( 'Try another Gift Card?', 'woocommerce-square' ),
			)
		);

		wp_enqueue_script(
			'wc-square-gift-card',
			$this->gateway->get_plugin()->get_plugin_url() . '/assets/js/frontend/gift-card.min.js',
			array( 'jquery' ),
			Plugin::VERSION,
			true
		);

		wc_enqueue_js( sprintf( 'window.wc_square_gift_card_handler = new WC_Square_Gift_Card_Handler( %s );', wp_json_encode( $args ) ) );

		wp_enqueue_style(
			'wc-square-gift-card',
			$this->gateway->get_plugin()->get_plugin_url() . '/assets/css/frontend/wc-square-gift-card.min.css',
			array(),
			Plugin::VERSION
		);
	}

	/**
	 * Filters order review fragments to load Gift Card HTML.
	 *
	 * @since 3.7.0
	 *
	 * @param array $fragments Array of fragments.
	 */
	public function add_gift_card_fragments( $fragments ) {
		$payment_token  = WC()->session->woocommerce_square_gift_card_payment_token;
		$is_sandbox     = wc_square()->get_settings_handler()->is_sandbox();
		$cart_total     = WC()->cart->total;
		$gift_card      = null;
		$balance_amount = 0;
		$response       = array(
			'is_error' => false,
			'message'  => '',
		);

		if ( $is_sandbox ) {
			// The card allowed for testing with the Sandbox account has fund of $1.
			if ( 1 < $cart_total ) {
				$response['is_error'] = false;
				$response['message']  = esc_html__( 'Gift Card has insufficient funds.', 'woocommerce-square' );
			} else {
				$response['is_error'] = true;
				$response['message']  = esc_html__( 'Gift Card applied!', 'woocommerce-square' );
				$balance_amount       = 1 - $cart_total;
			}
		} else {
			if ( $payment_token ) {
				$response       = $this->gateway->get_api()->retrieve_gift_card( $payment_token );
				$gift_card_data = $response->get_data();

				if ( $gift_card_data instanceof \Square\Models\RetrieveGiftCardFromNonceResponse ) {
					$gift_card      = $gift_card_data->getGiftCard();
					$balance_money  = $gift_card->getBalanceMoney();
					$balance_amount = (float) Square_Helper::number_format( Money_Utility::cents_to_float( $balance_money->getAmount() ) );

					if ( $balance_amount < $cart_total ) {
						$response['message'] = esc_html__( 'Gift Card has insufficient funds.', 'woocommerce-square' );
					} else {
						$response['message'] = esc_html__( 'Gift Card applied!', 'woocommerce-square' );
					}
				}
			} else {
				$response['is_error'] = true;
				$response['message']  = esc_html__( 'Gift Card payment token missing.', 'woocommerce-square' );
			}
		}

		ob_start();
		?>
		<div id="square-gift-card-wrapper">
			<div id="square-gift-card-application" <?php echo $payment_token ? 'style="display: none;"' : 'style="display: flex;"'; ?>>
				<div id="square-gift-card-title"><?php esc_html_e( 'Have a Square Gift Card?', 'woocommerce-square' ); ?></div>
				<div id="square-gift-card-fields-input"></div>

				<div id="square-gift-card-apply-button-wrapper">
					<button type="button" id="square-gift-card-apply-btn">
						<?php esc_html_e( 'Apply', 'woocommerce-square' ); ?>
					</button>
				</div>
			</div>

			<div id="square-gift-card-response" style="<?php echo $payment_token ? 'display: flex;' : ''; ?>">
				<div id="square-gift-card-balance-info">
					<?php
					printf(
						/* Translators: %1$s - Gift card last 4 digits, %2$s balance amount. */
						__( 'Your gift card ending in %1$s has a remaining balance of %2$s.', 'woocommerce-square' ),
						$gift_card ? esc_html( substr( $gift_card->getGan(), -4 ) ) : '0000',
						wp_kses_post(
							wc_price(
								$balance_amount,
								array( 'currency' => get_woocommerce_currency() )
							)
						)
					);
					?>
				</div>
				<div id="square-gift-card-funds-message" role="alert" <?php echo $response['is_error'] ? 'class="woocommerce-message"' : 'class="woocommerce-error"'; ?>>
					<?php echo esc_html( $response['message'] ); ?>
				</div>
				<a id="square-gift-card-remove" href="#">
					<?php
					if ( $response['is_error'] ) {
						esc_html_e( 'Remove Gift Card?', 'woocommerce-square' );
					} else {
						esc_html_e( 'Try another Gift Card?', 'woocommerce-square' );
					}
					?>
				</a>
			</div>

			<?php if ( $response['is_error'] ) : ?>
				<div id="square-gift-card-hidden-fields">
					<input id="square-gift-card-payment-nonce" name="square-gift-card-payment-nonce" type="hidden" value="<?php echo esc_attr( $payment_token ); ?>" />
					<input id="square-gift-card-payment-method" name="payment_method" value="square_credit_card" type="hidden" />
				</div>
			<?php endif; ?>
		</div>
		<?php

		if ( $payment_token ) {
			ob_start();

			if ( WC()->cart->needs_payment() ) {
				$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
				WC()->payment_gateways()->set_current_gateway( $available_gateways );
			} else {
				$available_gateways = array();
			}

			wc_get_template(
				'Templates/payment.php',
				array(
					'checkout'           => WC()->checkout(),
					'available_gateways' => $available_gateways,
					// PHPCS ignored as it is a Woo core hook.
					'order_button_text'  => apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce-square' ) ), // phpcs:ignore
					'is_error'           => $response['is_error'],
				),
				'',
				WC_SQUARE_PLUGIN_PATH . 'includes/Gateway/'
			);

			$payment_methods = ob_get_clean();

			$fragments['.woocommerce-checkout-payment'] = $payment_methods;
		}

		$fragments['.woocommerce-square-gift-card-html'] = ob_get_clean();

		return $fragments;
	}

	/**
	 * Ajax callback to apply a Gift Card.
	 *
	 * @since 3.7.0
	 */
	public function apply_gift_card() {
		check_ajax_referer( 'wc-square-apply-gift-card', 'security' );

		$payment_token = isset( $_POST['token'] ) ? wc_clean( wp_unslash( $_POST['token'] ) ) : false;

		if ( ! $payment_token ) {
			wp_send_json_error();
		}

		WC()->session->set( 'woocommerce_square_gift_card_payment_token', $payment_token );

		wp_send_json_success();
	}

	/**
	 * Ajax callback to remove Gift Card.
	 *
	 * @since 3.7.0
	 */
	public function remove_gift_card() {
		WC()->session->set( 'woocommerce_square_gift_card_payment_token', null );

		wp_send_json_success();
	}

	/**
	 * Delete Gift card session after order complete.
	 *
	 * @since 3.7.0
	 */
	public function delete_sessions() {
		WC()->session->set( 'woocommerce_square_gift_card_payment_token', null );
	}
}
