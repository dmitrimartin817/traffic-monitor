<?php
/**
 * File: /classes/view/class-tfcm-log-table.php
 *
 * Extends WP_List_Table to display Traffic Monitor log entries in the WordPress admin.
 *
 * @package TrafficMonitor
 */

defined( 'ABSPATH' ) || exit;

// Ensure the WP_List_Table class is loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class TFCM_Log_Table
 *
 * Custom table for displaying Traffic Monitor log data with support for sorting, searching, and bulk actions.
 */
class TFCM_Log_Table extends WP_List_Table {
	/**
	 * Defines the columns for the Traffic Monitor log table.
	 *
	 * @return array Associative array where keys are column IDs and values are display labels.
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
	 * Retrieves the list of hidden columns based on the current user's settings.
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
	 * Returns available bulk actions for the log table.
	 *
	 * @return array Associative array of bulk action identifiers and their labels.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete',
			'export' => 'Export',
		);
		return $actions;
	}

	/**
	 * Displays additional navigation elements (buttons) above the table.
	 *
	 * Adds "Delete All" and "Export All" buttons above the table.
	 *
	 * @param string $which The location context ('top' or 'bottom').
	 * @return void
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
	 * Renders the checkbox for a single row in the log table.
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
	 * Renders default content for columns without a custom handler.
	 *
	 * Special handling is provided for the 'request_time' column to include a "View Details" link.
	 *
	 * @param array  $item        The current row's data.
	 * @param string $column_name The current column name.
	 * @return string The rendered content for the column.
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
	 * Displays a message when no log entries are available.
	 *
	 * @return void
	 */
	public function no_items() {
		echo 'No requests found.';
	}

	/**
	 * Prepares the log table data for display, handling sorting, searching, and pagination.
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

		$this->items = TFCM_Database::get_request_table_rows( $search_term, $orderby_sql, $per_page, $offset );

		$total_items = TFCM_Database::count_request_table_rows( $search_term );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}