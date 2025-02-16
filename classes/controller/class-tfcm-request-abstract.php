<?php
/**
 * File: /classes/controller/class-tfcm-request-abstract.php
 *
 * Abstract base class for capturing request metadata (headers, user agent, etc.).
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

// Cconsider switching to https://developers.whatismybrowser.com/api/ .
use donatj\UserAgent\UserAgentParser;

/**
 * Abstract Class TFCM_Request_Abstract
 *
 * Provides the base structure and common methods for capturing and processing request data.
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
	 * Constructs the request object and initializes default properties.
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
	 * Retrieves the current user's role.
	 *
	 * Returns 'visitor' if no user is logged in; otherwise, returns the user's first role.
	 *
	 * @return string The current user role.
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
	 * Determines the type of the current request.
	 *
	 * Checks for AJAX, API, CLI, HTTP, XML-RPC, WebSocket, etc.
	 *
	 * @return string The determined request type.
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
	 * Parses the User-Agent string to extract device, operating system, and browser details.
	 *
	 * @param string|null $user_agent Optional User-Agent string; defaults to $_SERVER['HTTP_USER_AGENT'].
	 * @return array An associative array with keys: device, operating_system, browser, browser_version, user_agent.
	 */
	protected function parse_user_agent( $user_agent = null ) {
		$user_agent = isset( $user_agent ) ? wp_unslash( $user_agent ) : '';

		// If user agent is empty, set device to ''
		$device = '';
		if ( '' !== $user_agent ) {
			if ( stripos( $user_agent, 'Mobile' ) !== false ) {
				$device = 'Mobile';
			} elseif ( stripos( $user_agent, 'Tablet' ) !== false || stripos( $user_agent, 'iPad' ) !== false ) {
				$device = 'Tablet';
			} else {
				$device = 'Desktop';
			}
		}

		$parser = new UserAgentParser();
		$ua     = $parser->parse( $user_agent );

		return array(
			'device'           => $device,
			'operating_system' => sanitize_text_field( $ua->platform() ),
			'browser'          => sanitize_text_field( $ua->browser() ),
			'browser_version'  => sanitize_text_field( $ua->browserVersion() ),
			'user_agent'       => sanitize_text_field( $user_agent ),
		);
	}

	/**
	 * Validates and sanitizes the host name.
	 *
	 * @param string $host The host to validate.
	 * @return string A valid host name, or an empty string if invalid.
	 */
	protected function validate_host( $host ) {
		$validated_host = isset( $host ) ? sanitize_text_field( wp_unslash( $host ) ) : '';
		$validated_host = filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME );
		return false !== $validated_host ? $validated_host : '';
	}

	/**
	 * Retrieves an associative array of all request properties.
	 *
	 * @return array Associative array of request metadata.
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
