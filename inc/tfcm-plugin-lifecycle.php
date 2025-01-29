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
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$users = get_users( array( 'fields' => 'ID' ) );
	foreach ( $users as $user_id ) {
		delete_user_meta( $user_id, 'managetoplevel_page_traffic-monitorcolumnshidden' );
	}

	// dbDelta() will create the table if it doesn't exist and update it if fields are added.
	$sql = 'CREATE TABLE ' . TFCM_TABLE_NAME . " (
		id INT UNSIGNED AUTO_INCREMENT,
		request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
		request_url VARCHAR(255),
		method VARCHAR(10),
		referer_url VARCHAR(255),
		user_role VARCHAR(50),
		ip_address VARCHAR(45),
		x_real_ip VARCHAR(45),
		x_forwarded_for TEXT,
		forwarded VARCHAR(255),
		x_forwarded_host VARCHAR(255),
		host VARCHAR(255),
		device VARCHAR(50),
		operating_system VARCHAR(255) NOT NULL,
		browser VARCHAR(128),
		browser_version VARCHAR(50),
		user_agent TEXT,
		origin VARCHAR(255),
		accept VARCHAR(255),
		accept_encoding VARCHAR(255),
		accept_language VARCHAR(255),
		content_type VARCHAR(255),
		connection_type VARCHAR(50),
		cache_control VARCHAR(255),
		status_code SMALLINT,
		PRIMARY KEY (id)
	) $charset_collate;";
	dbDelta( $sql );
}

/**
 * Handles plugin deactivation by performing cleanup tasks.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function tfcm_deactivate_plugin() {
	$users = get_users( array( 'fields' => 'ID' ) );
	foreach ( $users as $user_id ) {
		delete_user_meta( $user_id, 'tfcm_already_signed_up' );
		delete_user_meta( $user_id, 'tfcm_cache_notice_last_dismissed' );
		delete_user_meta( $user_id, 'tfcm_cache_notice_dismissals' );
		delete_user_meta( $user_id, 'tfcm_elements_per_page' );
		delete_user_meta( $user_id, 'managetoplevel_page_traffic-monitorcolumnshidden' );
	}
}

/**
 * Handle plugin uninstallation
 */
function tfcm_uninstall_plugin() {
	tfcm_delete_old_exports();

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate. Schema change required for database cleanup on uninstallation.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', TFCM_TABLE_NAME ) );

	if ( $wpdb->last_error ) {
		$error = $wpdb->last_error;
		error_log( 'deactivation error: ' . $error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' function in ' . basename( __FILE__ ) . ' file of Traffic Monitor plugin' );
	}
}

/**
 * Add action links (e.g., "Go Pro" and "Settings").
 *
 * @param array $links An array of existing action links.
 * @return array Modified array of action links.
 */
function tfcm_plugin_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=traffic-monitor' ) ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
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
	if ( plugin_basename( TFCM_PLUGIN_FILE ) === $file ) {
		$links[] = '<a href="https://viablepress.com/trafficmonitorpro" target="_blank">Pro Version</a>';
		$links[] = '<a href="https://wordpress.org/support/plugin/traffic-monitor/reviews/#new-post" target="_blank">Rate this plugin</a>';
	}
	return $links;
}
