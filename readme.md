# Traffic Monitor

[![License: GPL v2](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Version](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-blue.svg)](https://www.php.net/)

**Traffic Monitor** is a lightweight WordPress plugin that logs HTTP requests directly from your admin panel. Track visitors, analyze traffic, and export logs with ease.

---

## 🚀 Features
✅ Logs HTTP requests, including:
- **Page URLs**, **referrer data**, **IP addresses**, **browser details**, and more.  
✅ Supports **bulk deletion** and **CSV export**.  
✅ Provides an **admin panel UI** for managing traffic logs.  
✅ **Optimized performance** – excludes static assets and admin requests.  
✅ Works with WordPress **6.2+** and PHP **7.4+**.  

---

## 📌 Installation
1. Download the plugin ZIP file.
2. Go to **Plugins → Add New → Upload Plugin** in your WordPress admin panel.
3. Activate the plugin.

---

## 📖 Usage
- Navigate to **Traffic Monitor** in the **WordPress admin menu**.
- View **logged HTTP requests**, including IP addresses and user agents.
- Use **bulk actions** to **delete** or **export logs** as CSV files.
- Customize displayed columns using **Screen Options**.

---

## 🔍 Frequently Asked Questions (FAQ)

### **Does this plugin track visitors across multiple pages?**  
No, it logs **individual HTTP requests** but does not track full user sessions.

### **Where is request data stored?**  
Logs are stored in a **custom database table** (`wp_tfcm_request_log`).

### **Will this slow down my site?**  
No. The plugin **ignores unnecessary requests** (static files, admin pages, AJAX calls) to **minimize database load**.

### **Can I export request logs?**  
Yes, logs can be **exported as CSV files** directly from the admin panel.

---

## 📦 Dependencies
This plugin uses:

- **[PHP User Agent Parser](https://github.com/donatj/PhpUserAgent) (MIT License)**  
  - Used to parse and classify User-Agent strings.

---

## 🛠️ Contributing
Contributions are welcome! If you find a bug or have a feature request:

1. **Fork the repository**.
2. **Create a new branch** (`feature-name`).
3. **Submit a pull request**.

---

## 📜 License
This plugin is licensed under **GPL v2 or later**.  
See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

---

## 📧 Support
For issues and feature requests, open a **GitHub [Issue](https://github.com/dmitrimartin817/traffic-monitor/issues)**.
