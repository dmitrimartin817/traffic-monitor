<?php
/**
 * File: /classes/controller/class-tfcm-request-http.php
 *
 * Extends TFCM_Request_Abstract to handle HTTP (non-AJAX) request data.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

// Cconsider switching to https://developers.whatismybrowser.com/api/ .
use donatj\UserAgent\UserAgentParser;

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

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization happens within parse_user_agent().
		$user_agent_data = $this->parse_user_agent( $_SERVER['HTTP_USER_AGENT'] );

		// $this->request_time set by TFCM_Request_Abstract
		// $this->request_type set by TFCM_Request_Abstract
		$this->request_url = substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), 0, 255 );
		$this->method      = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		$this->referer_url = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 255 ) : '';
		// $this->user_role set by TFCM_Request_Abstract
		$this->ip_address = $best_ip;
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization happens within validate_host().
		$this->host             = $this->validate_host( $_SERVER['HTTP_HOST'] );
		$this->device           = $user_agent_data['device'];
		$this->operating_system = $user_agent_data['operating_system'];
		$this->browser          = $user_agent_data['browser'];
		$this->browser_version  = $user_agent_data['browser_version'];
		$this->user_agent       = $user_agent_data['user_agent'];
		$this->origin           = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$this->accept           = isset( $_SERVER['HTTP_ACCEPT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) ) : '';
		$this->accept_encoding  = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : '';
		$this->accept_language  = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$this->content_type     = isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : '';
		$this->connection_type  = isset( $_SERVER['HTTP_CONNECTION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CONNECTION'] ) ) : '';
		$this->cache_control    = isset( $_SERVER['HTTP_CACHE_CONTROL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CACHE_CONTROL'] ) ) : '';
		$this->status_code      = http_response_code();
	}


	/**
	 * Retrieves the HTTP request data as an associative array.
	 *
	 * @return array HTTP request metadata.
	 */
	public function get_data() {
		$data = parent::get_data();
		// $data['request_time']  set by TFCM_Request_Abstract
		// $data['request_type']  set by TFCM_Request_Abstract
		$data['request_url'] = $this->request_url;
		$data['method']      = $this->method;
		$data['referer_url'] = $this->referer_url;
		// $data['user_role']  set by TFCM_Request_Abstract
		$data['ip_address']       = $this->ip_address;
		$data['host']             = $this->host;
		$data['device']           = $this->device;
		$data['operating_system'] = $this->operating_system;
		$data['browser']          = $this->browser;
		$data['browser_version']  = $this->browser_version;
		$data['user_agent']       = $this->user_agent;
		$data['origin']           = $this->origin;
		$data['accept']           = $this->accept;
		$data['accept_encoding']  = $this->accept_encoding;
		$data['accept_language']  = $this->accept_language;
		$data['content_type']     = $this->content_type;
		$data['connection_type']  = $this->connection_type;
		$data['cache_control']    = $this->cache_control;
		$data['status_code']      = $this->status_code;
		return $data;
	}
}
