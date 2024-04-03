<?php

namespace WooCommerce\Square\Framework\Utilities;
use WooCommerce\Square\Framework\Square_Helper;
use WooCommerce\Square\Framework as SquareFramework;

defined( 'ABSPATH' ) or exit;

/**
 * The job batch handler class.
 *
 * This provides a way for plugins to process "background" jobs in batches when
 * regular background processing isn't available.
 *
 * @since 3.0.0
 */
class WP_Job_Batch_Handler {


	/** @var WP_Background_Job_Handler job handler instance */
	protected $job_handler;

	/** @var SquareFramework\Plugin $plugin WC plugin instance */
	protected $plugin;

	/** @var int default items per batch */
	protected $items_per_batch = 20;


	/**
	 * Constructs the class.
	 *
	 * @since 3.0.0
	 *
	 * @param SV_WP_Background_Job_Handler $job_handler job handler instance
	 * @param SquareFramework\Plugin $plugin WC plugin instance
	 */
	public function __construct( $job_handler, SquareFramework\Plugin $plugin ) {

		if ( ! is_admin() ) {
			return;
		}

		$this->job_handler = $job_handler;
		$this->plugin      = $plugin;

		$this->add_hooks();

		$this->render_js();
	}


	/**
	 * Adds the necessary action and filter hooks.
	 *
	 * @since 3.0.0
	 */
	protected function add_hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_' . $this->get_job_handler()->get_identifier() . '_process_batch', array( $this, 'ajax_process_batch' ) );
		add_action( 'wp_ajax_' . $this->get_job_handler()->get_identifier() . '_cancel_job', array( $this, 'ajax_cancel_job' ) );
	}


	/**
	 * Enqueues the scripts.
	 *
	 * @since 3.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->get_job_handler()->get_identifier() . '_batch_handler',  $this->get_plugin()->get_framework_assets_url() . '/js/admin/sv-wp-admin-job-batch-handler.min.js', array( 'jquery' ), $this->get_plugin()->get_version() );
	}


	/**
	 * Renders the inline JavaScript for instantiating the batch handler class.
	 *
	 * @since 3.0.0
	 */
	protected function render_js() {

		/**
		 * Filters the JavaScript batch handler arguments.
		 *
		 * @since 3.0.0
		 *
		 * @param array $args arguments to pass to the JavaScript batch handler
		 * @param SV_WP_Job_Batch_Handler $handler handler object
		 */
		$args = apply_filters( $this->get_job_handler()->get_identifier() . '_batch_handler_js_args', $this->get_js_args(), $this );

		wc_enqueue_js( sprintf( 'window.%1$s_batch_handler = new %2$s( %3$s );',
			esc_js( $this->get_job_handler()->get_identifier() ),
			esc_js( $this->get_js_class() ),
			wp_json_encode( $args )
		) );
	}


	/**
	 * Gets the JavaScript batch handler arguments.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	protected function get_js_args() {

		return array(
			'id'            => $this->get_job_handler()->get_identifier(),
			'process_nonce' => wp_create_nonce( $this->get_job_handler()->get_identifier() . '_process_batch' ),
			'cancel_nonce'  => wp_create_nonce( $this->get_job_handler()->get_identifier() . '_cancel_job' ),
		);
	}


	/**
	 * Gets the JavaScript batch handler class name.
	 *
	 * Plugins can override this with their own handler that extends the base.
	 *
	 * @since 3.0.0
	 *
	 * @return string
	 */
	protected function get_js_class() {

		return 'Square_WP_Job_Batch_Handler';
	}


	/**
	 * Processes a job batch via AJAX.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 *
	 * @throws \Exception upon error.
	 */
	public function ajax_process_batch() {

		check_ajax_referer( $this->get_job_handler()->get_identifier() . '_process_batch', 'security' );

		if ( empty( $_POST['job_id'] ) ) {
			return;
		}

		try {

			$job = $this->process_batch( $_POST['job_id'] );

			$job = $this->process_job_status( $job );

			wp_send_json_success( (array) $job );

		} catch( \Exception $e ) {

			$data = ( ! empty( $job ) ) ? (array) $job : array();

			$data['message'] = $e->getMessage();

			wp_send_json_error( $data );
		}
	}


	/**
	 * Cancels a job via AJAX.
	 *
	 * @internal
	 *
	 * @since 3.0.0
	 */
	public function ajax_cancel_job() {

		check_ajax_referer( $this->get_job_handler()->get_identifier() . '_cancel_job', 'security' );

		if ( empty( $_POST['job_id'] ) ) {
			return;
		}

		$this->get_job_handler()->delete_job( $_POST['job_id'] );

		wp_send_json_success();
	}


	/**
	 * Handles a job after processing one of its batches.
	 *
	 * Allows plugins to add extra job properties and handle certain statuses.
	 * Implementations may throw a \Exception.
	 *
	 * @since 3.0.0
	 *
	 * @param \stdClass|object $job job object
	 * @return \stdClass|object $job job object
	 */
	protected function process_job_status( $job ) {

		$job->percentage = Square_Helper::number_format( (int) $job->progress / (int) $job->total * 100 );

		return $job;
	}


	/**
	 * Processes a batch of items for the given job.
	 *
	 * A batch consists of the number of items defined by self::get_items_per_batch()
	 * or the number we're able to process before exceeding time or memory limits.
	 *
	 * @since 3.0.0
	 *
	 * @param string $job_id job to process
	 * @return \stdClass|object $job job after processing the batch
	 * @throws \Exception
	 */
	public function process_batch( $job_id ) {

		$job = $this->get_job_handler()->get_job( $job_id );

		if ( ! $job ) {
			throw new \Exception( 'Invalid job ID' );
		}

		return $this->get_job_handler()->process_job( $job, $this->get_items_per_batch() );
	}


	/**
	 * Gets the number of items to process in a single request when processing job item batches.
	 *
	 * @since 3.0.0
	 *
	 * @return int
	 */
	protected function get_items_per_batch() {

		/**
		 * Filters the number of items to process in a single request when processing job item batches.
		 *
		 * @since 3.0.0
		 *
		 * @param int $items_per_batch
		 */
		$items_per_batch = absint( apply_filters( $this->get_job_handler()->get_identifier() . '_batch_handler_items_per_batch', $this->items_per_batch ) );

		return $items_per_batch > 0 ? $items_per_batch : 1;
	}


	/**
	 * Gets the job handler.
	 *
	 * @since 3.0.0
	 *
	 * @return SV_WP_Background_Job_Handler
	 */
	protected function get_job_handler() {

		return $this->job_handler;
	}


	/**
	 * Gets the plugin instance.
	 *
	 * @since 3.0.0
	 *
	 * @return SquareFramework\Plugin
	 */
	protected function get_plugin() {

		return $this->plugin;
	}
}
