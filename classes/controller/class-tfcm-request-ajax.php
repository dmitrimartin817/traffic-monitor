<?php
/**
 * TFCM_Request_Ajax class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

// Cconsider switching to https://developers.whatismybrowser.com/api/ .
use donatj\UserAgent\UserAgentParser;

/**
 * Extends TFCM_Request_Abstract to handle AJAX requests.
 *
 * @package TrafficMonitor
 */
class TFCM_Request_Ajax extends TFCM_Request_Abstract {
	/**
	 * Initializes a new Request instance with default values.
	 */
	public function __construct() {
		parent::__construct();

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- This is sanitized later after parsing
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';
		$parser     = new UserAgentParser();
		$ua         = $parser->parse( $user_agent );

		$host           = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$validated_host = filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );

		// $this->request_time set by TFCM_Request_Abstract
		// $this->request_type set by TFCM_Request_Abstract
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in TFCM_Log_Controller class before saving to database
		$this->request_url = substr( sanitize_text_field( wp_unslash( $_POST['request_url'] ?? '' ) ), 0, 255 );
		$this->method      = ''; // Cannot determine original request header.
		$this->referer_url = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 255 ) : '';
		// $this->user_role set by TFCM_Request_Abstract
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in TFCM_Log_Controller class before saving to database
		$this->ip_address       = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
		$this->host             = false !== $validated_host ? $validated_host : '';
		$this->device           = strpos( $user_agent, 'Mobile' ) !== false ? 'Mobile' : 'Desktop';
		$this->operating_system = sanitize_text_field( $ua->platform() );
		$this->browser          = sanitize_text_field( $ua->browser() );
		$this->browser_version  = sanitize_text_field( $ua->browserVersion() );
		$this->user_agent       = sanitize_text_field( $user_agent );
		$this->origin           = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$this->accept           = ''; // Cannot determine original request header.
		$this->accept_encoding  = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : '';
		$this->accept_language  = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$this->content_type     = ''; // Cannot determine original request header.
		$this->connection_type  = ''; // Cannot determine original request header.
		$this->cache_control    = ''; // Cannot determine original request header.
		$this->status_code      = http_response_code();
	}

	/**
	 * Returns filtered data specific to AJAX requests.
	 *
	 * @return array Associative array containing AJAX request metadata.
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
