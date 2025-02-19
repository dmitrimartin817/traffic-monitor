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
	public $request_url;
	public $is_cached;
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
		$user_agent_data = $this->parse_user_agent( $_SERVER['HTTP_USER_AGENT'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization happens within parse_user_agent().

		$this->request_time     = current_time( 'mysql' );
		$this->request_url      = ''; // set by extended class
		$this->is_cached        = null; // set by extended class
		$this->method           = ''; // set by extended class
		$this->referer_url      = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 255 ) : '';
		$this->user_role        = self::get_user_role();
		$this->ip_address       = ''; // set by extended class
		$this->host             = $this->validate_host( $_SERVER['HTTP_HOST'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization happens within validate_host().
		$this->device           = $user_agent_data['device'];
		$this->operating_system = $user_agent_data['operating_system'];
		$this->browser          = $user_agent_data['browser'];
		$this->browser_version  = $user_agent_data['browser_version'];
		$this->user_agent       = $user_agent_data['user_agent'];
		$this->origin           = isset( $_SERVER['HTTP_ORIGIN'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$this->accept           = ''; // set by extended class
		$this->accept_encoding  = isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) : '';
		$this->accept_language  = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
		$this->content_type     = ''; // set by extended class
		$this->connection_type  = ''; // set by extended class
		$this->cache_control    = ''; // set by extended class
		$this->status_code      = http_response_code();
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
		$host           = isset( $host ) ? sanitize_text_field( wp_unslash( $host ) ) : '';
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
			'request_url'      => $this->request_url,
			'is_cached'        => $this->is_cached,
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
