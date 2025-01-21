=== Traffic Monitor ===
Contributors: dmitriamartin  
Tags: traffic, monitor, visitors, requests, logger
Tested up to: 6.7  
Stable tag: 1.0.1  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Monitor and log HTTP traffic directly from your WordPress admin panel.

== Description ==
Traffic Monitor is a lightweight WordPress plugin that logs HTTP requests, including page URLs, referrer data, IP addresses, browser details, and more. Easily view traffic logs from the WordPress admin panel.

Key features:
- Logs HTTP requests in a structured table.
- Tracks IP addresses, referrer URLs, and browser information.
- Supports bulk deletion and CSV export.
- Admin panel UI for easy management.
- Optimized to exclude static assets and admin requests.

== Installation ==
1. Download the plugin ZIP file.
2. Upload it to your WordPress installation via **Plugins → Add New → Upload Plugin**.
3. Activate the plugin.

== Frequently Asked Questions ==

= Does this plugin track visitors across pages? =
No, Traffic Monitor only logs individual HTTP requests, not full user sessions.

= Where is the request data stored? =
All logs are stored in a custom database table (`wp_tfcm_request_log`) within your WordPress database.

= Can I export request logs? =
Yes, logs can be exported as a CSV file from the admin panel.

= Will this plugin slow down my site? =
No, Traffic Monitor is optimized to exclude unnecessary requests (e.g., static assets) and logs only essential data.

== Screenshots ==
1. **Admin panel log view** - See visitor request logs.
2. **Bulk actions and export options** - Delete or export logs as CSV.

== Changelog ==

= 1.0.1 =
This update includes security enhancements for CSV exports.

= 1.0.0 - January 1, 2025 =
- Initial release.

== Acknowledgments ==
This plugin uses the **PHP User Agent Parser** library by Jesse G. Donat (donatj.com), licensed under the MIT License.

**MIT License:**
Copyright (c) 2013 Jesse G. Donat  
Licensed under the MIT License: https://opensource.org/licenses/MIT  

== Support ==
For support, please visit [GitHub Issues](https://github.com/dmitriamartin817/traffic-monitor/issues).

