<?php
/**
 * TFCM_Log_Table class file.
 *
 * Contains the definition for the TFCM_Log_Table class, which extends WP_List_Table
 * to display Traffic Monitor logs in the WordPress admin.
 *
 * @package TrafficMonitor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Ensure the WP_List_Table class is loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * TFCM_Log_Table class.
 *
 * Extends WP_List_Table to display log data in an admin table.
 *
 * @package TrafficMonitor
 */
class TFCM_Log_Table extends WP_List_Table {
	/**
	 * Stores table data for rendering.
	 *
	 * @var array
	 */
	private $table_data;

	/**
	 * WP_List_Table method that defines the columns for the table.
	 *
	 * @return array Associative array of column IDs and their display names.
	 */
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'request_time' => 'Date',
			'request_url'  => 'Resource',
			'method'       => 'Method',
			'referer'      => 'Prior Page',
			'ip_addr'      => 'IP Addr',
			'os'           => 'System',
			'device'       => 'Device',
			'browser'      => 'Browser',
		);
		return $columns;
	}

	/**
	 * WP_List_Table method that defines the bulk actions available in the table.
	 *
	 * @return array Associative array of bulk actions and their labels.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete'     => 'Delete Selected',
			'delete_all' => 'Delete All',
			'export'     => 'Export Selected',
			'export_all' => 'Export All',
		);
		return $actions;
	}

	/**
	 * WP_List_Table method that renders the checkbox column for each row in the table.
	 *
	 * @param array $item The current row's data.
	 * @return string HTML markup for the checkbox.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="element[]" value="%s" aria-label="Select row for %s" />',
			esc_attr( $item['id'] ),
			esc_attr( $item['id'] )
		);
	}

	/**
	 * WP_List_Table method that renders default column values for columns without custom handlers.
	 *
	 * @param array  $item       The current row's data.
	 * @param string $column_name The name of the current column.
	 * @return string The column's value.
	 */
	public function column_default( $item, $column_name ) {
		if ( 'request_time' === $column_name ) {
			$view_url = add_query_arg(
				array(
					'page'     => 'traffic-monitor',
					'action'   => 'view_details',
					'id'       => $item['id'],
					'_wpnonce' => wp_create_nonce( 'tfcm_view_details' ),
				),
				admin_url( 'admin.php' )
			);

			return sprintf(
				'%s <br><a href="%s">View Details</a>',
				esc_html( $item[ $column_name ] ),
				esc_url( $view_url )
			);
		}
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Retrieves paginated log data from the database based on search, sorting, and pagination parameters.
	 *
	 * This method fetches only the required rows for the current page, as specified by the
	 * pagination and "items per page" settings. It also supports search and sorting.
	 *
	 * @param string $search      Optional. Search term to filter log data. Default is an empty string.
	 * @param int    $per_page    Number of records to display per page. Default is 10.
	 * @param int    $current_page The current page number for pagination. Default is 1.
	 *
	 * @return array An array of associative arrays, each containing a row of log data. Returns an empty array if no records are found.
	 */
	private function tfcm_get_table_data( $search = '', $per_page = 10, $current_page = 1 ) {
		global $wpdb;

		$orderby              = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'request_time'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP doesn't provide a nonce for this list table element.
		$allowed_sort_columns = array( 'request_time', 'request_url', 'referer', 'ip_addr' );
		if ( in_array( $orderby, $allowed_sort_columns, true ) ) {
			$orderby = esc_sql( $orderby );
		}

		$order               = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WP doesn't provide a nonce for this list table element.
		$allowed_sort_orders = array( 'asc', 'desc' );
		if ( in_array( strtoupper( $order ), $allowed_sort_orders, true ) ) {
			$order = esc_sql( strtoupper( $order ) );
		}

		$search_term    = '%' . wp_unslash( $search ) . '%';
		$query          = 'SELECT * FROM ' . TFCM_TABLE_NAME . " WHERE request_time LIKE %s OR request_url LIKE %s OR referer LIKE %s OR user_agent LIKE %s ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$offset         = ( $current_page - 1 ) * $per_page;
		$prepared_query = $wpdb->prepare( $query, $search_term, $search_term, $search_term, $search_term, $per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are properly prepared.

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct database calls required for custom table access, no caching as up-to-date records are essential, and placeholders are properly prepared.
		$result = $wpdb->get_results( $prepared_query, ARRAY_A );

		if ( false === $result ) {
			// error_log( 'Database error in tfcm_get_table_data: ' . $wpdb->last_error );
			return array();
		}

		return $result ? $result : array();
	}

	/**
	 * WP_List_Table method that prepares table data, columns, and pagination for rendering.
	 *
	 * Retrieves and processes data, sets column headers, and configures pagination.
	 */
	public function prepare_items() {
		// Retrieve the data, potentially filtered by search.
		$search = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WP doesn't provide a nonce for its list table form.

		// Dynamically retrieve pagination and per-page settings.
		$per_page     = $this->get_items_per_page( 'tfcm_elements_per_page', 10 );
		$current_page = $this->get_pagenum();

		$this->table_data = $this->tfcm_get_table_data( $search, $per_page, $current_page );

		// Setup column headers and sortable columns.
		$columns               = $this->get_columns();
		$user_meta             = get_user_meta( get_current_user_id(), 'manage_toplevel_page_traffic-monitor_columns_hidden', true );
		$hidden                = is_array( $user_meta ) ? $user_meta : array();
		$sortable              = array(
			'request_time' => array( 'request_time', false ),
			'request_url'  => array( 'request_url', false ),
			'referer'      => array( 'referer', true ),
			'ip_addr'      => array( 'ip_addr', true ),
		);
		$primary               = 'request_time'; // used to display row actions (e.g., Edit, Delete).
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		// Get total number of items for pagination.
		global $wpdb;
		$search_term = '%' . wp_unslash( $search ) . '%';
		$query       = 'SELECT COUNT(*) FROM ' . TFCM_TABLE_NAME . ' WHERE request_time LIKE %s OR request_url LIKE %s OR referer LIKE %s OR user_agent LIKE %s';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query and no caching are justified because this query retrieves real-time counts from a custom table. Placeholders are properly prepared.
		$total_items = $wpdb->get_var( $wpdb->prepare( $query, $search_term, $search_term, $search_term, $search_term ) );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->items = $this->table_data;
	}
}
