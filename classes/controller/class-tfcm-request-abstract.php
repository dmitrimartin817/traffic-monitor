<?php
/**
 * TFCM_Request_Abstract class file class-tfcm-request-abstract.php
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class representing an HTTP request.
 *
 * Encapsulates request data, including metadata such as headers, user-agent,
 * and request type. Provides a base structure for different types of requests,
 * such as AJAX and standard HTTP requests.
 *
 * @package TrafficMonitor
 */
abstract class TFCM_Request_Abstract {
	public $request_time;
	public $request_type;
	public $request_url;
	public $method;
	public $referer_url;
	public $user_role;
	public $ip_address;
	public $host;
	public $device;
	public $operating_system;
	public $browser;
	public $browser_version;
	public $user_agent;
	public $origin;
	public $accept;
	public $accept_encoding;
	public $accept_language;
	public $content_type;
	public $connection_type;
	public $cache_control;
	public $status_code;

	/**
	 * Initializes default properties for request objects.
	 * Subclasses must override specific request handling methods.
	 */
	public function __construct() {
		global $tfcm_request_type;
		$this->request_time     = current_time( 'mysql' );
		$this->request_type     = $tfcm_request_type;
		$this->request_url      = '';
		$this->method           = '';
		$this->referer_url      = '';
		$this->user_role        = self::get_user_role();
		$this->ip_address       = '';
		$this->host             = '';
		$this->device           = '';
		$this->operating_system = '';
		$this->user_agent       = '';
		$this->browser          = '';
		$this->browser_version  = '';
		$this->origin           = '';
		$this->accept           = '';
		$this->accept_encoding  = '';
		$this->accept_language  = '';
		$this->content_type     = '';
		$this->connection_type  = '';
		$this->cache_control    = '';
		$this->status_code      = '';
	}

	/**
	 * Determines the current user's role.
	 *
	 * @return string The user's role (e.g., 'administrator', 'editor', 'subscriber', 'visitor').
	 */
	private static function get_user_role() {
		$user_role = 'visitor';
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( ! empty( $user->roles ) ) {
				$user_role = $user->roles[0];
			}
		}
		return $user_role;
	}

	/**
	 * Determines the type of request.
	 *
	 * @return string The request type ('AJAX', 'API', 'CLI', 'CRON', 'XML-RPC', 'ADMIN', 'HTTP', or 'UNKNOWN').
	 */
	public static function get_request_type() {
		// error_log( 'Backtrace: ' . print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), true ) );
		// error_log( 'New request received at: ' . current_time( 'mysql' ) );
		// error_log( '$_SERVER[REQUEST_URI]: ' . ( $_SERVER['REQUEST_URI'] ?? 'Not set' ) );
		// error_log( '$_SERVER[HTTP_REFERER]: ' . ( $_SERVER['HTTP_REFERER'] ?? 'Not set' ) );
		// error_log( '$_POST: ' . ( isset( $_POST ) ? print_r( $_POST, true ) : 'Not set' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return 'AJAX';
		}
		if ( is_admin() ) {
			return 'ADMIN'; // request is for an administrative interface page
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'API';
		}
		if ( defined( 'WP_CLI' ) ) {
			return 'CLI';
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return 'CRON';
		}
		if ( isset( $_SERVER['REQUEST_METHOD'] ) ) {
			return 'HTTP';
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return 'XML-RPC';
		}
		if ( isset( $_SERVER['HTTP_UPGRADE'] ) && 'websocket' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_UPGRADE'] ) ) ) ) {
			return 'WEBSOCKET';
		}
		return 'UNKNOWN';
	}

	/**
	 * Returns an array of all request data.
	 *
	 * @return array Associative array containing request metadata.
	 */
	public function get_data() {
		return array(
			'request_time'     => $this->request_time,
			'request_type'     => $this->request_type,
			'request_url'      => $this->request_url,
			'method'           => $this->method,
			'referer_url'      => $this->referer_url,
			'user_role'        => $this->user_role,
			'ip_address'       => $this->ip_address,
			'host'             => $this->host,
			'device'           => $this->device,
			'operating_system' => $this->operating_system,
			'browser'          => $this->browser,
			'browser_version'  => $this->browser_version,
			'user_agent'       => $this->user_agent,
			'origin'           => $this->origin,
			'accept'           => $this->accept,
			'accept_encoding'  => $this->accept_encoding,
			'accept_language'  => $this->accept_language,
			'content_type'     => $this->content_type,
			'connection_type'  => $this->connection_type,
			'cache_control'    => $this->cache_control,
			'status_code'      => $this->status_code,
		);
	}
}
