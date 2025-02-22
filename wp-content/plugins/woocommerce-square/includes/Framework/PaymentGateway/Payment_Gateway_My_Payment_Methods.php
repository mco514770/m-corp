<?php

namespace WooCommerce\Square\Framework\PaymentGateway;
use WooCommerce\Square\Plugin;
use WooCommerce\Square\Framework\PaymentGateway\PaymentTokens\Payment_Gateway_Payment_Token;
use WooCommerce\Square\Framework\Square_Helper;

defined( 'ABSPATH' ) or exit;

/**
 * My Payment Methods Class
 *
 * Renders the My Payment Methods table on the My Account page and handles
 * any associated actions (deleting a payment method, etc)
 *
 * @since 3.0.0
 */
class Payment_Gateway_My_Payment_Methods {


	/** @var Payment_Gateway_Plugin */
	protected $plugin;

	/** @var Payment_Gateway_Payment_Token[] array of token objects */
	protected $tokens;

	/** @var Payment_Gateway_Payment_Token[] array of token objects */
	protected $credit_card_tokens;

	/** @var Payment_Gateway_Payment_Token[] array of token objects */
	protected $echeck_tokens;

	/** @var bool true if there are tokens */
	protected $has_tokens;


	/**
	 * Setup Class
	 *
	 * Note: this constructor executes during the `wp` action
	 *
	 * @param Payment_Gateway_Plugin $plugin gateway plugin
	 * @since 3.0.0
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		add_action( 'wp', array( $this, 'init' ) );

		// save a payment method via AJAX
		add_action( 'wp_ajax_wc_square_save_payment_method', array( $this, 'ajax_save_payment_method' ) );
	}


	/**
	 * Initializes the My Payment Methods table
	 *
	 * @since 3.0.0
	 */
	public function init() {

		if ( ! $this->is_payment_methods_page() ) {
			return;
		}

		// load all tokens for the given plugin
		$this->load_tokens();

		// styles/scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_styles_scripts' ) );

		// render the My Payment Methods section
		// TODO: merge our payment methods data into the core table and remove this in a future version {CW 2016-05-17}
		add_action( 'woocommerce_after_account_payment_methods', array( $this, 'render' ) );
		add_action( 'woocommerce_after_account_payment_methods', array( $this, 'render_js' ) );

		// handle payment method deletion, etc.
		$this->handle_payment_method_actions();
	}


	/**
	 * Enqueue frontend CSS/JS
	 *
	 * @since 3.0.0
	 */
	public function maybe_enqueue_styles_scripts() {

		$handle = 'wc-square-payment-gateway-my-payment-methods';

		// if there are tokens to display, add the custom JS
		if ( $this->has_tokens ) {

			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );

			wp_enqueue_style( $handle, $this->get_plugin()->get_plugin_url() . '/assets/css/frontend/' . $handle . '.min.css', array( 'dashicons' ), Plugin::VERSION );

			wp_enqueue_script( $handle, $this->get_plugin()->get_plugin_url() . '/assets/js/frontend/' . $handle . '.min.js', array( 'jquery-tiptip', 'jquery' ), Plugin::VERSION );
		}
	}


	/**
	 * Get the the available tokens for each plugin gateway and combine them
	 *
	 * Tokens are also separated into Credit Card and eCheck-specific class members
	 * for convenience.
	 *
	 * @since 3.0.0
	 */
	protected function load_tokens() {

		if ( ! empty( $this->tokens ) ) {
			return $this->tokens;
		}

		$this->credit_card_tokens = $this->echeck_tokens = array();

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( ! $gateway->is_available() || ! ( $gateway->supports_tokenization() && $gateway->tokenization_enabled() ) ) {
				continue;
			}

			foreach ( $gateway->get_payment_tokens_handler()->get_tokens( get_current_user_id() ) as $token ) {

				// prevent duplicates, as some gateways will return all tokens in each each gateway
				if ( isset( $this->credit_card_tokens[ $token->get_id() ] ) ||  isset( $this->echeck_tokens[ $token->get_id() ] ) ) {
					continue;
				}

				if ( $token->is_credit_card() ) {

					$this->credit_card_tokens[ $token->get_id() ] = $token;

				}
			}
		}

		// we don't use array_merge here since the indexes could be numeric
		// and cause the indexes to be reset
		$this->tokens = $this->credit_card_tokens + $this->echeck_tokens;

		$this->has_tokens = ! empty( $this->tokens );

		return $this->tokens;
	}


	/**
	 * Render the payment methods table.
	 *
	 * @since 3.0.0
	 */
	public function render() {

		if ( $this->has_tokens ) {

			/**
			 * Before My Payment Methods Table Action.
			 *
			 * Fired before the My Payment Methods table HTML is rendered.
			 *
			 * @since 3.0.0
			 *
			 * @param Payment_Gateway_My_Payment_Methods $this instance
			 */
			do_action( 'wc_square_before_my_payment_method_table', $this );

			echo $this->get_table_html();

			/**
			 * After My Payment Methods Table Action.
			 *
			 * Fired after the My Payment Methods table HTML is rendered.
			 *
			 * @since 3.0.0
			 *
			 * @param Payment_Gateway_My_Payment_Methods $this instance
			 */
			do_action( 'wc_square_after_my_payment_method_table', $this );

		}
	}


	/**
	 * Renders the JavaScript.
	 *
	 * @since 3.0.0
	 */
	public function render_js() {

		$args = array(
			'id'              => 'square',
			'slug'            => $this->get_plugin()->get_id_dasherized(),
			'has_core_tokens' => (bool) wc_get_customer_saved_methods_list( get_current_user_id() ),
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'      => wp_create_nonce( 'wc_square_save_payment_method' ),
			'i18n'            => array(
				'edit_button'   => esc_html__( 'Edit', 'woocommerce-square' ),
				'cancel_button' => esc_html__( 'Cancel', 'woocommerce-square' ),
				'save_error'    => esc_html__( 'Oops, there was an error updating your payment method. Please try again.', 'woocommerce-square' ),
				'delete_ays'    => esc_html__( 'Are you sure you want to delete this payment method?', 'woocommerce-square' ),
			),
		);

		/**
		 * Filters the payment gateway payment methods JavaScript args.
		 *
		 * @since 3.0.0
		 *
		 * @param array $args arguments
		 * @param Payment_Gateway_My_Payment_Methods $handler payment methods handler
		 */
		$args = apply_filters( 'wc_payment_gateway_square_payment_methods_js_args', $args, $this );

		wc_enqueue_js( sprintf(
			'window.wc_%1$s_payment_methods_handler = new %2$s( %3$s );',
			esc_js( 'square' ),
			esc_js( $this->get_js_handler_class() ),
			wp_json_encode( $args )
		) );
	}


	/**
	 * Gets the JS handler class name.
	 *
	 * Plugins can override this for their own JS implementations.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_js_handler_class() {

		return 'Square_Payment_Methods_Handler';
	}

	/**
	 * Return the table HTML
	 *
	 * @since 3.0.0
	 * @return string table HTML
	 */
	public function get_table_html() {

		$html = sprintf( '<table class="shop_table shop_table_responsive sv-wc-payment-gateway-my-payment-methods-table wc-%s-my-payment-methods">', sanitize_html_class( $this->get_plugin()->get_id_dasherized() ) );

		$html .= $this->get_table_head_html();

		$html .= $this->get_table_body_html();

		$html .= '</table>';

		/**
		 * My Payment Methods Table HTML Filter.
		 *
		 * Allow actors to modify the table HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html table HTML
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_html', $html, $this );
	}


	/**
	 * Return the table head HTML
	 *
	 * @since 3.0.0
	 * @return string table thead HTML
	 */
	protected function get_table_head_html() {

		$html = '<thead><tr>';

		foreach ( $this->get_table_headers() as $key => $title ) {
			$html .= sprintf( '<th class="sv-wc-payment-gateway-my-payment-method-table-header sv-wc-payment-gateway-payment-method-header-%1$s wc-%2$s-payment-method-%1$s"><span class="nobr">%3$s</span></th>', sanitize_html_class( $key ), sanitize_html_class( $this->get_plugin()->get_id_dasherized() ), esc_html( $title ) );
		}

		$html .= '</tr></thead>';

		/**
		 * My Payment Methods Table Head HTML Filter.
		 *
		 * Allow actors to modify the table head HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html table head HTML
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_head_html', $html, $this );
	}


	/**
	 * Return the table headers
	 *
	 * @since 3.0.0
	 * @return array of table headers in key => Title format
	 */
	protected function get_table_headers() {

		$headers = array(
			'title'   => __( 'Method', 'woocommerce-square' ),
			'details' => __( 'Details', 'woocommerce-square' ),
			'expiry'  => __( 'Expires', 'woocommerce-square' ),
			'default' => __( 'Default?', 'woocommerce-square' ),
			'actions' => __( 'Actions', 'woocommerce-square' ),
		);

		/**
		 * My Payment Methods Table Headers Filter.
		 *
		 * Allow actors to modify the table headers.
		 *
		 * @since 3.0.0
		 * @param array $headers table headers {
		 *     @type string $title
		 *     @type string $expiry
		 *     @type string $actions
		 * }
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_headers', $headers, $this );
	}


	/**
	 * Return the table body HTML
	 *
	 * @since 3.0.0
	 * @return string table tbody HTML
	 */
	protected function get_table_body_html() {

		$html = '<tbody>';

		if ( $this->credit_card_tokens && $this->echeck_tokens ) {

			$html .= sprintf(
				'<tr class="sv-wc-payment-gateway-my-payment-methods-type-divider wc-%s-my-payment-methods-type-divider"><td colspan="%d">%s</td></tr>',
				sanitize_html_class( $this->get_plugin()->get_id_dasherized() ),
				count( $this->get_table_headers() ),
				esc_html__( 'Credit/Debit Cards', 'woocommerce-square' )
			);

			$html .= $this->get_table_body_row_html( $this->credit_card_tokens );

			$html .= sprintf(
				'<tr class="sv-wc-payment-gateway-my-payment-methods-type-divider wc-%s-my-payment-methods-type-divider"><td colspan="%d">%s</td></tr>',
				sanitize_html_class( $this->get_plugin()->get_id_dasherized() ),
				count( $this->get_table_headers() ),
				esc_html__( 'Bank Accounts', 'woocommerce-square' )
			);

			$html .= $this->get_table_body_row_html( $this->echeck_tokens );

		} else {

			$html .= $this->get_table_body_row_html( $this->tokens );
		}

		$html .= '</tbody>';

		/**
		 * My Payment Methods Table Body HTML Filter.
		 *
		 * Allow actors to modify the table body HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html table body HTML
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_body_html', $html, $this );
	}


	/**
	 * Returns the table body row HTML, each row represents a single payment method.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token[] $tokens token objects
	 * @return string table tbody > tr HTML
	 */
	protected function get_table_body_row_html( $tokens ) {

		$html = '';

		// for responsive table data-title attributes
		$headers = $this->get_table_headers();

		foreach ( $tokens as $token ) {

			$method = $this->get_table_body_row_data( $token );

			$html .= sprintf(
				'<tr class="sv-wc-payment-gateway-my-payment-methods-method wc-%1$s-my-payment-methods-method %2$s" data-token-id="%3$s">',
				sanitize_html_class( $this->get_plugin()->get_id_dasherized() ),
				$token->is_default() ? 'default' : '',
				esc_attr( $token->get_id() )
			);

			// Display the row data in the order of the headers
			foreach ( $headers as $attribute => $attribute_title ) {

				$value = isset( $method[ $attribute ] ) ? $method[ $attribute ] : __( 'N/A', 'woocommerce-square' );

				$html .= sprintf(
					'<td class="sv-wc-payment-gateway-payment-method-%1$s wc-%2$s-payment-method-%1$s" data-title="%4$s">%3$s</td>',
					sanitize_html_class( $attribute ),
					sanitize_html_class( $this->get_plugin()->get_id_dasherized() ),
					$value,
					esc_attr( $attribute_title )
				);
			}

			$html .= '</tr>';
		}

		/**
		 * My Payment Methods Table Row HTML Filter.
		 *
		 * Allow actors to modify the table row HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html table row HTML
		 * @param Payment_Gateway_Payment_Token[] $tokens simple array of token objects
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_row_html', $html, $tokens, $this );
	}


	/**
	 * Return the payment method data for a given token
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token the token object
	 * @return array payment method data suitable for HTML output
	 */
	protected function get_table_body_row_data( $token ) {

		$method = array(
			'title'   => $this->get_payment_method_title_html( $token ),
			'default' => $this->get_payment_method_default_html( $token ),
			'details' => $this->get_payment_method_details_html( $token ),
			'actions' => $this->get_payment_method_actions_html( $token ),
		);

		// add the expiration date if applicable
		if ( $token->get_exp_month() && $token->get_exp_year() ) {
			$method['expiry'] = $this->get_payment_method_expiry_html( $token );
		}

		/**
		 * My Payment Methods Table Body Row Data Filter.
		 *
		 * Allow actors to modify the table body row data.
		 *
		 * @since 3.0.0
		 *
		 * @param array $methods {
		 *     @type string $title payment method title
		 *     @type string $expiry payment method expiry
		 *     @type string $actions actions for payment method
		 * }
		 * @param array $token simple array of Payment_Gateway_Payment_Token objects
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_body_row_data', $method, $token, $this );
	}

	/**
	 * Get a token's payment method title HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_title_html( Payment_Gateway_Payment_Token $token ) {

		$nickname = $token->get_nickname();
		$title    = $token->get_nickname() ? $token->get_nickname() : $token->get_type_full();

		/**
		 * Filter a token's payment method title.
		 *
		 * @since 3.0.0
		 *
		 * @param string $title payment method title
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		$title = apply_filters( 'wc_square_my_payment_methods_table_method_title', $title, $token, $this );

		$html = '<div class="view">' . esc_html( $title ) . '</div>';

		// add the edit context input
		$html .= '<div class="edit" style="display:none;">';
			$html .= '<input type="text" class="nickname" name="nickname" value="' . esc_html( $token->get_nickname() ) . '" placeholder="' . esc_attr( __( 'Nickname', 'woocommerce-square' ) ) . '" />';
		$html .= '</div>';

		/**
		 * Filter a token's payment method title HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html title HTML
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_method_title_html', $html, $token );
	}


	/**
	 * Get a token's payment method "default" flag HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_default_html( Payment_Gateway_Payment_Token $token ) {

		$html = '<div class="view">';
		 	$html .= $token->is_default() ? '<mark class="default">' . esc_html__( 'Default', 'woocommerce-square' ) . '</mark>' : '';
		$html .= '</div>';

		// add the edit context input
		$html .= '<div class="edit" style="display:none;">';
			$html .= '<input type="checkbox" class="default" name="default" value="yes" ' . checked( true, $token->is_default(), false ) . ' />';
		$html .= '</div>';

		/**
		 * Filter a token's payment method "default" flag HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html "default" flag HTML
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_method_default_html', $html, $token );
	}


	/**
	 * Gets a token's payment method details HTML.
	 *
	 * This includes the method type icon, last four digits, and "default"
	 * badge if applicable. Example:
	 *
	 * [icon] * * * 1234 [default]
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return array
	 */
	protected function get_payment_method_details_html( Payment_Gateway_Payment_Token $token ) {

		$html = '';

		if ( $image_url = $token->get_image_url() ) {
			$html .= sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" width="40" height="25" />', esc_url( $image_url ), esc_attr( $token->get_type_full() ) );
		}

		if ( $last_four = $token->get_last_four() ) {
			$html .= "&bull; &bull; &bull; {$last_four}";
		}

		/**
		 * Filters a token's payment method details HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html details HTML
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_details_html', $html, $token );
	}


	/**
	 * Get a token's payment method expiration date HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_expiry_html( Payment_Gateway_Payment_Token $token ) {

		$html = esc_html( $token->get_exp_date() );

		// TODO: add edit support {CW 2018-01-30}

		/**
		 * Filter a token's payment method expiration date HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html expiration date HTML
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_method_expiry_html', $html, $token );
	}


	/**
	 * Get a token's payment method actions HTML.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return string
	 */
	protected function get_payment_method_actions_html( Payment_Gateway_Payment_Token $token ) {

		$actions = array(
			'<a href="#" class="edit-payment-method button">' . esc_html__( 'Edit', 'woocommerce-square' ) . '</a>',
			'<a href="#" class="save-payment-method button" style="display:none">' . esc_html__( 'Save', 'woocommerce-square' ) . '</a>',
		);

		foreach ( $this->get_payment_method_actions( $token ) as $action => $details ) {

			$classes    = isset( $details['class'] ) ? (array) $details['class'] : array();
			$attributes = isset( $details['attributes'] ) ? (array) $details['attributes'] : array();

			$attributes['data-token-id'] = $token->get_id();
			$attributes['data-action']   = $action;

			// if the action has a tooltip set
			if ( ! empty( $details['tip'] ) ) {

				$classes[] = 'tip';

				$attributes['title'] = $details['tip'];
			}

			// build the attributes
			foreach ( $attributes as $attribute => $value ) {
				$attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
				unset( $attributes[ $attribute ] );
			}

			// build the button
			$actions[] = sprintf(
				( in_array( 'disabled', $classes, true ) ) ? '<a class="button %2$s" %3$s>%4$s</a>' : '<a href="%1$s" class="button %2$s" %3$s>%4$s</a>',
				! empty( $details['url'] ) ? esc_url( $details['url'] ) : '#',
				implode( ' ', array_map( 'sanitize_html_class', $classes ) ),
				implode( ' ', $attributes ),
				esc_html( $details['name'] )
			);
		}

		$html = implode( '', $actions );

		/**
		 * Filters a token's payment method actions HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string $html actions HTML
		 * @param Payment_Gateway_Payment_Token $token token object
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_actions_html', $html, $token );
	}


	/**
	 * Gets the actions for the given payment method token.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @return array
	 */
	protected function get_payment_method_actions( $token ) {

		$actions = array(
			'delete' => __( 'Delete', 'woocommerce-square' ),
		);

		$plugin_slug = $this->get_plugin()->get_id_dasherized();

		foreach ( $actions as $action => $label ) {

			$url = add_query_arg( array(
				"wc-{$plugin_slug}-token"  => $token->get_id(),
				"wc-{$plugin_slug}-action" => $action,
			) );

			$actions[ $action ] = array(
				'name'  => $label,
				'url'   => wp_nonce_url( $url, "wc-{$plugin_slug}-token-action" ),
				'class' => "{$action}-payment-method",
			);
		}

		/**
		 * My Payment Methods Table Method Actions Filter.
		 *
		 * Allow actors to modify the table method actions.
		 *
		 * @since 3.0.0
		 *
		 * @param $actions array {
		 *     @type string $url action URL
		 *     @type string $class action button class
		 *     @type string $name action button name
		 * }
		 * @param Payment_Gateway_Payment_Token $token
		 * @param Payment_Gateway_My_Payment_Methods $this instance
		 */
		return apply_filters( 'wc_square_my_payment_methods_table_method_actions', $actions, $token, $this );
	}


	/** Payment Method actions ************************************************/


	/**
	 * Saves a payment method via AJAX.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function ajax_save_payment_method() {

		check_ajax_referer( 'wc_square_save_payment_method', 'nonce' );

		try {

			$this->load_tokens();

			$token_id = Square_Helper::get_post( 'token_id' );

			if ( empty( $this->tokens[ $token_id ] ) || ! $this->tokens[ $token_id ] instanceof Payment_Gateway_Payment_Token ) {
				throw new \Exception( 'Invalid token ID' );
			}

			$user_id  = get_current_user_id();
			$token    = $this->tokens[ $token_id ];
			$gateway  = $this->get_plugin()->get_gateway_from_token( $user_id, $token );

			// bail if the gateway or token couldn't be found for this user
			if ( ! $gateway || ! $gateway->get_payment_tokens_handler()->user_has_token( $user_id, $token ) ) {
				throw new \Exception( 'Invalid token' );
			}

			$data = array();

			parse_str( Square_Helper::get_post( 'data' ), $data );

			// set the data
			$token = $this->save_token_data( $token, $data );

			 // use the handler so other methods don't remain default
			if ( $token->is_default() ) {
				$gateway->get_payment_tokens_handler()->set_default_token( $user_id, $token );
			}

			// persist the data
			$gateway->get_payment_tokens_handler()->update_token( $user_id, $token );

			wp_send_json_success( array(
				'html'       => $this->get_table_body_row_html( array( $token ) ),
				'is_default' => $token->is_default(),
				'nonce'      => wp_create_nonce( 'wc_square_save_payment_method' ),
			) );

		} catch ( \Exception $e ) {

			wp_send_json_error( $e->getMessage() );
		}
	}


	/**
	 * Saves data to a token.
	 *
	 * Gateways can override this to set their own data if they add custom Edit
	 * fields. Note that this does not persist the data to the db, but only sets
	 * it for the object.
	 *
	 * @since 3.0.0
	 *
	 * @param Payment_Gateway_Payment_Token $token token object
	 * @param array $data {
	 *    new data to store for the token
	 *
	 *    @type string $nickname method nickname
	 *    @type string $default  whether the method should be set as default
	 * }
	 * @return Payment_Gateway_Payment_Token
	 */
	protected function save_token_data( Payment_Gateway_Payment_Token $token, array $data ) {

		$raw_nickname   = ! empty( $data['nickname'] ) ? $data['nickname'] : '';
		$clean_nickname = wc_clean( $raw_nickname );

		// only set the nickname if there is a clean value, or it was deliberately cleared
		if ( $clean_nickname || ! $raw_nickname ) {
			$token->set_nickname( $clean_nickname );
		}

		$token->set_default( isset( $data['default'] ) && 'yes' === $data['default'] );

		return $token;
	}


	/**
	 * Handle payment methods actions, e.g. deleting a payment method or setting
	 * one as default
	 *
	 * @since 3.0.0
	 */
	public function handle_payment_method_actions() {

		if ( ! $this->has_tokens ) {
			return;
		}

		$token  = isset( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token' ] )  ? trim( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token' ] ) : '';
		$action = isset( $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' ] ) ? $_GET[ 'wc-' . $this->get_plugin()->get_id_dasherized() . '-action' ] : '';

		// process payment method actions
		if ( $token && $action && ! empty( $_GET['_wpnonce'] ) && is_user_logged_in() ) {

			// security check
			if ( false === wp_verify_nonce( $_GET['_wpnonce'], 'wc-' . $this->get_plugin()->get_id_dasherized() . '-token-action' ) ) {

				Square_Helper::wc_add_notice( esc_html__( 'Oops, you took too long, please try again.', 'woocommerce-square' ), 'error' );

				$this->redirect_to_my_account();
			}

			// current logged in user
			$user_id = get_current_user_id();

			$gateway = $this->get_plugin()->get_gateway_from_token( $user_id, $token );

			// couldn't find an associated gateway for that token
			if ( ! is_object( $gateway ) ) {

				Square_Helper::wc_add_notice( esc_html__( 'There was an error with your request, please try again.', 'woocommerce-square' ), 'error' );

				$this->redirect_to_my_account();
			}

			switch ( $action ) {

				// handle deletion
				case 'delete':

					if ( ! $gateway->get_payment_tokens_handler()->remove_token( $user_id, $token ) ) {

						/* translators: Payment method as in a specific credit card, e-check or bank account */
						Square_Helper::wc_add_notice( esc_html__( 'Error removing payment method', 'woocommerce-square' ), 'error' );

					} else {

						/* translators: Payment method as in a specific credit card, e-check or bank account */
						Square_Helper::wc_add_notice( esc_html__( 'Payment method deleted.', 'woocommerce-square' ) );

						/**
						 * Fires after a new payment method is deleted by a customer.
						 *
						 * @since 3.0.0
						 *
						 * @param string $token_id ID of the deleted token
						 * @param int $user_id user ID
						 */
						do_action( 'wc_payment_gateway_' . $gateway->get_id() . '_payment_method_deleted', $token, $user_id );
					}

				break;

				// custom actions
				default:

					/**
					 * My Payment Methods Custom Action.
					 *
					 * Fired when a custom action is requested for a payment method (e.g. other than delete/make default)
					 *
					 * @since 3.0.0
					 * @param \Payment_Gateway_My_Payment_Methods $this instance
					 */
					do_action( 'wc_square_my_payment_methods_action_' . sanitize_title( $action ), $this );
				break;
			}

			$this->redirect_to_my_account();
		}
	}


	/**
	 * Redirect back to the Payment Methods (WC 2.6+) or My Account page
	 *
	 * @since 3.0.0
	 */
	protected function redirect_to_my_account() {

		wp_redirect( wc_get_account_endpoint_url( 'payment-methods' ) );
		exit;
	}


	/**
	 * Return the gateway plugin, primarily a convenience method to other actors
	 * using filters
	 *
	 * @since 3.0.0
	 *
	 * @return Payment_Gateway_Plugin
	 */
	public function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Returns true if at least one of the plugin's gateways supports the
	 * add new payment method feature
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	protected function supports_add_payment_method() {

		foreach ( $this->get_plugin()->get_gateways() as $gateway ) {

			if ( $gateway->is_direct_gateway() && $gateway->supports_add_payment_method() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Determines if we're viewing the My Account -> Payment Methods page.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	protected function is_payment_methods_page() {
		global $wp;

		return is_user_logged_in() && is_account_page() && isset( $wp->query_vars['payment-methods'] );
	}
}
