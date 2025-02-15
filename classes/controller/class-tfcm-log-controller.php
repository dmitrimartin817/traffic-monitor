<?php
/**
 * TFCM_Log_Controller class file class-tfcm-log-controller.php
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Processes request and logs it into the database.
 *
 * @package TrafficMonitor
 */
class TFCM_Log_Controller {
	private $request;

	/**
	 * TFCM_Log_Controller constructor.
	 *
	 * Initializes the logger with a request object and assigns the global `$wpdb` instance.
	 *
	 * @param mixed $request The request object containing request metadata.
	 */
	public function __construct( $request ) {
		$this->request = $request;
	}

	/**
	 * Processes the request then logs it by passing it to the database model.
	 *
	 * @return void
	 */
	public function process_request() {

		// Exclude non-HTML requests for HTTP requests
		if ( 'HTTP' === $this->request->request_type && stripos( $this->request->accept ?? '', 'text/html' ) === false ) {
			return; // skip logging
		}

		// Exclude static files for all requests
		if (
			preg_match( '/\.(css|js|jpg|jpeg|png|gif|svg|woff|woff2|ttf|ico|map)$/i', $this->request->request_url ?? '' ) ||
			stripos( $this->request->request_url ?? '', '/wp-json/' ) !== false
		) {
			return; // skip logging
		}

		$request_type = $this->request->request_type;

		if ( 'AJAX' === $request_type ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Cannot verify a unique nonce, although it is used in a transient key that gets varified belos
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( '' === $nonce ) {
				// error_log( 'Nonce is missing on ' . __LINE__ . ' of ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
				wp_send_json_error( array( 'message' => 'Missing nonce.' ), 400 );
				return;
			}
			$is_localhost = stripos( $this->request->request_url, 'localhost' ) !== false;
			if ( $is_localhost ) {
				// error_log( 'Skipping AJAX logging on localhost on ' . __LINE__ . ' of ' . basename( __FILE__ ) );
				wp_send_json_success( array( 'message' => 'Localhost AJAX request ignored.' ), 200 );
				return;
			}

			// Extract only the path from the full URL
			$parsed_url                 = parse_url( $this->request->request_url );
			$this->request->request_url = $parsed_url['path'] ?? $this->request->request_url;

		} elseif ( 'HTTP' === $request_type ) {
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

			if ( 'AJAX' === $request_type ) {
				wp_send_json_success( array( 'message' => 'Request already logged.' ), 200 );
			}

			return;
		}

		// Prevent duplicate logging by setting the transient **before** database insertion.
		set_transient( $transient_key, true, 60 );

		TFCM_Database::insert_request( $this->request );

		if ( 'AJAX' === $request_type ) {
			wp_send_json_success( array( 'message' => 'AJAX request logged successfully.' ), 200 );
			return;
		}
	}
}
