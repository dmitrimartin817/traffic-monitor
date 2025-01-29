=== Traffic Monitor ===
Contributors: dmitriamartin  
Tags: traffic, monitor, visitors, requests, security
Tested up to: 6.7  
Stable tag: 1.0.4
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Log and view each page visit including date, page requested, IP address, and more. Useful for development, security, and tracking advertising.

== Description ==

Traffic Monitor is a **lightweight** and **straightforward** solution for tracking website visitors. It logs each page request and displays a **detailed table** in the WordPress admin panel with key request data.

== Key Features ==

* **Logs page requests** with details including IP address, referrer URL, browser, and more.
* **Displays structured request data** in an admin table with sortable columns.
* **Expandable request details**—view additional HTTP request fields per entry.
* **Bulk actions** for exporting or deleting selected records or all logs.
* **Comprehensive Help Tab**—includes usage instructions, column definitions, and troubleshooting tips.

== Use Cases ==

* **Debugging:** Identify broken links, incorrect headers, or unexpected request behaviors.
* **Performance Monitoring:** Analyze frequently accessed pages and optimize site speed accordingly.
* **Security Analysis:** Detect unusual traffic patterns, excessive bot activity, or malicious attacks (DDoS, brute force, etc.).
* **User Behavior Insights:** See where your visitors come from and what devices, operating systems, and browsers they use.
* **Click Fraud Detection:** Spot multiple rapid clicks from the same IP address and user-agent combination.

🚀 **Keep track of your site’s traffic with ease!**

== Installation ==

= Automatic installation =

1. Log into your WordPress admin
2. Click __Plugins__
3. Click __Add New__
4. Search for __Traffic Monitor__
5. Click __Install Now__ under "Traffic Monitor"
6. Activate the plugin

= Manual installation =

1. Download the plugin
2. Extract the contents of the zip file
3. Upload the file contents to the `wp-content/plugins/` folder of your WordPress installation
4. Activate the plugin from 'Plugins' page.

== Frequently Asked Questions ==

= Does this plugin track visitors across pages? =
No, Traffic Monitor only logs individual HTTP requests, not full user sessions.

= Where is the request data stored? =
All logs are stored in a custom database table within your WordPress database.

= Can I export request logs? =
Yes, logs can be exported as a CSV file from the admin panel.

= Will this plugin slow down my site? =
No, Traffic Monitor is optimized to exclude unnecessary requests (e.g., static assets) and logs only essential data.

== Screenshots ==

1. **Admin panel log view** - See visitor request logs.
2. **Request Details** - View details for all columns of a request.

== Changelog ==

= 1.0.4 (2025-01-22) =

* Improved readme.txt and added cached page warning.

= 1.0.3 (2025-01-22) =

* Performance optimizations and cache busting.

= 1.0.2 (2025-01-21) =

* Security enhancements for database queries.  Moved Delete All and Export All from bulk action to buttons.

= 1.0.1 (2025-01-20) =

* Security enhancements for database queries and CSV exports.

= 1.0.0 (2025-01-16) =

Initial release.

== Acknowledgments ==

This plugin uses the **PHP User Agent Parser** library by Jesse G. Donat (donatj.com), licensed under the [MIT License] (https://opensource.org/licenses/MIT).

