<?php
/**
 * Plugin Name: Traffic Monitor
 * Plugin URI: https://github.com/dmitrimartin817/traffic-monitor
 * Description: Monitor and log HTTP traffic, including headers and User-Agent details, directly from your WordPress admin panel.
 * Version: 2.1.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Dmitri Martin
 * Author URI: https://www.linkedin.com/in/dmitriamartin/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: traffic-monitor
 *
 * @package TrafficMonitor
 */

// traffic-monitor.php

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

global $wpdb;

// define( 'TFCM_IP_TABLE', $wpdb->prefix . 'tfcm_ip_addresses' );
// define( 'TFCM_USER_AGENT_TABLE', $wpdb->prefix . 'tfcm_user_agents' );
// define( 'TFCM_FINGERPRINT_TABLE', $wpdb->prefix . 'tfcm_fingerprints' );
// define( 'TFCM_REQUESTED_PAGES_TABLE', $wpdb->prefix . 'tfcm_requested_pages' );
// define( 'TFCM_REFERRER_PAGES_TABLE', $wpdb->prefix . 'tfcm_referrer_pages' );
define( 'TFCM_REQUEST_LOG_TABLE', $wpdb->prefix . 'tfcm_request_log' );
define( 'TRAFFIC_MONITOR_VERSION', '2.1.1' );
define( 'TFCM_PLUGIN_FILE', __FILE__ );
define( 'TFCM_PLUGIN_DIR', plugin_dir_path( TFCM_PLUGIN_FILE ) );

// Load Dependencies.
require_once TFCM_PLUGIN_DIR . 'vendor/autoload.php';

// Load Controler classes.
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-lifecycle.php'; // load this first!
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-admin-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-assets.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-export-manager.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-log-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-plugin-links-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-controller.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-abstract.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-ajax.php';
require_once TFCM_PLUGIN_DIR . 'classes/controller/class-tfcm-request-http.php';

// Load Model classes.
require_once TFCM_PLUGIN_DIR . 'classes/model/class-tfcm-database.php';

// Load View classes.
require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-help-tabs.php';
require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-log-table.php';
require_once TFCM_PLUGIN_DIR . 'classes/view/class-tfcm-view.php';

// Register class hooks.
TFCM_Assets::register_hooks();
TFCM_Lifecycle::register_hooks();
TFCM_Plugin_Links_Controller::register_hooks();
TFCM_Help_Tabs::register_hooks();
TFCM_Admin_Controller::register_hooks();
TFCM_Request_Controller::register_hooks();

/**
 * Handles incoming HTTP requests and delegates logging.
 *
 * This function filters out unwanted AJAX requests (such as Heartbeat and Traffic Monitor's own AJAX calls)
 * and delegates valid requests to the TFCM_Request_Controller for logging.
 *
 * @return void
 */
function tfcm_handle_requests() {
	// Ignore WordPress Heartbeat API requests.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No nonce needed, just checking if this is a heartbeat request.
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';

		if ( 'heartbeat' === $action ) {
			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

			if ( false !== strpos( $referer, admin_url() ) ) {
				return;
			}
		}

		// Bypass WordPress Admin AJAX Requests
		$allowed_admin_ajax = array(
			'save_user_options',
			'update_screen_option',
			'hidden-columns',
		);
		if ( in_array( $action, $allowed_admin_ajax, true ) ) {
			return; // Let WordPress handle these AJAX requests
		}

		// Bypass Traffic Monitor's AJAX Requests (Handled Separately)
		if ( 'tfcm_log_ajax_request' === $action ) {
			// error_log( 'Skipping request in init because it is an AJAX request to detect cached pages which will be handeled via hooks in TFCM_Request_Controller.' );
			return;
		}
		if ( 'tfcm_handle_bulk_action' === $action ) {
			// error_log( 'Skipping request in init because it is an AJAX request to process bulk action which will be handeled via hook in TFCM_Request_Controller.' );
			return;
		}
	}

	TFCM_Request_Controller::handle_request();
}
add_action( 'init', 'tfcm_handle_requests' );
