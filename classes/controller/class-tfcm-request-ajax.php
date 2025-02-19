<?php
/**
 * File: /classes/controller/class-tfcm-request-ajax.php
 *
 * Extends TFCM_Request_Abstract to handle AJAX-specific request data.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

// Cconsider switching to https://developers.whatismybrowser.com/api/ .
use donatj\UserAgent\UserAgentParser;

/**
 * Class TFCM_Request_Ajax
 *
 * Captures and sanitizes request data specific to AJAX requests.
 */
class TFCM_Request_Ajax extends TFCM_Request_Abstract {
	/**
	 * Constructs an AJAX request object and populates properties from POST and SERVER data.
	 */
	public function __construct() {
		parent::__construct();

		$this->request_url     = substr( sanitize_text_field( wp_unslash( $_POST['request_url'] ?? '' ) ), 0, 255 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in TFCM_Log_Controller class before saving to database
		$this->is_cached       = true;
		$this->method          = ''; // Cannot determine original request header.
		$this->ip_address      = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in TFCM_Log_Controller class before saving to database
		$this->accept          = ''; // Cannot determine original request header.
		$this->content_type    = ''; // Cannot determine original request header.
		$this->connection_type = ''; // Cannot determine original request header.
		$this->cache_control   = ''; // Cannot determine original request header.
	}

	/**
	 * Retrieves the AJAX request data as an associative array.
	 *
	 * @return array AJAX request metadata.
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
