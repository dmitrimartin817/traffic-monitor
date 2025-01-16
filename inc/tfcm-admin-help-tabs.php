<?php
/**
 * Traffic Monitor - Help File
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds help tabs to the Traffic Monitor admin page.
 *
 * This function creates multiple help tabs for the Traffic Monitor plugin's admin page,
 * providing guidance on usage, bulk actions, search functionality, and available columns.
 *
 * @return void
 */
function tfcm_add_help_tab() {
	$screen = get_current_screen();

	// Ensure we are on the Traffic Monitor screen.
	if ( 'toplevel_page_traffic-monitor' !== $screen->id ) {
		return;
	}

	// Content for the Instructions Help Tab.
	$instructions_content  = '<h3>Instructions and Use Cases</h3>';
	$instructions_content .= '<p>The Traffic Monitor plugin is a simple tool for logging, managing, and analyzing HTTP traffic directly from your WordPress admin panel. This plugin is particularly useful for:</p>';
	$instructions_content .= '<ul>';
	$instructions_content .= '<li><strong>Debugging:</strong> Identify issues with HTTP requests, such as broken links, incorrect headers, or unauthorized access attempts.</li>';
	$instructions_content .= '<li><strong>Performance Monitoring:</strong> Understand which resources are frequently accessed and optimize your site accordingly.</li>';
	$instructions_content .= '<li><strong>Security Analysis:</strong> Monitor for suspicious traffic patterns, potential DDoS attacks, or unauthorized API usage.</li>';
	$instructions_content .= '<li><strong>User Behavior Analysis:</strong> Analyze referrer data and User-Agent strings to better understand your audience’s devices and browsing habits.</li>';
	$instructions_content .= '</ul>';
	$instructions_content .= '<p>Click on the help tabs for detailed instructions and descriptions of the data available.</p>';

	// Content for the Bulk Actions Help Tab.
	$bulk_options  = '<h3>Bulk Actions</h3>';
	$bulk_options .= '<ul>';
	$bulk_options .= '<li><strong>Delete Selected:</strong> Deletes the selected records from the log.</li>';
	$bulk_options .= '<li><strong>Delete All:</strong> Deletes ALL records from the log, including those not currently displayed.</li>';
	$bulk_options .= '<li><strong>Export Selected:</strong> Creates a link to a CSV file to download the selected records.</li>';
	$bulk_options .= '<li><strong>Export All:</strong> Creates a link to a CSV file to download ALL records, including those not currently displayed.</li>';
	$bulk_options .= '</ul>';

	// Content for the Screen Options Help Tab.
	$screen_options  = '<h3>Screen Options</h3>';
	$screen_options .= '<p>Click the <strong>Screen Options</strong> tab in the top-right corner to customize the columns displayed and the number of items per page.</p>';

	// Content for the Search Function Help Tab.
	$search_function  = '<h3>Search Function</h3>';
	$search_function .= '<p>Use the search box above the table to filter records. You can search based on:</p>';
	$search_function .= '<ul>';
	$search_function .= '<li><strong>Date:</strong> Search for dates or times stored in YYYY-MM-DD HH:MM:SS format.</li>';
	$search_function .= '<li><strong>Resource:</strong> Search the web addresses of the pages requested.</li>';
	$search_function .= '<li><strong>Prior Page:</strong> Search the web addresses of the pages visited before the requested pages.</li>';
	$search_function .= '<li><strong>Unparsed User Agent:</strong> Search User-Agent profile content left after removing the System, Device, Browser, and Browser Version.</li>';
	$search_function .= '</ul>';

	// Content for the Default Columns Help Tab.
	$default_columns_content  = '<h3>Default Columns</h3>';
	$default_columns_content .= '<p>The following columns are displayed by default in the Traffic Monitor log:</p>';
	$default_columns_content .= '<ul>';
	$default_columns_content .= '<li><strong>Date (request_time):</strong> Timestamp of the request.</li>';
	$default_columns_content .= '<li><strong>Resource (request_url):</strong> Page being requested.</li>';
	$default_columns_content .= '<li><strong>Method (method):</strong> HTTP method used (GET, POST, PUT, etc.).</li>';
	$default_columns_content .= '<li><strong>Prior Page (referer):</strong> URL of the previous page the client came from.</li>';
	$default_columns_content .= '<li><strong>IP Address (referer):</strong> Unique number of the internet router a request came from, unless proxied.</li>';
	$default_columns_content .= '<li><strong>System (os):</strong> Operating system name (e.g., Windows, macOS, Linux, Android, iOS). Parsed from User-Agent.</li>';
	$default_columns_content .= '<li><strong>Device (device):</strong> Device type (e.g., Mobile, Tablet, Desktop). Parsed from User-Agent.</li>';
	$default_columns_content .= '<li><strong>Browser (browser):</strong> Browser name (e.g., Chrome, Firefox, Safari). Parsed from User-Agent.</li>';
	$default_columns_content .= '</ul>';

	// Content for the Other Columns Help Tab.
	$other_columns_content  = '<h3>Other Columns</h3>';
	$other_columns_content .= '<p>The following columns are included when exporting data for analysis:</p>';
	$other_columns_content .= '<ul>';
	$other_columns_content .= '<li><strong>Browser Version (browser_version):</strong> Browser version (e.g., 114.0.0). Parsed from User-Agent.</li>';
	$other_columns_content .= '<li><strong>User Agent (user_agent):</strong> Description of the software used to make the request.</li>';
	$other_columns_content .= '<li><strong>Origin (origin):</strong> Origin of the request, mainly for cross-origin requests (CORS).</li>';
	$other_columns_content .= '<li><strong>Original IP (x_real_ip):</strong> Real client IP behind a proxy (if set).</li>';
	$other_columns_content .= '<li><strong>IP Chain (x_forwarded_for):</strong> List of client IPs in the forwarding chain (comma-separated).</li>';
	$other_columns_content .= '<li><strong>Forwarding Info (forwarded):</strong> Standardized header providing structured forwarding details (e.g., IPs, protocol, host).</li>';
	$other_columns_content .= '<li><strong>Final Host (host):</strong> Domain or subdomain the client is requesting (useful for multi-site or debugging).</li>';
	$other_columns_content .= '<li><strong>Original Host (x_forwarded_host):</strong> Original host header value before proxy modifications.</li>';
	$other_columns_content .= '<li><strong>MIME (accept):</strong> MIME types the client can handle in the response (e.g., text/html, application/json).</li>';
	$other_columns_content .= '<li><strong>Compression (accept_encoding):</strong> Compression algorithms supported by the client (e.g., gzip, deflate, br).</li>';
	$other_columns_content .= '<li><strong>Language (accept_language):</strong> Preferred languages.</li>';
	$other_columns_content .= '<li><strong>Media Type (content_type):</strong> Media type of the request body (e.g., application/json, text/plain).</li>';
	$other_columns_content .= '<li><strong>Connection (connection):</strong> Connection header indicating client preferences (e.g., keep-alive, close).</li>';
	$other_columns_content .= '<li><strong>Caching (cache_control):</strong> Caching preferences.</li>';
	$other_columns_content .= '<li><strong>Response (status_code):</strong> HTTP response status code sent by the server (e.g., 200, 404, 500).</li>';
	$other_columns_content .= '</ul>';

	// Content for the Troubleshooting Help Tab.
	$troubleshooting_content  = '<h3>Troubleshooting</h3>';
	$troubleshooting_content .= '<p>If you are experiencing issues with logging in Traffic Monitor, here are some common scenarios and solutions:</p>';
	$troubleshooting_content .= '<p><strong>Request Not Logged:</strong> </p>';
	$troubleshooting_content .= '<ul>';
	$troubleshooting_content .= '<li>Some caching plugins and CDN services serve cached HTML pages. This bypasses the ability of the server to process those page requests. If you want Traffic Monitor to log them, disable page caching.</li>';
	$troubleshooting_content .= '<li>Some CDNs, name servers, or web hosts may block suspicious requests. If you want Traffic Monitor to log those requests, disable bot blocking and other related security features.</li>';
	$troubleshooting_content .= '</ul>';
	$troubleshooting_content .= '<p><strong>Missing or Incorrect IP Addresses:</strong> Services like Cloudflare or Sucuri act as reverse proxies and may alter headers or mask client IPs. To address this:</p>';
	$troubleshooting_content .= '<ul>';
	$troubleshooting_content .= '<li>If you are using Cloudflare, disable the proxy mode (gray cloud) in your DNS settings to bypass its proxy layer.</li>';
	$troubleshooting_content .= '<li>Check the Client IP or IP Chain columns for original IP addresses. Traffic Monitor logs these when present.</li>';
	$troubleshooting_content .= '</ul>';
	$troubleshooting_content .= '<p>If issues persist, support is available by email at dmitriamartin@gmail.com.</p>';

	// Add the Instructions Help Tab.
	$screen->add_help_tab(
		array(
			'id'      => 'traffic_monitor_instructions',
			'title'   => 'Instructions',
			'content' => $instructions_content,
		)
	);

	// Add other help tabs.
	$screen->add_help_tab(
		array(
			'id'      => 'traffic_monitor_bulk_options',
			'title'   => 'Bulk Actions',
			'content' => $bulk_options,
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'traffic_monitor_screen_options',
			'title'   => 'Screen Options',
			'content' => $screen_options,
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
			'id'      => 'traffic_monitor_default_columns',
			'title'   => 'Default Columns',
			'content' => $default_columns_content,
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'traffic_monitor_other_columns',
			'title'   => 'Other Columns',
			'content' => $other_columns_content,
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'traffic_monitor_troubleshooting',
			'title'   => 'Troubleshooting',
			'content' => $troubleshooting_content,
		)
	);
}
add_action( 'current_screen', 'tfcm_add_help_tab' );
