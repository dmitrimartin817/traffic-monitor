<?php
/**
 * File: /classes/controller/class-tfcm-log-controller.php
 *
 * Processes incoming requests and logs them to the database.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Log_Controller
 *
 * Processes a given request object and logs the data, delegating the database insertion to TFCM_Database.
 */
class TFCM_Log_Controller {
	private $request;

	/**
	 * Constructor for TFCM_Log_Controller.
	 *
	 * Initializes the controller with a given request object.
	 *
	 * @param mixed $request The request object containing metadata.
	 */
	public function __construct( $request ) {
		$this->request = $request;
	}

	/**
	 * Processes the request and logs it to the database.
	 *
	 * Skips logging for non-HTML responses or duplicate requests.
	 * Sends a JSON response for AJAX requests upon completion.
	 *
	 * @return void
	 */
	public function process_request() {
		global $tfcm_request_type;

		// Exclude non-HTML requests for HTTP requests
		if ( 'HTTP' === $tfcm_request_type && stripos( $this->request->accept ?? '', 'text/html' ) === false ) {
			return; // skip logging
		}

		// Exclude static files for all requests
		if (
			preg_match( '/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico|map)$/i', $this->request->request_url ?? '' ) ||
			stripos( $this->request->request_url ?? '', '/wp-json/' ) !== false
		) {
			return; // skip logging
		}

		if ( 'AJAX' === $tfcm_request_type ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Cannot verify a unique nonce, although it is used in a transient key that gets varified belos
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( '' === $nonce ) {
				// error_log( 'Nonce is missing on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
				wp_send_json_error( array( 'message' => 'Missing nonce.' ), 400 );
				return;
			}
			$is_localhost = stripos( $this->request->request_url, 'localhost' ) !== false;
			if ( $is_localhost ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				// error_log( 'Skipping AJAX logging on localhost on ' . __LINE__ . ' of ' . basename( __FILE__ ) );
				wp_send_json_success( array( 'message' => 'Localhost AJAX request ignored.' ), 200 );
				return;
			}

			// Extract only the path from the full URL
			$parsed_url                 = wp_parse_url( $this->request->request_url );
			$this->request->request_url = $parsed_url['path'] ?? $this->request->request_url;

		} elseif ( 'HTTP' === $tfcm_request_type ) {
			global $cache_check_nonce;
			$nonce = $cache_check_nonce;
		} else {
			return; // Ignore other request types.
		}

		$ip            = $this->request->ip_address;
		$transient_key = 'tfcm_nonce_' . $nonce . '_' . md5( $ip );

		// Prevent double logging for all requests (not just AJAX)
		if ( get_transient( $transient_key ) ) {
			// error_log( 'Skipping duplicate request. Nonce: ' . $nonce . ' IP: ' . $ip . ' → Already logged on ' . __LINE__ . ' of ' . basename( __FILE__ ) );

			if ( 'AJAX' === $tfcm_request_type ) {
				wp_send_json_success( array( 'message' => 'Request already logged.' ), 200 );
			}

			return;
		}

		// Prevent duplicate logging by setting the transient **before** database insertion.
		set_transient( $transient_key, true, 60 );

		TFCM_Database::insert_request( $this->request );

		if ( 'AJAX' === $tfcm_request_type ) {
			wp_send_json_success( array( 'message' => 'AJAX request logged successfully.' ), 200 );
			return;
		}
	}
}
