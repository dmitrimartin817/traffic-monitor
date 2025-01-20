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
	 * WP_List_Table method that defines the columns for the table.
	 *
	 * @return array Associative array of column IDs and their display names.
	 */
	public function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'request_time'     => 'Date',
			'request_url'      => 'Resource',
			'method'           => 'Method',
			'referer_url'      => 'Prior Page',
			'ip_address'       => 'IP Address',
			'operating_system' => 'System',
			'device'           => 'Device',
			'browser'          => 'Browser',
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
					'page'               => 'traffic-monitor',
					'action'             => 'view_details',
					'id'                 => $item['id'],
					'tfcm_details_nonce' => wp_create_nonce( 'tfcm_details_nonce' ),
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
	 * Prepares the Traffic Monitor log table data for display in the WordPress admin.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		// Verify nonce before processing form data.
		if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'traffic-monitor' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid request. Please try again.' ) );
		}

		// Retrieve the data, potentially filtered by search.
		$search      = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		// Dynamically retrieve pagination and per-page settings.
		$per_page     = absint( $this->get_items_per_page( 'tfcm_elements_per_page', 10 ) );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Setup column headers and sortable columns.
		$columns               = $this->get_columns();
		$user_meta             = get_user_meta( get_current_user_id(), 'manage_toplevel_page_traffic-monitor_columns_hidden', true );
		$hidden                = is_array( $user_meta ) ? $user_meta : array();
		$sortable              = array(
			'request_time' => array( 'request_time', true ),
			'request_url'  => array( 'request_url', false ),
			'referer_url'  => array( 'referer_url', false ),
			'ip_address'   => array( 'ip_address', false ),
		);
		$primary               = 'request_time'; // used to display row actions (e.g., View Details).
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		// Handle sorting safely.
		$orderby              = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'request_time';
		$allowed_sort_columns = array( 'request_time', 'request_url', 'referer_url', 'ip_address' );
		$orderby              = in_array( $orderby, $allowed_sort_columns, true ) ? esc_sql( $orderby ) : 'request_time';

		$order               = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';
		$allowed_sort_orders = array( 'ASC', 'DESC' );
		$order               = in_array( strtoupper( $order ), $allowed_sort_orders, true ) ? strtoupper( $order ) : 'DESC';

		$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate. ORDER BY clause cannot use placeholders in wpdb->prepare(), so it is safely validated with sanitize_sql_orderby().
		$this->items = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE request_time LIKE %s	OR request_url LIKE %s OR referer_url LIKE %s OR user_agent LIKE %s ORDER BY ' . $orderby_sql . ' LIMIT %d OFFSET %d', TFCM_TABLE_NAME, $search_term, $search_term, $search_term, $search_term, $per_page, $offset ), ARRAY_A );

		// Get total number of items for pagination.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for retrieving real-time data from a custom table, and caching is not appropriate.
		$total_items = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i
			WHERE request_time LIKE %s
			OR request_url LIKE %s
			OR referer_url LIKE %s
			OR user_agent LIKE %s',
				TFCM_TABLE_NAME,
				$search_term,
				$search_term,
				$search_term,
				$search_term
			)
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
