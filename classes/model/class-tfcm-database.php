<?php
/**
 * File: /classes/model/class-tfcm-database.php
 *
 * Manages database schema creation, updates, and CRUD operations for the Traffic Monitor log.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class TFCM_Database
 *
 * Handles database operations such as table creation, insertion, deletion, and retrieval of log entries.
 */
class TFCM_Database {

	// =========================================================================
	// CREATE & SCHEMA MANAGEMENT METHODS
	// =========================================================================

	/**
	 * Creates or updates the request log table in the database.
	 *
	 * Uses dbDelta() to handle table creation and schema updates.
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
			is_cached BOOLEAN,
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

		$wpdb->show_errors();
	}

	/**
	 * Removes deprecated columns from the request log table.
	 *
	 * Checks if columns such as 'forwarded', 'x_real_ip', 'x_forwarded_for', and 'x_forwarded_host' exist and drops them.
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

			$deprecated_columns = array( 'forwarded', 'x_real_ip', 'x_forwarded_for', 'x_forwarded_host', 'request_type' );
			foreach ( $deprecated_columns as $column ) {
				if ( in_array( $column, $column_names, true ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for a custom table, and caching is not appropriate.
					$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP COLUMN %i', TFCM_REQUEST_LOG_TABLE, $column ) );
				}
			}
		}
	}

	// =========================================================================
	// READ METHODS
	// =========================================================================

	/**
	 * Retrieves a single request by its ID.
	 *
	 * @param int $log_id The ID of the request.
	 * @return array|null The request as an associative array, or null if not found.
	 */
	public static function get_single_request( $log_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$log = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', TFCM_REQUEST_LOG_TABLE, $log_id ), ARRAY_A );

		return $log;
	}

	/**
	 * Retrieves requests matching the specified IDs.
	 *
	 * @param array $log_ids Array of request IDs to retrieve.
	 * @return array Array of requests.
	 */
	public static function get_selected_requests( $log_ids ) {
		global $wpdb;

		if ( empty( $log_ids ) ) {
			return array();
		}

		$placeholders   = implode( ', ', array_fill( 0, count( $log_ids ), '%d' ) );
		$prepare_values = array_merge( array( TFCM_REQUEST_LOG_TABLE ), $log_ids );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i WHERE id IN ( $placeholders )", $prepare_values ), ARRAY_A );
	}

	/**
	 * Retrieves all requests from the request table.
	 *
	 * @return array Array of all requests.
	 */
	public static function get_all_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i', TFCM_REQUEST_LOG_TABLE ), ARRAY_A );
	}

	/**
	 * Counts all requests in the request table.
	 *
	 * @return int The total count of requests.
	 */
	public static function count_all_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', TFCM_REQUEST_LOG_TABLE ) );
	}

	/**
	 * Retrieves a set of request table rows based on search criteria.
	 *
	 * This method builds a SQL query that filters requests based on a search term,
	 * orders them using a sanitized ORDER BY clause, and limits the result set for pagination.
	 * A direct database call is used without caching.
	 *
	 * @param string $search_term The search term with wildcard characters (e.g. '%term%').
	 * @param string $orderby_sql The sanitized ORDER BY clause (e.g., "request_time DESC").
	 * @param int    $per_page    The number of items to retrieve.
	 * @param int    $offset      The offset for pagination.
	 *
	 * @return array Array of requests as associative arrays.
	 */
	public static function get_request_table_rows( $search_term, $orderby_sql, $per_page, $offset ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for a custom table, and caching is not appropriate. ORDER BY clause cannot use placeholders in wpdb->prepare(), so it is safely validated with sanitize_sql_orderby().
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i 
				WHERE request_time LIKE %s	
				OR request_url LIKE %s 
				OR method LIKE %s
				OR referer_url LIKE %s 
				OR user_role LIKE %s
				OR ip_address LIKE %s
				OR user_agent LIKE %s 
				OR origin LIKE %s
				OR status_code LIKE %s 
				ORDER BY ' . $orderby_sql . ' LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				TFCM_REQUEST_LOG_TABLE,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$per_page,
				$offset
			),
			ARRAY_A
		);
	}

	/**
	 * Counts the number of requests matching a search term.
	 *
	 * This method builds a SQL query to count requests based on a search term.
	 * A direct database call is used without caching.
	 *
	 * @param string $search_term The search term with wildcard characters (e.g. '%term%').
	 *
	 * @return int The total count of matching requests.
	 */
	public static function count_request_table_rows( $search_term ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for a custom table, and caching is not appropriate.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i
				WHERE request_time LIKE %s
				OR request_url LIKE %s 
				OR method LIKE %s
				OR referer_url LIKE %s 
				OR user_role LIKE %s
				OR ip_address LIKE %s
				OR user_agent LIKE %s 
				OR origin LIKE %s
				OR status_code LIKE %s',
				TFCM_REQUEST_LOG_TABLE,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);
	}

	// =========================================================================
	// INSERT METHODS
	// =========================================================================

	/**
	 * Inserts a new request into the request table.
	 *
	 * @param object $request The request object containing request data.
	 * @return void
	 */
	public static function insert_request( $request ) {
		global $wpdb;

		$data = array(
			'request_time'     => $request->request_time,
			'request_url'      => $request->request_url,
			'is_cached'        => $request->is_cached,
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Database Insert Error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		}
	}

	// =========================================================================
	// DELETE METHODS
	// =========================================================================

	/**
	 * Deletes plugin tables from the database.
	 *
	 * Use with caution as this operation is irreversible.
	 *
	 * @return void
	 */
	public static function delete_tables() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Direct query is required for table deletion.
		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', TFCM_REQUEST_LOG_TABLE ) );

		if ( $wpdb->last_error ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'Database deletion error: ' . $wpdb->last_error . ' on line ' . __LINE__ . ' of ' . __FUNCTION__ . ' in ' . basename( __FILE__ ) );
		}
	}

	/**
	 * Deletes selected requests from the request table identified by an array of IDs.
	 *
	 * @param array $log_ids Array of request IDs to delete.
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public static function delete_selected_requests( $log_ids ) {
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
	 * Deletes all requests from the request table.
	 *
	 * @return int|false The number of rows deleted, or false on failure.
	 */
	public static function delete_all_requests() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', TFCM_REQUEST_LOG_TABLE ) );
	}
}
