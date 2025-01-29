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

	$instructions  = '<h3>Instructions and Use Cases</h3>';
	$instructions .= '<p>The Traffic Monitor plugin is a simple tool for logging, managing, and analyzing HTTP traffic directly from your WordPress admin panel. This plugin is handy for:</p>';
	$instructions .= '<ul>';
	$instructions .= '<li><strong>Debugging:</strong> Identify broken links, incorrect headers, or unexpected request behaviors.</li>';
	$instructions .= '<li><strong>Performance Monitoring:</strong> Analyze frequently accessed pages and optimize site speed accordingly.</li>';
	$instructions .= '<li><strong>Security Analysis:</strong> Detect unusual traffic patterns, excessive bot activity, or malicious attacks (DDoS, brute force, etc.).</li>';
	$instructions .= '<li><strong>User Behavior Analysis:</strong> See where your visitors come from and what devices, operating systems, and browsers they use.</li>';
	$instructions .= '<li><strong>Click Fraud Detection:</strong> Spot multiple rapid clicks from the same IP address and user-agent combination.</li>';
	$instructions .= '</ul>';
	$instructions .= '<p>Click on the help tabs for detailed instructions and descriptions of the data available.</p>';

	$bulk_options  = '<h3>Bulk Actions</h3>';
	$bulk_options .= '<p><strong>Selected Records:</strong> Select records to delete or export, then select and apply either of the following actions.</p>';
	$bulk_options .= '<ul>';
	$bulk_options .= '<li><strong>Delete:</strong> Deletes the selected records from the log.</li>';
	$bulk_options .= '<li><strong>Export:</strong> Creates a link to a CSV file to download the selected records.</li>';
	$bulk_options .= '</ul>';
	$bulk_options .= '<p><strong>All Records:</strong> Select either of the following buttons.</p>';
	$bulk_options .= '<ul>';
	$bulk_options .= '<li><strong>Delete All:</strong> Deletes ALL records from the log, including those not currently displayed.</li>';
	$bulk_options .= '<li><strong>Export All:</strong> Creates a link to a CSV file to download ALL records, including those not currently displayed.</li>';
	$bulk_options .= '</ul>';

	$search_function  = '<h3>Search Function</h3>';
	$search_function .= '<p>Use the search box above the table to filter records. You can search based on:</p>';
	$search_function .= '<ul>';
	$search_function .= '<li><strong>Date:</strong> Search for dates or times stored in YYYY-MM-DD HH:MM:SS format.</li>';
	$search_function .= '<li><strong>Resource:</strong> Search the web addresses of the pages requested.</li>';
	$search_function .= '<li><strong>Prior Page:</strong> Search the web addresses of the pages visited before the requested pages.</li>';
	$search_function .= '<li><strong>Unparsed User Agent:</strong> Search User-Agent profile content left after removing the System, Device, Browser, and Browser Version.</li>';
	$search_function .= '</ul>';

	$columns  = '<h3>Column Descriptions</h3>';
	$columns .= '<p><strong>Primary Request Data</strong></p>';
	$columns .= '<ul>';
	$columns .= '<li><strong>Date (request_time):</strong> Timestamp of the request.</li>';
	$columns .= '<li><strong>Resource (request_url):</strong> The page being requested.</li>';
	$columns .= '<li><strong>Method (method):</strong> HTTP method (GET, POST, PUT, etc.).</li>';
	$columns .= '<li><strong>Prior Page (referer_url):</strong> URL of the previous page the client came from.</li>';
	$columns .= '</ul>';
	$columns .= '<p><strong>User and Information</strong></p>';
	$columns .= '<ul>';
	$columns .= '<li><strong>User Role (user_role):</strong> Visitor, subscriber, contributor, author, editor, or administrator.</li>';
	$columns .= '<li><strong>IP Address (ip_address):</strong> The unique number of the internet router a request came from, unless proxied.</li>';
	$columns .= '<li><strong>Original IP (x_real_ip):</strong> Real client IP behind a proxy (if set).</li>';
	$columns .= '<li><strong>IP Chain (x_forwarded_for):</strong> List of client IPs in the forwarding chain (comma-separated).</li>';
	$columns .= '<li><strong>Forwarding Info (forwarded):</strong> Standardized header providing structured forwarding details (e.g., IPs, protocol, host).</li>';
	$columns .= '<li><strong>Original Host (x_forwarded_host):</strong> Original host header value before proxy modifications.</li>';
	$columns .= '<li><strong>Final Host (host):</strong> Domain or subdomain the client requests (useful for multi-site or debugging).</li>';
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
	$columns .= '<li><strong>MIME (accept):</strong> MIME types the client can handle in the response (e.g., text/html, application/json).</li>';
	$columns .= '<li><strong>Compression (accept_encoding):</strong> Compression algorithms the client supports (e.g., gzip, deflate, br).</li>';
	$columns .= '<li><strong>Language (accept_language):</strong> Preferred languages.</li>';
	$columns .= '<li><strong>Media Type (content_type):</strong> Media type of the request body (e.g., application/json, text/plain).</li>';
	$columns .= '<li><strong>Connection (connection_type):</strong> Connection header indicating client preferences (e.g., keep-alive, close).</li>';
	$columns .= '<li><strong>Caching (cache_control):</strong> Caching preferences.</li>';
	$columns .= '</ul>';
	$columns .= '<p><strong>Response Data</strong></p>';
	$columns .= '<ul>';
	$columns .= '<li><strong>Status Code (status_code):</strong> HTTP response status code sent by the server (e.g., 200, 404, 500).</li>';
	$columns .= '</ul>';

	$troubleshooting  = '<h3>Troubleshooting</h3>';
	$troubleshooting .= '<p>If you are experiencing issues with logging in Traffic Monitor, here are some common scenarios and solutions:</p>';
	$troubleshooting .= '<p><strong>Caching Detected:</strong> </p>';
	$troubleshooting .= '<ul>';
	$troubleshooting .= '<li>The caching detected notice appears when WordPress detects page caching, meaning some requests are being served from cache instead of dynamically generating pages from PHP.</li>';
	$troubleshooting .= '<li>This detection isn’t limited to caching plugins—other sources include CDNs (Cloudflare, AWS CloudFront, etc.), server-level caching (NGINX FastCGI Cache, Varnish, Apache mod_cache), and hosting provider caching that’s automatically applied.</li>';
	$troubleshooting .= '<li>Even if you turn off all caching, WordPress caches its test results for 24 hours, so the warning may persist until WordPress retests for cache.</li>';
	$troubleshooting .= '<li>If you dismiss the warning, it won’t appear again for 24 hours. Dismiss it three times and it won’t appear again.</li>';
	$troubleshooting .= '</ul>';
	$troubleshooting .= '<p><strong>Page Request Not Logged:</strong> </p>';
	$troubleshooting .= '<ul>';
	$troubleshooting .= '<li>Traffic Monitor cannot log requests for cached pages because they are served directly by your server or CDN, bypassing WordPress entirely.</li>';
	$troubleshooting .= '<li>If logging requests is a higher priority than caching, you may need to turn off full-page caching in your caching plugin or CDN settings. Refer to your caching provider’s documentation for specific instructions.</li>';
	$troubleshooting .= '<li>You do NOT need to turn off caching for images, fonts, JavaScript, or CSS—Traffic Monitor only logs page requests, not static assets.</li>';
	$troubleshooting .= '<li>Some CDNs, name servers, or web hosts may block bots or suspicious requests. If you want Traffic Monitor to log those requests, turn off bot blocking and other security features.</li>';
	$troubleshooting .= '</ul>';
	$troubleshooting .= '<p><strong>Missing or Incorrect IP Addresses:</strong> Services like Cloudflare or Sucuri act as reverse proxies and may alter headers or mask client IPs. To address this:</p>';
	$troubleshooting .= '<ul>';
	$troubleshooting .= '<li>If you use Cloudflare, turn off the proxy mode (gray cloud) in your DNS settings to bypass its proxy layer.</li>';
	$troubleshooting .= '<li>Check the Client IP or IP Chain columns for original IP addresses. Traffic Monitor logs these when present.</li>';
	$troubleshooting .= '</ul>';
	$troubleshooting .= '<p>If issues persist, support is available by email at dmitri.amartin@viablepress.com.</p>';

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
