<?php
/**
 * TFCM_Log_Table class file class-tfcm-log-table.php
 *
 * @package TrafficMonitor
 */

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
			'request_type'     => 'Type',
			'request_url'      => 'Page Requested',
			'method'           => 'Method',
			'referer_url'      => 'Prior Page',
			'user_role'        => 'User Role',
			'ip_address'       => 'IP Address',
			'host'             => 'Host',
			'device'           => 'Device',
			'operating_system' => 'System',
			'browser'          => 'Browser',
			'browser_version'  => 'Browser Version',
			'user_agent'       => 'User Agent',
			'origin'           => 'Origin',
			'accept'           => 'MIME',
			'accept_encoding'  => 'Compression',
			'accept_language'  => 'Language',
			'content_type'     => 'Media Type',
			'connection_type'  => 'Connection',
			'cache_control'    => 'Caching',
			'status_code'      => 'Response',
		);
		return $columns;
	}

	/**
	 * Retrieves the list of hidden columns for the current user on the Traffic Monitor admin page.
	 *
	 * @return array List of hidden column IDs.
	 */
	public function get_hidden_columns() {
		$user           = get_current_user_id();
		$screen         = get_current_screen();
		$hidden_columns = get_user_meta( $user, 'manage' . $screen->id . 'columnshidden', true );

		return is_array( $hidden_columns ) ? $hidden_columns : array();
	}

	/**
	 * WP_List_Table method that defines the bulk actions available in the table.
	 *
	 * @return array Associative array of bulk actions and their labels.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
			'export' => 'Export',
		);
		return $actions;
	}

	/**
	 * WP_List_Table method that adds custom buttons (Delete All, Export All) next to bulk actions.
	 *
	 * @param string $which Positioning context ('top' or 'bottom').
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		?>
	<div class="alignleft actions">
		<button type="button" id="tfcm-delete-all" class="button button-secondary">Delete All</button>
		<button type="button" id="tfcm-export-all" class="button button-secondary">Export All</button>
	</div>
		<?php
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
	 * Displays a message when there are no log entries.
	 *
	 * @return void
	 */
	public function no_items() {
		echo 'No requests found.';
	}

	/**
	 * Prepares the Traffic Monitor log table data for display in the WordPress admin.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		if ( isset( $_POST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bulk-toplevel_page_traffic-monitor' ) ) {
			wp_die( 'Invalid request. Please try again.', 'Error', array( 'response' => 403 ) );
		}

		$columns  = $this->get_columns();
		$sortable = array();
		foreach ( array_keys( $columns ) as $column ) {
			if ( 'request_time' === $column ) {
				$sortable[ $column ] = array( $column, true );
			} elseif ( 'id' !== $column ) {
				$sortable[ $column ] = array( $column, false );
			}
		}

		$hidden                = get_hidden_columns( get_current_screen() );
		$primary               = 'request_time';
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		$orderby              = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $primary;
		$allowed_sort_columns = array_keys( $sortable );
		$orderby              = in_array( $orderby, $allowed_sort_columns, true ) ? esc_sql( $orderby ) : $primary;

		$allowed_sort_orders = array( 'ASC', 'DESC' );
		$order               = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';
		$order               = in_array( strtoupper( $order ), $allowed_sort_orders, true ) ? strtoupper( $order ) : 'DESC';

		$orderby_sql = sanitize_sql_orderby( "{$orderby} {$order}" );

		$search      = isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		$per_page     = absint( $this->get_items_per_page( 'tfcm_elements_per_page', 10 ) );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Direct query is required for a custom table, and caching is not appropriate. ORDER BY clause cannot use placeholders in wpdb->prepare(), so it is safely validated with sanitize_sql_orderby().
		$this->items = $wpdb->get_results(
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
				ORDER BY ' . $orderby_sql . ' LIMIT %d OFFSET %d',
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

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct query is required for a custom table, and caching is not appropriate.
		$total_items = $wpdb->get_var(
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

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}