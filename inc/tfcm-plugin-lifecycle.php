<?php
/**
 * Plugin lifecycle management for Traffic Monitor.
 *
 * Handles plugin activation, deactivation, and uninstallation processes,
 * including database table creation and cleanup.
 *
 * @package TrafficMonitor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation by creating the required database tables.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function tfcm_activate_plugin() {
	// Create database tables.
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Table for Blocked Requests.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %i', TFCM_TABLE_NAME ) ) !== TFCM_TABLE_NAME ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Using dbDelta() to manage schema changes
		$sql = 'CREATE TABLE IF NOT EXISTS ' . TFCM_TABLE_NAME . " (
			id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
			request_url VARCHAR(255),
			method VARCHAR(10),
			referer_url VARCHAR(255),
			ip_address VARCHAR(45),
			browser VARCHAR(128),
			browser_version VARCHAR(50),
			operating_system VARCHAR(255) NOT NULL,
			device VARCHAR(50),
			origin VARCHAR(255),
			x_real_ip VARCHAR(45),
			x_forwarded_for TEXT,
			forwarded VARCHAR(255),
			x_forwarded_host VARCHAR(255),
			host VARCHAR(255),
			accept VARCHAR(255),
			accept_encoding VARCHAR(255),
			accept_language VARCHAR(255),
			content_type VARCHAR(255),
			connection_type VARCHAR(50),
			cache_control VARCHAR(255),
			user_agent TEXT,
			status_code SMALLINT,
			INDEX (request_time),
			INDEX (request_url(100)),
			INDEX (referer_url(100)),
			INDEX (user_agent(100))
		) $charset_collate;";
		dbDelta( $sql );

		// Set default "Elements per page" value.
		$default_per_page = 10;
		update_user_meta( get_current_user_id(), 'manage_toplevel_page_traffic-monitor_per_page', $default_per_page );
	}
}

/**
 * Handles plugin deactivation by performing cleanup tasks.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function tfcm_deactivate_plugin() {
	$time = current_time( 'mysql' );
	// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found
	// error_log( 'Traffic Monitor plugin deactivated on ' . $time );
}

/**
 * Handle plugin uninstallation
 */
function tfcm_uninstall_plugin() {
	// Delete old CSV export files from the plugin's data directory.
	tfcm_delete_old_exports();

	global $wpdb;

	// Safely drop the table.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate. Schema change required for database cleanup on uninstallation.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', TFCM_TABLE_NAME ) );

	// Log any database errors.
	if ( $wpdb->last_error ) {
		$error = $wpdb->last_error;
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found
		// error_log( 'Traffic Monitor deactivation error: ' . $error );
	}

	// Delete plugin-specific options.
	delete_option( 'tfcm_elements_per_page' );
}

/**
 * Add action links (e.g., "Go Pro" and "Settings").
 *
 * @param array $links An array of existing action links.
 * @return array Modified array of action links.
 */
function tfcm_plugin_action_links( $links ) {
	$settings_link = '<a href="options-general.php?page=traffic-monitor">Settings</a>';
	// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found
	// $go_pro_link = '<a href="https://trafficmonitorpro.com/pro-version" target="_blank">Pro Version</a>';
	// array_unshift($links, $settings_link, $go_pro_link);
	return $links;
}

/**
 * Add meta links (e.g., "Homepage" and "Rate this plugin").
 *
 * @param array  $links An array of existing meta links.
 * @param string $file  The current plugin file.
 * @return array Modified array of meta links.
 */
function tfcm_plugin_row_meta_links( $links, $file ) {
	if ( plugin_basename( __FILE__ ) === $file ) {
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar, Squiz.PHP.CommentedOutCode.Found
		// $links[] = '<a href="https://viablepress.com" target="_blank">Homepage</a>';
		$links[] = '<a href="https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post" target="_blank">Rate this plugin</a>';
	}
	return $links;
}
