<?php
/**
 * File: /classes/controller/class-tfcm-request-abstract.php
 *
 * Abstract base class for capturing request metadata (headers, user agent, etc.).
 *
 * @package TrafficMonitor
 */

/*
 * This file contains code derived from the PHP User Agent Parser by Jesse G. Donat (donatj).
 *
 * Original work Copyright (c) [year] Jesse G. Donat
 * Licensed under the MIT License.
 *
 * You may obtain a copy of the license at:
 * https://opensource.org/licenses/MIT
 *
 * Modifications have been made to integrate this functionality into the TrafficMonitor plugin.
 */

defined( 'ABSPATH' ) || exit;

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
		$this->request_time     = current_time( 'mysql' );
		$this->request_url      = ''; // set by extended class
		$this->is_cached        = null; // set by extended class
		$this->method           = ''; // set by extended class
		$this->referer_url      = isset( $_SERVER['HTTP_REFERER'] ) ? substr( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), 0, 255 ) : '';
		$this->user_role        = self::get_user_role();
		$this->ip_address       = ''; // set by extended class
		$this->host             = $this->validate_host( $_SERVER['HTTP_HOST'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization happens within validate_host().
		$this->device           = isset( $_SERVER['HTTP_USER_AGENT'] ) ? self::get_user_agent_device( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
		$data                   = self::get_user_agent_data( $_SERVER['HTTP_USER_AGENT'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Regex requires unsanitized values in associated method.
		$this->operating_system = $data['platform'];
		$this->browser          = $data['browser'];
		$this->browser_version  = $data['browser_version'];
		$this->user_agent       = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
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
	 * Determines the device type (Mobile, Tablet, or Desktop) based on the user agent.
	 *
	 * @param string $user_agent The user agent string.
	 * @return string The detected device type.
	 */
	private static function get_user_agent_device( $user_agent ) {
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
		return $device;
	}

	/**
	 * Parses a user agent string into its important parts.
	 *
	 * This method is based on the original donatj parsing logic and returns an
	 * associative array with keys 'platform', 'browser', and 'browser_version'.
	 *
	 * @param string|null $u_agent User agent string to parse. If null, will use $_SERVER['HTTP_USER_AGENT'].
	 * @return array {
	 *     @type mixed $platform         The detected platform.
	 *     @type mixed $browser          The detected browser.
	 *     @type mixed $browser_version  The detected browser version.
	 * }
	 * @throws \InvalidArgumentException If no user agent is provided.
	 */
	private static function get_user_agent_data( $u_agent = null ) {
		if ( $u_agent === null && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$u_agent = (string) $_SERVER['HTTP_USER_AGENT']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Regex requires unsanitized values in associated method.
		}

		if ( $u_agent === null ) {
			throw new \InvalidArgumentException( 'get_user_agent_data requires a user agent' );
		}

		$platform = null;
		$browser  = null;
		$version  = null;
		$data     = array(
			'platform'        => $platform,
			'browser'         => $browser,
			'browser_version' => $version,
		);

		if ( ! $u_agent ) {
			return $data;
		}

		// Extract platform from parenthesized substring.
		if ( preg_match( '/\((.*?)\)/m', $u_agent, $parent_matches ) ) {
			preg_match_all(
				<<<'REGEX'
/(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(?:Open|Net|Free)BSD|Macintosh|
Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?)
(?:\ [^;]*)?
(?:;|$)/imx
REGEX
				,
				$parent_matches[1],
				$result
			);
			if ( $result ) {
				$priority = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish' );
				// Use strict comparisons where possible.
				$result['platform'] = array_unique( $result['platform'] );
				if ( count( $result['platform'] ) > 1 ) {
					$intersect = array_intersect( $priority, $result['platform'] );
					if ( ! empty( $intersect ) ) {
						$platform = reset( $intersect );
					} else {
						$platform = $result['platform'][0];
					}
				} elseif ( isset( $result['platform'][0] ) ) {
					$platform = $result['platform'][0];
				}
			}
		}

		// Normalize some common platform names.
		if ( $platform === 'linux-gnu' || $platform === 'X11' ) {
			$platform = 'Linux';
		} elseif ( $platform === 'CrOS' ) {
			$platform = 'Chrome OS';
		} elseif ( $platform === 'Adr' ) {
			$platform = 'Android';
		} elseif ( $platform === null ) {
			if ( preg_match_all( '%(?P<platform>Android)[:/ ]%ix', $u_agent, $result ) ) {
				$platform = $result['platform'][0];
			}
		}

		// Extract browser and version.
		preg_match_all(
			<<<'REGEX'
%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|YaApp_Android|Whale|
Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|ChatGPT-User|GPTBot|OAI-SearchBot|
Valve\ Steam\ Tenfoot|Mastodon|
NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)
\)?;?
(?:[:/ ](?P<version>[0-9A-Z.]+)|/[A-Z]*)
%ix
REGEX,
			$u_agent,
			$result
		);

		if ( $result ) {
			if ( ! isset( $result['browser'][0], $result['version'][0] ) ) {
				if ( preg_match( '%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)([/ :](?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result ) ) {
					return array(
						'platform'        => $platform ? $platform : null,
						'browser'         => $result['browser'],
						'browser_version' => empty( $result['version'] ) ? null : $result['version'],
					);
				}
				return $data;
			}
		} else {
			// If no matches, try fallback.
			if ( preg_match( '%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)([/ :](?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result ) ) {
				return array(
					'platform'        => $platform ? $platform : null,
					'browser'         => $result['browser'],
					'browser_version' => empty( $result['version'] ) ? null : $result['version'],
				);
			}
			return $data;
		}

		// Check for IE's rv: version pattern.
		if ( preg_match( '/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result ) ) {
			$rv_result = $rv_result['version'];
		} else {
			$rv_result = null;
		}

		$browser = $result['browser'][0];
		$version = $result['version'][0];

		$lower_browser = array_map( 'strtolower', $result['browser'] );

		// Helper closures.
		$find = function ( $search, &$key = null, &$value = null ) use ( $lower_browser ) {
			$search = (array) $search;
			foreach ( $search as $val ) {
				$xkey = array_search( strtolower( $val ), $lower_browser, true );
				if ( $xkey !== false ) {
					$value = $val;
					$key   = $xkey;
					return true;
				}
			}
			return false;
		};

		$find_t = function ( array $search, &$key = null, &$value = null ) use ( $find ) {
			$value2 = null;
			if ( $find( array_keys( $search ), $key, $value2 ) ) {
				$value = $search[ $value2 ];
				return true;
			}
			return false;
		};

		$key = 0;
		$val = '';
		if ( $find_t(
			array(
				'OPR'                => 'Opera',
				'Facebot'            => 'iMessageBot',
				'UCBrowser'          => 'UC Browser',
				'YaBrowser'          => 'Yandex',
				'YaApp_Android'      => 'Yandex',
				'Iceweasel'          => 'Firefox',
				'Icecat'             => 'Firefox',
				'CriOS'              => 'Chrome',
				'Edg'                => 'Edge',
				'EdgA'               => 'Edge',
				'XiaoMi/MiuiBrowser' => 'MiuiBrowser',
			),
			$key,
			$browser
		) ) {
			$version = is_numeric( substr( $result['version'][ $key ], 0, 1 ) ) ? $result['version'][ $key ] : null;
		} elseif ( $find( 'Playstation Vita', $key, $platform ) ) {
			$platform = 'PlayStation Vita';
			$browser  = 'Browser';
		} elseif ( $find( array( 'Kindle Fire', 'Silk' ), $key, $val ) ) {
			$browser  = ( $val === 'Silk' ) ? 'Silk' : 'Kindle';
			$platform = 'Kindle Fire';
			$version  = $result['version'][ $key ];
			if ( ! $version || ! is_numeric( $version[0] ) ) {
				$version = $result['version'][ array_search( 'Version', $result['browser'], true ) ];
			}
		} elseif ( $find( 'NintendoBrowser', $key ) || $platform === 'Nintendo 3DS' ) {
			$browser = 'NintendoBrowser';
			$version = $result['version'][ $key ];
		} elseif ( $find( array( 'Kindle' ), $key, $platform ) ) {
			$browser = $result['browser'][ $key ];
			$version = $result['version'][ $key ];
		} elseif ( $find( 'Opera', $key, $browser ) ) {
			$find( 'Version', $key );
			$version = $result['version'][ $key ];
		} elseif ( $find( 'Puffin', $key, $browser ) ) {
			$version = $result['version'][ $key ];
			if ( strlen( $version ) > 3 ) {
				$part = substr( $version, -2 );
				if ( ctype_upper( $part ) ) {
					$version = substr( $version, 0, -2 );
					$flags   = array(
						'IP' => 'iPhone',
						'IT' => 'iPad',
						'AP' => 'Android',
						'AT' => 'Android',
						'WP' => 'Windows Phone',
						'WT' => 'Windows',
					);
					if ( isset( $flags[ $part ] ) ) {
						$platform = $flags[ $part ];
					}
				}
			}
		} elseif ( $find(
			array(
				'Googlebot',
				'Applebot',
				'IEMobile',
				'Edge',
				'Midori',
				'Whale',
				'Vivaldi',
				'OculusBrowser',
				'SamsungBrowser',
				'Valve Steam Tenfoot',
				'Chrome',
				'HeadlessChrome',
				'SailfishBrowser',
			),
			$key,
			$browser
		) ) {
			$version = $result['version'][ $key ];
		} elseif ( $rv_result && $find( 'Trident', $key ) ) {
			$browser = 'MSIE';
			$version = $rv_result;
		} elseif ( $browser === 'AppleWebKit' ) {
			if ( $platform === 'Android' ) {
				$browser = 'Android Browser';
			} elseif ( strpos( (string) $platform, 'BB' ) === 0 ) {
				$browser  = 'BlackBerry Browser';
				$platform = 'BlackBerry';
			} elseif ( $platform === 'BlackBerry' || $platform === 'PlayBook' ) {
				$browser = 'BlackBerry Browser';
			} elseif ( $find( 'Safari', $key, $browser ) || $find( 'TizenBrowser', $key, $browser ) ) {
				$version = $result['version'][ $key ];
			} elseif ( count( $result['browser'] ) ) {
				$key     = count( $result['browser'] ) - 1;
				$browser = $result['browser'][ $key ];
				$version = $result['version'][ $key ];
			}
			if ( $find( 'Version', $key ) ) {
				$version = $result['version'][ $key ];
			}
		} elseif ( preg_grep( '/playstation \d/i', $result['browser'] ) ) {
			$p_key    = reset( preg_grep( '/playstation \d/i', $result['browser'] ) );
			$platform = 'PlayStation ' . preg_replace( '/\D/', '', $p_key );
			$browser  = 'NetFront';
		}

		return array(
			'platform'        => $platform,
			'browser'         => $browser,
			'browser_version' => $version,
		);
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
