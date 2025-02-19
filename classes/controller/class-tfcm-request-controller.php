<?php
/**
 * File: /classes/controller/class-tfcm-request-controller.php
 *
 * Determines the type of incoming request (AJAX, HTTP, etc.) and routes it for logging,
 * as well as handling bulk actions via AJAX.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Request_Controller
 *
 * Routes incoming requests to the appropriate handler and processes bulk actions.
 */
class TFCM_Request_Controller {
	/**
	 * Registers AJAX hooks for handling request logging and bulk actions.
	 *
	 * Hooks are registered for both logged-in and non-logged-in users.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// Handle AJAX requests to log cached pages from non-logged-in users
		add_action( 'wp_ajax_nopriv_tfcm_log_ajax_request', array( __CLASS__, 'handle_ajax_request' ) );
		// Handle AJAX requests to log cached pages from logged-in users
		add_action( 'wp_ajax_tfcm_log_ajax_request', array( __CLASS__, 'handle_ajax_request' ) );
		// Handle AJAX requests to handle bul actions from logged-in users
		add_action( 'wp_ajax_tfcm_handle_bulk_action', array( __CLASS__, 'handle_bulk_action' ) );
	}

	/**
	 * Handle AJAX requests.
	 *
	 * This function sets the global request type to 'AJAX' and then calls handle_request().
	 *
	 * @return void
	 */
	public static function handle_ajax_request() {
		global $tfcm_request_type;
		$tfcm_request_type = 'AJAX';
		self::handle_request();
	}

	/**
	 * Handles the request by instantiating the appropriate request object and logging it.
	 *
	 * @return void
	 */
	public static function handle_request() {
		// error_log( 'Trace on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );

		global $tfcm_request_type;

		// Instantiate the correct request class based on the request type.
		if ( 'AJAX' === $tfcm_request_type ) {
			$request = new TFCM_Request_Ajax();
		} elseif ( 'HTTP' === $tfcm_request_type ) {
			$request = new TFCM_Request_Http();
		} else {
			exit;
		}

		// create nonce that can be used in enqueue_client_scripts() and process_request()
		global $cache_check_nonce;
		$cache_check_nonce = wp_create_nonce( uniqid( 'tfcm_cache_logging_nonce_', true ) );

		// Log request through the log controller
		$log_controller = new TFCM_Log_Controller( $request );
		$log_controller->process_request();
	}

	/**
	 * Processes bulk actions (delete, export) for log entries.
	 *
	 * Verifies nonces and user capabilities, then performs the requested action,
	 * returning a JSON response with the result.
	 *
	 * @return void
	 */
	public static function handle_bulk_action() {

		// Verify nonce.
		if ( ! check_ajax_referer( 'tfcm_ajax_nonce', 'nonce', false ) ) {
			// error_log( 'tfcm_ajax_nonce nonce not verified on line ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
			wp_send_json_error( array( 'message' => 'Invalid request. Please try again.' ), 400 );
			return;
		}

		// Restrict access to admins only.
		if ( ! current_user_can( 'manage_options' ) ) {
			// error_log( 'user cannot manage options on line ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
			wp_send_json_error( array( 'message' => 'Unauthorized access.' ), 403 );
			return;
		}

		// Get the action and IDs.
		$bulk_action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$log_ids     = isset( $_POST['element'] ) ? wp_parse_id_list( wp_unslash( $_POST['element'] ) ) : array();

		if ( empty( $bulk_action ) ) {
			wp_send_json_error( array( 'message' => 'Please select a bulk action before clicking Apply.' ), 400 );
		}

		if ( ( 'delete' === $bulk_action || 'export' === $bulk_action ) && empty( $log_ids ) ) {
			wp_send_json_error( array( 'message' => 'Please select the records you want to ' . $bulk_action . '.' ), 400 );
		}

		if ( 'delete' === $bulk_action ) {
			$result = TFCM_Database::delete_selected_requests( $log_ids );

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'Total records deleted: ' . count( $log_ids ) ), 200 );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete records.' ), 400 );
			}
		} elseif ( 'delete_all' === $bulk_action ) {
			$result = TFCM_Database::delete_all_requests();

			if ( false !== $result ) {
				wp_send_json_success( array( 'message' => 'All records deleted successfully. Refresh table to verify.' ), 200 );
			} else {
				wp_send_json_error( array( 'message' => 'Failed to delete all records.' ), 400 );
			}
		}

		if ( 'export' === $bulk_action || 'export_all' === $bulk_action ) {
			TFCM_Export_Manager::delete_old_exports();

			// Generate unique filename with nonce + timestamp.
			$nonce     = wp_create_nonce( 'tfcm_csv_export' );
			$timestamp = time();
			$file_name = "traffic-log-{$nonce}-{$timestamp}.csv";
		}

		if ( 'export' === $bulk_action ) {
			$rows = TFCM_Database::get_selected_requests( $log_ids );

			$total_rows = count( $log_ids );
			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		} elseif ( 'export_all' === $bulk_action ) {
			$total_rows = TFCM_Database::count_all_requests();
			$rows       = TFCM_Database::get_all_requests();

			TFCM_Export_Manager::generate_csv( $rows, $file_name, $total_rows );
		}
	}
}
