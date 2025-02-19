<?php
/**
 * Plugin Name: Traffic Monitor
 * Plugin URI: https://github.com/dmitrimartin817/traffic-monitor
 * Description: Monitor and log HTTP traffic, including headers and User-Agent details, directly from your WordPress admin panel.
 * Version: 2.2.1
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
define( 'TRAFFIC_MONITOR_VERSION', '2.2.1' );
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
 * This function filters out unwanted AJAX, Admin, API, CLI, Cron, XMLRPC, and WebSocket requests
 * and delegates valid requests to the TFCM_Request_Controller for logging.
 *
 * @return void
 */
function tfcm_handle_requests() {
	// Only log HTTP requests via init. Exclude AJAX, Admin, API, CLI, Cron, XMLRPC, and WebSocket requests
	if ( isset( $_SERVER['REQUEST_METHOD'] ) &&
		! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
		! ( is_admin() ) &&
		! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) &&
		! ( defined( 'WP_CLI' ) ) &&
		! ( defined( 'DOING_CRON' ) && DOING_CRON ) &&
		! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) &&
		! ( isset( $_SERVER['HTTP_UPGRADE'] ) && 'websocket' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_UPGRADE'] ) ) ) ) ) {

		global $tfcm_request_type;
		$tfcm_request_type = 'HTTP';
		TFCM_Request_Controller::handle_request();
	}
}
add_action( 'init', 'tfcm_handle_requests' );
