<?php
/**
 * File: /classes/view/class-tfcm-help-tabs.php
 *
 * This file defines the TFCM_Help_Tabs class, which is responsible for adding help tabs
 * to the Traffic Monitor admin page in WordPress. These tabs provide users with guidance
 * on how to use the plugin, including instructions, bulk actions, search functionality,
 * column definitions, and troubleshooting information.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Help_Tabs
 *
 * Provides methods to register and add help tabs to the Traffic Monitor admin page.
 * All methods in this class are declared as public static because they do not require
 * object state and are used purely as utility callbacks.
 */
class TFCM_Help_Tabs {
	/**
	 * Registers the help tab functionality.
	 *
	 * Adds the 'add_help_tab' callback to the 'admin_head' action so that help tabs
	 * are added when the admin header is rendered.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_head', array( __CLASS__, 'add_help_tab' ) );
	}

	/**
	 * Adds help tabs to the Traffic Monitor admin screen.
	 *
	 * Retrieves the current screen. If it matches the Traffic Monitor admin page,
	 * it adds several help tabs with detailed content covering instructions, bulk actions,
	 * search functionality, column definitions, and troubleshooting advice.
	 *
	 * @return void
	 */
	public static function add_help_tab() {
		$screen = get_current_screen();

		// Ensure we are on the Traffic Monitor screen.
		if ( 'toplevel_page_traffic-monitor' !== $screen->id ) {
			return;
		}

		$instructions  = '<h3>Instructions and Use Cases</h3>';
		$instructions .= '<p>The Traffic Monitor plugin logs, manages, and analyzes page requests directly in your WordPress admin panel. It is useful for::</p>';
		$instructions .= '<ul>';
		$instructions .= '<li><strong>Debugging:</strong> Identify broken links, incorrect headers, or unexpected request behaviors.</li>';
		$instructions .= '<li><strong>Performance Monitoring:</strong> Track frequently accessed pages to optimize site speed and other improvements.</li>';
		$instructions .= '<li><strong>Security Analysis:</strong> Detect unusual traffic patterns, bot activity, and potential attacks (DDoS, brute force, etc.).</li>';
		$instructions .= '<li><strong>User Behavior Analysis:</strong> Analyze visitor sources, devices, operating systems, and browsers.</li>';
		$instructions .= '<li><strong>Click Fraud Detection:</strong> Identify multiple rapid clicks from the same IP and user-agent combination..</li>';
		$instructions .= '</ul>';
		$instructions .= '<p>Click on the help tabs for detailed instructions on available features.</p>';

		$bulk_options  = '<h3>Bulk Actions</h3>';
		$bulk_options .= '<p><strong>Managing Selected Records:</strong> To apply actions to specific records, select them using the checkboxes, then choose an action from the dropdown.</p>';
		$bulk_options .= '<ul>';
		$bulk_options .= '<li><strong>Delete:</strong> Permanently removes selected log entries.</li>';
		$bulk_options .= '<li><strong>Export:</strong> Generates a downloadable CSV file of the selected logs.</li>';
		$bulk_options .= '</ul>';
		$bulk_options .= '<p><strong>Managing All Records:</strong> For bulk actions on all logs, use the buttons next to the bulk actions dropdown.</p>';
		$bulk_options .= '<ul>';
		$bulk_options .= '<li><strong>Delete All:</strong> Permanently removes all logs, including those not currently displayed.</li>';
		$bulk_options .= '<li><strong>Export All:</strong> Generates a CSV file with all logs, including those not currently displayed.</li>';
		$bulk_options .= '</ul>';

		$search_function  = '<h3>Search Functionality</h3>';
		$search_function .= '<p>Use the search box above the log table to filter records. Searches apply to the following fields:</p>';
		$search_function .= '<ul>';
		$search_function .= '<li><strong>Date:</strong> Search by request date/time (YYYY-MM-DD HH:MM:SS format).</li>';
		$search_function .= '<li><strong>Page Requested:</strong> Find requests for specific URLs.</li>';
		$search_function .= '<li><strong>Method:</strong> Filter by request method (GET, POST, etc.).</li>';
		$search_function .= '<li><strong>Prior Page:</strong> Find requests referred from specific URLs.</li>';
		$search_function .= '<li><strong>User Role:</strong> Search by WordPress user role (admin, editor, subscriber, etc.).</li>';
		$search_function .= '<li><strong>IP Address:</strong> Locate requests from specific IP addresses.</li>';
		$search_function .= '<li><strong>User Agent:</strong> Identify traffic from specific browsers or bots.</li>';
		$search_function .= '<li><strong>Origin:</strong> Search by hostname or port to analyze cross-origin requests.</li>';
		$search_function .= '<li><strong>Status Code:</strong> Find responses like 200 OK, 404 Not Found, or 500 Internal Server Error.</li>';
		$search_function .= '</ul>';

		$columns  = '<h3>Column Descriptions</h3>';
		$columns .= '<p><strong>Primary Request Data</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Date (request_time):</strong> Timestamp of the request.</li>';
		$columns .= '<li><strong>Page Requested (request_url):</strong> The URL of the requested page.</li>';
		$columns .= '<li><strong>Cached (is_cached):</strong> If the page was served from cache instead of handled by WordPress.</li>';
		$columns .= '<li><strong>Method (method):</strong> HTTP request method (GET, POST, etc.).</li>';
		$columns .= '<li><strong>Prior Page (referer_url):</strong> URL of the page the client came from.</li>';
		$columns .= '</ul>';
		$columns .= '<p><strong>User Information</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>User Role (user_role):</strong> WordPress user role (admin, editor, subscriber, etc.).</li>';
		$columns .= '<li><strong>IP Address (ip_address):</strong> Last known public IP address of the requester.</li>';
		$columns .= '<li><strong>Host (host):</strong> Final host where the request was directed.</li>';
		$columns .= '</ul>';
		$columns .= '<p><strong>Device Information</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Device (device):</strong> Device type (e.g., Mobile, Tablet, Desktop). Parsed from User-Agent.</li>';
		$columns .= '<li><strong>System (operating_system):</strong> Operating system name (e.g., Windows, macOS, Linux, Android, iOS). Parsed from User-Agent.</li>';
		$columns .= '<li><strong>Browser (browser):</strong> Browser name (e.g., Chrome, Firefox, Safari). Parsed from User-Agent.</li>';
		$columns .= '<li><strong>Browser Version (browser_version):</strong> Browser version (e.g., 114.0.0). Parsed from User-Agent.</li>';
		$columns .= '<li><strong>User Agent (user_agent):</strong> Description of the software used to make the request.</li>';
		$columns .= '</ul>';
		$columns .= '<p><strong>Other Headers</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Origin (origin):</strong> Origin of the request, mainly for cross-origin requests (CORS).</li>';
		$columns .= '<li><strong>MIME (accept):</strong> MIME types accepted by the client (e.g., text/html, application/json).</li>';
		$columns .= '<li><strong>Compression (accept_encoding):</strong> Compression algorithms the client supports (e.g., gzip, deflate, br).</li>';
		$columns .= '<li><strong>Language (accept_language):</strong> Preferred language(s) sent in the request.</li>';
		$columns .= '<li><strong>Media Type (content_type):</strong> Content type of the request (application/json, text/html, etc.).</li>';
		$columns .= '<li><strong>Connection (connection_type):</strong> Connection preference (keep-alive, close).</li>';
		$columns .= '<li><strong>Caching (cache_control):</strong> Cache control settings from the request headers.</li>';
		$columns .= '</ul>';
		$columns .= '<p><strong>Response Data</strong></p>';
		$columns .= '<ul>';
		$columns .= '<li><strong>Status Code (status_code):</strong> HTTP response code (200, 404, 500, etc.).</li>';
		$columns .= '</ul>';

		$troubleshooting  = '<h3>Troubleshooting</h3>';
		$troubleshooting .= '<p>If Traffic Monitor isn’t logging requests as expected, check the following:</p>';
		$troubleshooting .= '<p><strong>Page Request Not Logged:</strong> </p>';
		$troubleshooting .= '<ul>';
		$troubleshooting .= '<li>The person or bot making the request may have Javascript disabled and the page may be cached.  Traffic Monitor can log cached pages but only if Javascript can run on the page once it is recieved.  All requests for cached pages will have the AJAX request type.</li>';
		$troubleshooting .= '<li>Some CDNs, name servers, or web hosts may block bots. To log bot traffic, turn off bot blocking.</li>';
		$troubleshooting .= '</ul>';
		$troubleshooting .= '<p><strong>Missing or Incorrect IP Addresses:</strong></p>';
		$troubleshooting .= '<ul>';
		$troubleshooting .= '<li>Services like Cloudflare and Sucuri act as proxies and may alter headers, masking real IPs</li>';
		$troubleshooting .= '<li>If Cloudflare is used, turn off proxy mode (gray cloud) in DNS settings to reveal visitor IPs.</li>';
		$troubleshooting .= '<li>Some visitors may insert fake IP addresses so their activity can’t be tracked.</li>';
		$troubleshooting .= '</ul>';
		$troubleshooting .= '<p>For additional support, contact dmitri.amartin@viablepress.com.</p>';

		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_instructions',
				'title'   => 'Instructions',
				'content' => $instructions,
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_bulk_options',
				'title'   => 'Bulk Actions',
				'content' => $bulk_options,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_search_function',
				'title'   => 'Search Function',
				'content' => $search_function,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_columns',
				'title'   => 'Column Definitions',
				'content' => $columns,
			)
		);
		$screen->add_help_tab(
			array(
				'id'      => 'traffic_monitor_troubleshooting',
				'title'   => 'Troubleshooting',
				'content' => $troubleshooting,
			)
		);
	}
}
