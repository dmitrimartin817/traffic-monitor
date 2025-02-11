<?php
/**
 * TFCM_Database class file.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles database schema management for Traffic Monitor.
 *
 * @package TrafficMonitor
 */
class TFCM_Database {
	/**
	 * Checks and removes deprecated columns from existing tables.
	 *
	 * @return void
	 */
	public static function remove_deprecated_columns() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for a custom table, and caching is not appropriate.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', TFCM_REQUEST_LOG_TABLE ) );
		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for a custom table, and caching is not appropriate.
			$existing_columns = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i', TFCM_REQUEST_LOG_TABLE ), ARRAY_A );
			$column_names     = wp_list_pluck( $existing_columns, 'Field' );

			$deprecated_columns = array( 'forwarded', 'x_real_ip', 'x_forwarded_for', 'x_forwarded_host' );
			foreach ( $deprecated_columns as $column ) {
				if ( in_array( $column, $column_names, true ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate.
					$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN %i', TFCM_REQUEST_LOG_TABLE, $column ) );
				}
			}
		}
	}

	/**
	 * Creates or updates the request log database table.
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = 'CREATE TABLE ' . TFCM_REQUEST_LOG_TABLE . " (
			id INT UNSIGNED AUTO_INCREMENT,
			request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
			request_url VARCHAR(255),
			request_type VARCHAR(7),
			method VARCHAR(10),
			referer_url VARCHAR(255),
			user_role VARCHAR(50),
			ip_address VARCHAR(45),
			host VARCHAR(255),
			device VARCHAR(50),
			operating_system VARCHAR(255),
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
	 * Inserts a request record into the database.
	 *
	 * @param mixed $request The request object containing request metadata.
	 * @return void
	 */
	public static function insert_request( $request ) {
		global $wpdb;

		$data = array(
			'request_time'     => $request->request_time,
			'request_url'      => $request->request_url,
			'request_type'     => $request->request_type,
			'method'           => $request->method,
			'referer_url'      => $request->referer_url,
			'user_role'        => $request->user_role,
			'ip_address'       => $request->ip_address,
			'host'             => $request->host,
			'device'           => $request->device,
			'operating_system' => $request->operating_system,
			'browser'          => $request->browser,
			'browser_version'  => $request->browser_version,
			'user_agent'       => $request->user_agent,
			'origin'           => $request->origin,
			'accept'           => $request->accept,
			'accept_encoding'  => $request->accept_encoding,
			'accept_language'  => $request->accept_language,
			'content_type'     => $request->content_type,
			'connection_type'  => $request->connection_type,
			'cache_control'    => $request->cache_control,
			'status_code'      => $request->status_code,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Direct query is required
		$wpdb->insert( TFCM_REQUEST_LOG_TABLE, $data );

		if ( false === $wpdb->insert_id ) {
			error_log( 'Database Insert Error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		}
	}

	/**
	 * Deletes the request log table.
	 *
	 * @return void
	 */
	public static function delete_tables() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for table deletion.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', TFCM_REQUEST_LOG_TABLE ) );

		if ( $wpdb->last_error ) {
			error_log( 'Database deletion error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		}
	}

		/**
		 * Deletes specific request records from the database.
		 *
		 * @param array $log_ids The list of log IDs to delete.
		 * @return int|false Number of rows deleted or false on failure.
		 */
	public static function delete_requests( $log_ids ) {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return false;
		}

		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_REQUEST_LOG_TABLE ), $log_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->query( $wpdb->prepare( "DELETE FROM %i WHERE id IN ( $placeholders )", $prepare_values ) );
	}

	/**
	 * Retrieves specific request records.
	 *
	 * @param array $log_ids The list of log IDs to retrieve.
	 * @return array Retrieved log data.
	 */
	public static function get_requests( $log_ids ) {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return array();
		}

		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_REQUEST_LOG_TABLE ), $log_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Direct query is required for immediate deletion, caching is not appropriate, and WordPress review team-approved example structure.
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE id IN ( $placeholders )", $prepare_values ), ARRAY_A );
	}

	/**
	 * Deletes all request logs from the database.
	 *
	 * @return int|false Number of rows deleted or false on failure.
	 */
	public static function delete_all_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_REQUEST_LOG_TABLE ) );
	}

	/**
	 * Retrieves the total count of request logs.
	 *
	 * @return int The number of request logs.
	 */
	public static function count_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', TFCM_REQUEST_LOG_TABLE ) );
	}

	/**
	 * Retrieves all request logs from the database.
	 *
	 * @return array Retrieved log data.
	 */
	public static function get_all_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', TFCM_REQUEST_LOG_TABLE ), ARRAY_A );
	}
}
