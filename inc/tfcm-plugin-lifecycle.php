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
	$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', TFCM_TABLE_NAME );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query is on executed on activation. Table name is dynamic but safe.
	if ( $wpdb->get_var( $query ) !== TFCM_TABLE_NAME ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange -- Using dbDelta() to manage schema changes
		$sql = 'CREATE TABLE IF NOT EXISTS ' . TFCM_TABLE_NAME . " (
			id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
			request_url VARCHAR(255),
			method VARCHAR(10),
			referer VARCHAR(255),
			ip_addr VARCHAR(45),
			browser VARCHAR(128),
			browser_version VARCHAR(50),
			os VARCHAR(255) NOT NULL,
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
			status_code SMALLINT
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
	// error_log( 'Traffic Monitor plugin deactivated on ' . current_time( 'mysql' ) );

	// Delete the traffic-monitor-log.csv file if it exists.
	$csv_file = plugin_dir_path( __FILE__ ) . 'data/traffic-monitor-log.csv';
	if ( file_exists( $csv_file ) ) {
		if ( ! wp_delete_file( $csv_file ) ) {
			// error_log( 'Traffic Monitor: Failed to delete traffic-monitor-log.csv during deactivation.' );
		}
	}
}

/**
 * Handle plugin uninstallation
 */
function tfcm_uninstall_plugin() {
	global $wpdb;

	// Safely drop the table.
	$query = 'DROP TABLE IF EXISTS ' . TFCM_TABLE_NAME;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- This query is necessary for managing custom tables during plugin lifecycle events. Table names cannot be parameterized with prepare()
	$wpdb->query( $query );

	// Log any database errors.
	if ( $wpdb->last_error ) {
		// error_log( 'Traffic Monitor deactivation error: ' . $wpdb->last_error );
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
		$links[] = '<a href="https://viablepress.com" target="_blank">Homepage</a>';
		// $links[] = '<a href="https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post" target="_blank">Rate this plugin</a>';
	}
	return $links;
}
