# Traffic Monitor

**Contributors**: [dmitriamartin](https://profiles.wordpress.org/dmitriamartin)  
**Tags**: traffic, monitor, visitors, requests, security  
**Tested up to**: 6.7  
**Stable tag**: 2.2.0
**License**: GPLv2 or later  
**License URI**: [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

Traffic Monitor is a lightweight WordPress plugin that logs every page visit in real-time. Easily track IP addresses, user agents, referrers, and more—directly from your admin panel. Perfect for debugging, performance monitoring, security analysis, user behavior insights, and click fraud detection.

## Key Features

- **Logs page requests** with details including IP address, referrer URL, browser, and more.
- **Displays structured request data** in an admin table with sortable columns.
- **Expandable request details**—view additional HTTP request fields per entry.
- **Bulk actions for selected records** – Use the dropdown to delete or export specific logs.
- **One-click actions for all records** – Quickly delete or export the entire log with dedicated buttons.
- **Comprehensive help tab**—includes usage instructions, column definitions, and troubleshooting tips.

## Use Cases

- **Debugging:** Identify broken links, incorrect headers, or unexpected request behaviors.
- **Performance Monitoring:** Analyze frequently accessed pages and optimize site speed accordingly.
- **Security Analysis:** Detect unusual traffic patterns, excessive bot activity, or malicious attacks (DDoS, brute force, etc.).
- **User Behavior Insights:** See where your visitors come from and what devices, operating systems, and browsers they use.
- **Click Fraud Detection:** Spot multiple rapid clicks from the same IP address and user-agent combination.

🚀 Track your visitors, detect threats, and optimize performance—all in one simple dashboard.

## Installation

### Automatic Installation

1. Log into your WordPress admin.
2. Go to **Plugins** > **Add New**.
3. Search for **Traffic Monitor**.
4. Click **Install Now** under "Traffic Monitor."
5. Activate the plugin.

### Manual Installation

1. Download the plugin.
2. Extract the contents of the zip file.
3. Upload the contents to the `wp-content/plugins/` folder of your WordPress installation.
4. Activate the plugin from the **Plugins** page.

## FAQ

### How long are logs stored?
Traffic Monitor retains logs until manually deleted. For high-traffic sites, regularly clearing logs will prevent excessive database growth.

### Does this plugin track visitors across pages?
No, Traffic Monitor only logs individual HTTP requests, not full user sessions.

### Where is the request data stored?
Logs are stored in a custom database table within your WordPress database.

### Can I export request logs?
Yes, logs can be exported as a CSV file from the admin panel.

### Will this plugin slow down my site?
No, Traffic Monitor is optimized to log only essential data and exclude unnecessary requests.

## Screenshots

1. **Admin panel log view** - View visitor request logs.
2. **Request Details** - Expand to view detailed HTTP request information.

## Changelog

### 2.2.0 (2025-02-19)
- Added field for whether page requested was cached.

### 2.1.1 (2025-02-15)
- Improved comments and code organization.

### 2.1.0 (2025-02-14)
- Improved branding, cache busting, and updating.

### 2.0.0 (2025-02-12)
- Added logging of cached pages.

### 1.4.0 (2025-02-11)
- Added Request Type field.
- Refactored code from proceedural to OOP with MVC design.

### 1.3.2 (2025-02-05)
- Fixed version error.

### 1.3.1 (2025-02-05)
- Fixed version error.

### 1.3.0 (2025-02-05)
- Removed cache detection.

### 1.2.0 (2025-01-29)
- Added sorting for each column and increased number of search fields.
- Removed forwarded, x_real_ip, x_forwarded_for, and x_forwarded_host fields.
- Improved help file and code comments.

### 1.1.3 (2025-01-28)
- Fixed bugs and improved help.

### 1.1.2 (2025-01-28)
- Security enhancements.

### 1.1.1 (2025-01-28)
- Fixed bugs.

### 1.1.0 (2025-01-27)
- Added cache detection and bug fixes.

### 1.0.4 (2025-01-22)
- Improved readme.txt and fixed bugs.

### 1.0.3 (2025-01-22)
- Fixed bugs.

### 1.0.2 (2025-01-21)
- Security enhancements.

### 1.0.1 (2025-01-20)
- Security enhancements.

### 1.0.0 (2025-01-16)
- Initial release.

## Acknowledgments

This plugin uses the [PHP User Agent Parser](https://github.com/donatj/PhpUserAgent) by Jesse G. Donat, licensed under the [MIT License](https://opensource.org/licenses/MIT).
