<?php
/**
 * File: /classes/controller/class-tfcm-request-http.php
 *
 * Extends TFCM_Request_Abstract to handle HTTP (non-AJAX) request data.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Request_Http
 *
 * Captures and sanitizes request data for standard HTTP requests.
 */
class TFCM_Request_Http extends TFCM_Request_Abstract {
	/**
	 * Constructs an HTTP request object and populates properties from SERVER data.
	 */
	public function __construct() {
		parent::__construct();

		// Determines the best client IP by selecting the last valid public IP from HTTP_X_FORWARDED_FOR if available, else REMOTE_ADDR
		$forwarded_for = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
		$xff_ips       = array_filter( array_map( 'trim', explode( ',', $forwarded_for ) ) );
		$remote_addr   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$best_ip       = filter_var( $remote_addr, FILTER_VALIDATE_IP ) ? $remote_addr : '';
		foreach ( array_reverse( $xff_ips ) as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				$best_ip = $ip;
				break;
			}
		}

		$this->request_url     = substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), 0, 255 );
		$this->is_cached       = false;
		$this->method          = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		$this->ip_address      = $best_ip;
		$this->accept          = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		$this->content_type    = isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : '';
		$this->connection_type = isset( $_SERVER['HTTP_CONNECTION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CONNECTION'] ) ) : '';
		$this->cache_control   = isset( $_SERVER['HTTP_CACHE_CONTROL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CACHE_CONTROL'] ) ) : '';
	}


	/**
	 * Retrieves the HTTP request data as an associative array.
	 *
	 * @return array HTTP request metadata.
	 */
	public function get_data() {
		$data                    = parent::get_data();
		$data['request_url']     = $this->request_url;
		$data['is_cached']       = $this->is_cached;
		$data['method']          = $this->method;
		$data['ip_address']      = $this->ip_address;
		$data['accept']          = $this->accept;
		$data['content_type']    = $this->content_type;
		$data['connection_type'] = $this->connection_type;
		$data['cache_control']   = $this->cache_control;
		return $data;
	}
}
