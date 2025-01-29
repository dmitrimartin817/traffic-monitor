<?php
/**
 * Plugin lifecycle management for Traffic Monitor.
 *
 * @package TrafficMonitor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation by creating the required database tables.
 *
 * @return void
 */
function tfcm_activate_plugin() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$users = get_users( array( 'fields' => 'ID' ) );
	foreach ( $users as $user_id ) {
		delete_user_meta( $user_id, 'managetoplevel_page_traffic-monitorcolumnshidden' );
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate.
	$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', TFCM_TABLE_NAME ) );
	if ( $table_exists ) {

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate.
		$existing_columns = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i', TFCM_TABLE_NAME ), ARRAY_A );
		$column_names     = wp_list_pluck( $existing_columns, 'Field' );

		if ( in_array( 'forwarded', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate. Schema change required for database change on upgrade.
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN forwarded', TFCM_TABLE_NAME ) );
		}
		if ( in_array( 'x_real_ip', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate. Schema change required for database change on upgrade.
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN x_real_ip', TFCM_TABLE_NAME ) );
		}
		if ( in_array( 'x_forwarded_for', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate. Schema change required for database change on upgrade.
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN x_forwarded_for', TFCM_TABLE_NAME ) );
		}
		if ( in_array( 'x_forwarded_host', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate. Schema change required for database change on upgrade.
			$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN x_forwarded_host', TFCM_TABLE_NAME ) );
		}
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
 * Handles plugin uninstallation.
 *
 * @return void
 */
function tfcm_uninstall_plugin() {
	tfcm_delete_old_exports();

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate. Schema change required for database cleanup on uninstallation.
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
